<?php

use App\Models\AppleCatch;
use App\Models\Variety;
use App\Support\EatenSynonyms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.dex')] class extends Component
{
    use WithFileUploads;

    public Variety $variety;

    public bool $editingVariety = false;

    public string $varietyName = '';

    public string $varietyOrigin = '';

    public $varietyReferencePhoto = null;

    public bool $confirmingCatchDelete = false;

    public bool $confirmingVarietyDelete = false;

    public string $eatenWord = 'eaten';

    public function mount(Variety $variety): void
    {
        $this->authorize('view', $variety);

        $this->variety = $variety;
        $this->varietyName = $variety->name;
        $this->varietyOrigin = $variety->origin ?? '';
        $this->eatenWord = EatenSynonyms::pick();
    }

    #[Computed]
    public function catch(): ?AppleCatch
    {
        return AppleCatch::query()
            ->where('user_id', Auth::id())
            ->where('variety_id', $this->variety->id)
            ->first();
    }

    #[Computed]
    public function isOwnCustom(): bool
    {
        return $this->variety->user_id === Auth::id();
    }

    public function startEditingVariety(): void
    {
        $this->authorize('update', $this->variety);

        $this->editingVariety = true;
        $this->varietyName = $this->variety->name;
        $this->varietyOrigin = $this->variety->origin ?? '';
    }

    public function cancelEditingVariety(): void
    {
        $this->editingVariety = false;
        $this->varietyReferencePhoto = null;
    }

    public function updateVariety(): void
    {
        $this->authorize('update', $this->variety);

        $user = Auth::user();

        $this->validate([
            'varietyName' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, $value, $fail) use ($user) {
                    $exists = Variety::query()
                        ->visibleTo($user)
                        ->where('id', '!=', $this->variety->id)
                        ->whereRaw('LOWER(name) = ?', [Str::lower(trim($value))])
                        ->exists();

                    if ($exists) {
                        $fail(__('A variety with this name already exists.'));
                    }
                },
            ],
            'varietyOrigin' => ['nullable', 'string', 'max:150'],
            'varietyReferencePhoto' => ['nullable', 'image', 'max:10240'],
        ]);

        $this->variety->update([
            'name' => trim($this->varietyName),
            'origin' => $this->varietyOrigin ?: null,
        ]);

        if ($this->varietyReferencePhoto) {
            $this->variety->addMedia($this->varietyReferencePhoto->getRealPath())
                ->usingFileName($this->varietyReferencePhoto->getClientOriginalName())
                ->toMediaCollection('reference_photo');
        }

        $this->variety->refresh();
        $this->editingVariety = false;
        $this->varietyReferencePhoto = null;
    }

    public function deleteCatch(): void
    {
        $catch = $this->catch;

        if (! $catch) {
            return;
        }

        $this->authorize('delete', $catch);

        $catch->delete();

        $this->confirmingCatchDelete = false;
        unset($this->catch);
    }

    public function deleteVariety(): void
    {
        $this->authorize('delete', $this->variety);

        $this->variety->delete();

        $this->redirect(route('dex'), navigate: true);
    }
}; ?>

