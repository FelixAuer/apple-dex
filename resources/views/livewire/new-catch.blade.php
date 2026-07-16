<?php

use App\Models\AppleCatch;
use App\Models\Variety;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dex')] class extends Component
{
    use WithFileUploads;

    public string $query = '';

    public ?int $selectedVarietyId = null;

    public string $selectedVarietyName = '';

    public bool $creatingNew = false;

    public string $newVarietyOrigin = '';

    public $newVarietyReferencePhoto = null;

    public $photo = null;

    public string $caughtAt;

    public ?float $lat = null;

    public ?float $lng = null;

    public string $locationLabel = '';

    public string $notes = '';

    public ?int $editingCatchId = null;

    public ?string $existingPhotoUrl = null;

    public function mount(): void
    {
        $this->caughtAt = now()->toDateString();

        $user = Auth::user();

        if ($catchId = request()->integer('catch')) {
            $catch = AppleCatch::query()->where('user_id', $user->id)->findOrFail($catchId);

            $this->editingCatchId = $catch->id;
            $this->selectedVarietyId = $catch->variety_id;
            $this->selectedVarietyName = $catch->variety->name;
            $this->caughtAt = $catch->caught_at->toDateString();
            $this->lat = $catch->lat ? (float) $catch->lat : null;
            $this->lng = $catch->lng ? (float) $catch->lng : null;
            $this->locationLabel = $catch->location_label ?? '';
            $this->notes = $catch->notes ?? '';
            $this->existingPhotoUrl = $catch->getFirstMediaUrl('photo', 'thumb') ?: null;

            return;
        }

        if ($varietyId = request()->integer('variety')) {
            $variety = Variety::query()->visibleTo($user)->find($varietyId);

            if ($variety) {
                $this->selectedVarietyId = $variety->id;
                $this->selectedVarietyName = $variety->name;
            }
        } elseif ($name = request()->string('name')->trim()->value()) {
            $this->query = $name;
            $this->creatingNew = true;
        }
    }

    #[Computed]
    public function suggestions(): array
    {
        if ($this->selectedVarietyId || $this->creatingNew || $this->query === '') {
            return [];
        }

        $user = Auth::user();
        $needle = Str::lower(Str::ascii($this->query));

        $matches = Variety::query()
            ->visibleTo($user)
            ->get()
            ->filter(fn (Variety $variety) => str_contains(Str::lower(Str::ascii($variety->name)), $needle))
            ->sortBy(fn (Variety $variety) => Str::lower($variety->name))
            ->take(8)
            ->values();

        $hasExactMatch = $matches->contains(
            fn (Variety $variety) => Str::lower($variety->name) === Str::lower($this->query)
        );

        $suggestions = $matches->map(fn (Variety $variety) => [
            'type' => 'variety',
            'id' => $variety->id,
            'name' => $variety->name,
        ])->all();

        if (! $hasExactMatch) {
            $suggestions[] = ['type' => 'create', 'id' => null, 'name' => $this->query];
        }

        return $suggestions;
    }

    #[Computed]
    public function alreadyCaught(): ?AppleCatch
    {
        if (! $this->selectedVarietyId || $this->editingCatchId) {
            return null;
        }

        return AppleCatch::query()
            ->where('user_id', Auth::id())
            ->where('variety_id', $this->selectedVarietyId)
            ->first();
    }

    public function selectVariety(int $varietyId): void
    {
        $variety = Variety::query()->visibleTo(Auth::user())->findOrFail($varietyId);

        $this->selectedVarietyId = $variety->id;
        $this->selectedVarietyName = $variety->name;
        $this->creatingNew = false;
        $this->query = '';
    }

    public function selectCreateNew(): void
    {
        $this->creatingNew = true;
        $this->selectedVarietyId = null;
    }

    public function changeVariety(): void
    {
        $this->selectedVarietyId = null;
        $this->selectedVarietyName = '';
        $this->creatingNew = false;
        $this->query = '';
        $this->newVarietyOrigin = '';
        $this->newVarietyReferencePhoto = null;
    }

    public function useMyLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function save(): void
    {
        if ($this->editingCatchId) {
            $this->saveEdit();

            return;
        }

        $user = Auth::user();

        $rules = [
            'caughtAt' => ['required', 'date', 'before_or_equal:today'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'locationLabel' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ];

        if ($this->creatingNew) {
            $rules['query'] = [
                'required',
                'string',
                'max:100',
                function (string $attribute, $value, $fail) use ($user) {
                    $exists = Variety::query()
                        ->visibleTo($user)
                        ->whereRaw('LOWER(name) = ?', [Str::lower(trim($value))])
                        ->exists();

                    if ($exists) {
                        $fail(__('A variety with this name already exists.'));
                    }
                },
            ];
            $rules['newVarietyOrigin'] = ['nullable', 'string', 'max:150'];
            $rules['newVarietyReferencePhoto'] = ['nullable', 'image', 'max:10240'];
        } else {
            $rules['selectedVarietyId'] = ['required', 'integer'];
        }

        $this->validate($rules);

        if (! $this->creatingNew && $this->alreadyCaught) {
            $this->addError('selectedVarietyId', __('This variety has already been caught.'));

            return;
        }

        try {
            $variety = DB::transaction(function () use ($user) {
                if ($this->creatingNew) {
                    $variety = Variety::create([
                        'name' => trim($this->query),
                        'origin' => $this->newVarietyOrigin ?: null,
                        'user_id' => $user->id,
                    ]);

                    if ($this->newVarietyReferencePhoto) {
                        $variety->addMedia($this->newVarietyReferencePhoto->getRealPath())
                            ->usingFileName($this->newVarietyReferencePhoto->getClientOriginalName())
                            ->toMediaCollection('reference_photo');
                    }
                } else {
                    $variety = Variety::query()->visibleTo($user)->findOrFail($this->selectedVarietyId);
                }

                $catch = AppleCatch::create([
                    'user_id' => $user->id,
                    'variety_id' => $variety->id,
                    'caught_at' => $this->caughtAt,
                    'lat' => $this->lat,
                    'lng' => $this->lng,
                    'location_label' => $this->locationLabel ?: null,
                    'notes' => $this->notes ?: null,
                ]);

                if ($this->photo) {
                    $catch->addMedia($this->photo->getRealPath())
                        ->usingFileName($this->photo->getClientOriginalName())
                        ->toMediaCollection('photo');
                }

                return $variety;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->addError('selectedVarietyId', __('This variety has already been caught.'));

                return;
            }

            throw $e;
        }

        $total = Variety::query()->visibleTo($user)->count();
        $caughtCount = Variety::query()->visibleTo($user)
            ->whereHas('catches', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        session()->flash('toast', __(':name caught! :caught / :total', [
            'name' => $variety->name,
            'caught' => $caughtCount,
            'total' => $total,
        ]));

        $this->redirect(route('dex'), navigate: true);
    }

    protected function saveEdit(): void
    {
        $catch = AppleCatch::query()->where('user_id', Auth::id())->findOrFail($this->editingCatchId);

        $this->authorize('update', $catch);

        $this->validate([
            'caughtAt' => ['required', 'date', 'before_or_equal:today'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'locationLabel' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $catch->update([
            'caught_at' => $this->caughtAt,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'location_label' => $this->locationLabel ?: null,
            'notes' => $this->notes ?: null,
        ]);

        if ($this->photo) {
            $catch->addMedia($this->photo->getRealPath())
                ->usingFileName($this->photo->getClientOriginalName())
                ->toMediaCollection('photo');
        }

        session()->flash('toast', __('Catch updated.'));

        $this->redirect(route('varieties.show', $catch->variety_id), navigate: true);
    }
}; ?>

<div class="px-4 py-6 max-w-lg mx-auto">
    <h1 class="text-xl font-bold mb-6">{{ $editingCatchId ? __('Edit catch') : __('Log a catch') }}</h1>

    @if ($this->alreadyCaught)
        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 p-4 space-y-3">
            <p class="text-amber-800 dark:text-amber-200">
                {{ __('Already caught on :date', ['date' => $this->alreadyCaught->caught_at->format('d.m.Y')]) }}
            </p>
            <a href="{{ route('varieties.show', $selectedVarietyId) }}" wire:navigate class="inline-block underline text-amber-900 dark:text-amber-100 font-medium">
                {{ __('View variety') }} &rarr;
            </a>
            <button type="button" wire:click="changeVariety" class="block text-sm text-gray-500 underline">
                {{ __('Choose a different variety') }}
            </button>
        </div>
    @else
        <form wire:submit="save" class="space-y-5">
            {{-- Variety --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Variety') }}</label>

                @if ($selectedVarietyId || $creatingNew)
                    <div class="flex items-center justify-between rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2">
                        <span class="font-medium">
                            {{ $creatingNew ? $query : $selectedVarietyName }}
                            @if ($creatingNew)
                                <span class="text-xs text-gray-500">({{ __('new') }})</span>
                            @endif
                        </span>
                        @unless ($editingCatchId)
                            <button type="button" wire:click="changeVariety" class="text-sm text-gray-500 underline">
                                {{ __('Change') }}
                            </button>
                        @endunless
                    </div>

                    @if ($creatingNew)
                        <div class="mt-3 space-y-3 rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">{{ __('Origin (optional)') }}</label>
                                <input type="text" wire:model="newVarietyOrigin" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="{{ __('e.g. Steiermark, Österreich') }}">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">{{ __('Reference photo (optional)') }}</label>
                                <input type="file" wire:model="newVarietyReferencePhoto" accept="image/*" class="w-full text-sm">
                                @if ($newVarietyReferencePhoto)
                                    <img src="{{ $newVarietyReferencePhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-lg">
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <div class="relative">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="query"
                            placeholder="{{ __('Search varieties…') }}"
                            autocomplete="off"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        >
                        <svg wire:loading wire:target="query" class="animate-spin absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>

                    @if (! empty($this->suggestions))
                        <ul class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                            @foreach ($this->suggestions as $suggestion)
                                <li>
                                    @if ($suggestion['type'] === 'variety')
                                        <button type="button" wire:click="selectVariety({{ $suggestion['id'] }})" class="w-full text-start px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                                            {{ $suggestion['name'] }}
                                        </button>
                                    @else
                                        <button type="button" wire:click="selectCreateNew" class="w-full text-start px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 text-green-700 dark:text-green-400 font-medium">
                                            {{ __("➕ Create ':name' as new variety", ['name' => $suggestion['name']]) }}
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif

                @error('query') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('selectedVarietyId') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Photo --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="photo" accept="image/*" capture="environment" class="w-full text-sm">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" class="mt-2 h-32 w-32 object-cover rounded-lg">
                @elseif ($existingPhotoUrl)
                    <img src="{{ $existingPhotoUrl }}" class="mt-2 h-32 w-32 object-cover rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">{{ __('Current photo — choose a new file to replace it.') }}</p>
                @endif
                @error('photo') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Date') }}</label>
                <input type="date" wire:model="caughtAt" max="{{ now()->toDateString() }}" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                @error('caughtAt') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Location --}}
            <div
                x-data="{
                    request() {
                        if (! navigator.geolocation) return;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => $wire.useMyLocation(pos.coords.latitude, pos.coords.longitude),
                            () => {},
                        );
                    },
                }"
                class="space-y-2"
            >
                <label class="block text-sm font-medium mb-1">{{ __('Location') }}</label>

                <button type="button" @click="request" class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700">
                    📍 {{ __('Use my location') }}
                </button>

                @if ($lat !== null)
                    <span class="text-xs text-green-700 dark:text-green-400 ms-2">{{ __('Location captured') }}</span>
                @endif

                <input
                    type="text"
                    wire:model="locationLabel"
                    placeholder="{{ __('e.g. Naschmarkt, Wien') }}"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                >
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Notes (optional)') }}</label>
                <textarea wire:model="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"></textarea>
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full py-3 rounded-lg bg-green-600 text-white font-semibold disabled:opacity-50"
            >
                {{ $editingCatchId ? __('Save changes') : __('Save catch') }}
            </button>
        </form>
    @endif
</div>