<div class="px-4 py-6 max-w-lg mx-auto space-y-6">
    <a href="{{ route('dex') }}" wire:navigate class="text-sm text-gray-500">&larr; {{ __('Back to Dex') }}</a>

    @if ($this->catch)
        {{-- Caught state --}}
        @php $photoUrl = $this->catch->getFirstMediaUrl('photo', 'display'); @endphp

        <div class="rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 aspect-square flex items-center justify-center">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" class="w-full h-full object-cover">
            @else
                <span class="text-7xl">🍎</span>
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ $variety->name }}</h1>
            @if ($variety->origin)
                <p class="text-gray-500 dark:text-gray-400">{{ $variety->origin }}</p>
            @endif
        </div>

        <div class="space-y-1 text-sm">
            <p><span class="font-medium">{{ Str::ucfirst($eatenWord) }}:</span> {{ $this->catch->caught_at->format('d.m.Y') }}</p>

            @if ($this->catch->location_label)
                <p><span class="font-medium">{{ __('Location') }}:</span> {{ $this->catch->location_label }}</p>
            @endif

            @if ($this->catch->lat && $this->catch->lng)
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $this->catch->lat }}&mlon={{ $this->catch->lng }}#map=16/{{ $this->catch->lat }}/{{ $this->catch->lng }}"
                    target="_blank"
                    rel="noopener"
                    class="text-green-700 dark:text-green-400 underline"
                >
                    {{ __('View on OpenStreetMap') }} &rarr;
                </a>
            @endif
        </div>

        @if ($this->catch->notes)
            <p class="text-sm whitespace-pre-line">{{ $this->catch->notes }}</p>
        @endif

        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('catch.new', ['catch' => $this->catch->id]) }}" wire:navigate class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700">
                {{ __('Edit catch') }}
            </a>

            <button type="button" wire:click="$set('confirmingCatchDelete', true)" class="px-3 py-1.5 rounded-lg border border-red-300 text-red-600 dark:border-red-800 dark:text-red-400">
                {{ __('Delete catch') }}
            </button>
        </div>

        @if ($confirmingCatchDelete)
            <div class="rounded-lg border border-red-300 dark:border-red-800 p-4 space-y-3">
                <p class="text-sm">{{ __('Delete this catch? The variety will return to uncaught.') }}</p>
                <div class="flex gap-3">
                    <button type="button" wire:click="deleteCatch" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm">
                        {{ __('Yes, delete') }}
                    </button>
                    <button type="button" wire:click="$set('confirmingCatchDelete', false)" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-sm">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        @endif
    @else
        {{-- Uncaught state --}}
        @php $refUrl = $variety->getFirstMediaUrl('reference_photo', 'display'); @endphp

        <div class="rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 aspect-square flex items-center justify-center">
            @if ($refUrl)
                <img src="{{ $refUrl }}" class="w-full h-full object-cover grayscale opacity-60">
            @else
                <img src="{{ asset('images/apple-silhouette.svg') }}" alt="" class="w-1/2 h-1/2 text-gray-400 dark:text-gray-600">
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ $variety->name }}</h1>
            @if ($variety->origin)
                <p class="text-gray-500 dark:text-gray-400">{{ $variety->origin }}</p>
            @endif
        </div>

        <a
            href="{{ route('catch.new', ['variety' => $variety->id]) }}"
            wire:navigate
            class="block text-center py-3 rounded-lg bg-green-600 text-white font-semibold"
        >
            {{ __('Catch it!') }}
        </a>
    @endif

    @if ($this->isOwnCustom)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
            @if (! $editingVariety)
                <div class="flex flex-wrap gap-3 text-sm">
                    <button type="button" wire:click="startEditingVariety" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700">
                        {{ __('Edit variety') }}
                    </button>
                    <button type="button" wire:click="$set('confirmingVarietyDelete', true)" class="px-3 py-1.5 rounded-lg border border-red-300 text-red-600 dark:border-red-800 dark:text-red-400">
                        {{ __('Delete variety') }}
                    </button>
                </div>

                @if ($confirmingVarietyDelete)
                    <div class="rounded-lg border border-red-300 dark:border-red-800 p-4 space-y-3">
                        <p class="text-sm">{{ __('Delete this variety? Your catch of it will be deleted too. This cannot be undone.') }}</p>
                        <div class="flex gap-3">
                            <button type="button" wire:click="deleteVariety" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm">
                                {{ __('Yes, delete') }}
                            </button>
                            <button type="button" wire:click="$set('confirmingVarietyDelete', false)" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-sm">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <form wire:submit="updateVariety" class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('Name') }}</label>
                        <input type="text" wire:model="varietyName" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        @error('varietyName') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('Origin (optional)') }}</label>
                        <input type="text" wire:model="varietyOrigin" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        @error('varietyOrigin') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('Reference photo (optional)') }}</label>
                        <input type="file" wire:model="varietyReferencePhoto" accept="image/*" class="w-full text-sm">
                        @if ($varietyReferencePhoto)
                            <img src="{{ $varietyReferencePhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-lg">
                        @endif
                        @error('varietyReferencePhoto') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm">
                            {{ __('Save') }}
                        </button>
                        <button type="button" wire:click="cancelEditingVariety" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-sm">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
