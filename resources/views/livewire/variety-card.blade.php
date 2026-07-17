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

<div class="px-4 py-5 max-w-lg mx-auto space-y-5">
    <a href="{{ route('dex') }}" wire:navigate class="inline-block text-sm font-bold text-dex-gold">&larr; {{ __('Back to Dex') }}</a>

    @if ($this->catch)
        {{-- Caught state --}}
        @php $photoUrl = $this->catch->getFirstMediaUrl('photo', 'display'); @endphp

        <div class="rounded-[18px] overflow-hidden bg-dex-surface aspect-square flex items-center justify-center shadow-[0_6px_0_#10150a]">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" class="w-full h-full object-cover">
            @else
                <span class="text-7xl">🍎</span>
            @endif
        </div>

        <div>
            <h1 class="font-display font-bold text-2xl text-dex-text">{{ $variety->name }}</h1>
            @if ($variety->origin)
                <p class="text-dex-meta text-[13px] mt-0.5">{{ $variety->origin }}</p>
            @endif
        </div>

        <div class="space-y-1 text-[13px] text-dex-label bg-dex-surface rounded-xl px-3.5 py-3">
            <p>{{ Str::ucfirst($eatenWord) }}: {{ $this->catch->caught_at->format('d.m.Y') }}</p>

            @if ($this->catch->location_label)
                <p>{{ __('Location') }}: {{ $this->catch->location_label }}</p>
            @endif

            @if ($this->catch->lat && $this->catch->lng)
                <p>
                    <a
                        href="https://www.openstreetmap.org/?mlat={{ $this->catch->lat }}&mlon={{ $this->catch->lng }}#map=16/{{ $this->catch->lat }}/{{ $this->catch->lng }}"
                        target="_blank"
                        rel="noopener"
                        class="text-dex-gold font-bold underline underline-offset-4"
                    >
                        {{ __('View on OpenStreetMap') }} &rarr;
                    </a>
                </p>
            @endif
        </div>

        @if ($this->catch->notes)
            <p class="text-sm text-dex-label whitespace-pre-line">{{ $this->catch->notes }}</p>
        @endif

        <div class="flex gap-2.5 text-[13px] font-bold">
            <a href="{{ route('catch.new', ['catch' => $this->catch->id]) }}" wire:navigate class="flex-1 text-center py-2.5 rounded-[14px] bg-dex-card text-dex-text shadow-[0_3px_0_#171d10]">
                {{ __('Edit entry') }}
            </a>

            <button type="button" wire:click="$set('confirmingCatchDelete', true)" class="flex-1 text-center py-2.5 rounded-[14px] bg-dex-delete-bg text-dex-delete-text shadow-[0_3px_0_#241511]">
                {{ __('Delete entry') }}
            </button>
        </div>

        @if ($confirmingCatchDelete)
            <div class="rounded-xl bg-dex-surface p-4 space-y-3">
                <p class="text-sm text-dex-label">{{ __('Delete this entry? The variety will return to unlogged.') }}</p>
                <div class="flex gap-3 text-sm font-bold">
                    <button type="button" wire:click="deleteCatch" class="px-3 py-1.5 rounded-lg bg-dex-delete-bg text-dex-delete-text">
                        {{ __('Yes, delete') }}
                    </button>
                    <button type="button" wire:click="$set('confirmingCatchDelete', false)" class="px-3 py-1.5 rounded-lg bg-dex-card text-dex-label">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        @endif
    @else
        {{-- Uncaught state --}}
        @php $refUrl = $variety->getFirstMediaUrl('reference_photo', 'display'); @endphp

        <div class="rounded-[18px] overflow-hidden bg-dex-dim aspect-square flex items-center justify-center shadow-[0_6px_0_#10150a]">
            @if ($refUrl)
                <img src="{{ $refUrl }}" class="w-full h-full object-cover grayscale opacity-75">
            @else
                <div class="w-16 h-16 rounded-[50%_50%_50%_10px] bg-dex-silhouette -rotate-45"></div>
            @endif
        </div>

        <div>
            <h1 class="font-display font-bold text-2xl text-dex-text">{{ $variety->name }}</h1>
            @if ($variety->origin)
                <p class="text-dex-meta text-[13px] mt-0.5">{{ $variety->origin }}</p>
            @endif
        </div>

        <a
            href="{{ route('catch.new', ['variety' => $variety->id]) }}"
            wire:navigate
            class="block text-center py-3.5 rounded-2xl bg-dex-red-btn text-white font-display font-bold text-base shadow-[0_5px_0_#a5392b] active:translate-y-1 active:shadow-[0_1px_0_#a5392b] transition-[transform,box-shadow]"
        >
            {{ __('Log it!') }} 🍏
        </a>
    @endif

    @if ($this->isOwnCustom)
        <div class="border-t border-dex-surface pt-4 space-y-3">
            @if (! $editingVariety)
                <div class="flex flex-wrap gap-2.5 text-[13px] font-bold">
                    <button type="button" wire:click="startEditingVariety" class="px-3.5 py-1.5 rounded-xl bg-dex-card text-dex-text">
                        {{ __('Edit variety') }}
                    </button>
                    <button type="button" wire:click="$set('confirmingVarietyDelete', true)" class="px-3.5 py-1.5 rounded-xl bg-dex-delete-bg text-dex-delete-text">
                        {{ __('Delete variety') }}
                    </button>
                </div>

                @if ($confirmingVarietyDelete)
                    <div class="rounded-xl bg-dex-surface p-4 space-y-3">
                        <p class="text-sm text-dex-label">{{ __('Delete this variety? Your entry for it will be deleted too. This cannot be undone.') }}</p>
                        <div class="flex gap-3 text-sm font-bold">
                            <button type="button" wire:click="deleteVariety" class="px-3 py-1.5 rounded-lg bg-dex-delete-bg text-dex-delete-text">
                                {{ __('Yes, delete') }}
                            </button>
                            <button type="button" wire:click="$set('confirmingVarietyDelete', false)" class="px-3 py-1.5 rounded-lg bg-dex-card text-dex-label">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <form wire:submit="updateVariety" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-dex-label mb-1.5">{{ __('Name') }}</label>
                        <input type="text" wire:model="varietyName" class="w-full rounded-xl border-0 bg-dex-card text-dex-text text-sm focus:ring-2 focus:ring-dex-gold">
                        @error('varietyName') <p class="text-sm text-dex-delete-text mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-dex-label mb-1.5">{{ __('Origin (optional)') }}</label>
                        <input type="text" wire:model="varietyOrigin" class="w-full rounded-xl border-0 bg-dex-card text-dex-text text-sm focus:ring-2 focus:ring-dex-gold">
                        @error('varietyOrigin') <p class="text-sm text-dex-delete-text mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-dex-label mb-1.5">{{ __('Reference photo (optional)') }}</label>
                        <input type="file" wire:model="varietyReferencePhoto" accept="image/*" class="w-full text-sm text-dex-label">
                        @if ($varietyReferencePhoto)
                            <img src="{{ $varietyReferencePhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-[14px] shadow-[0_4px_0_#10150a]">
                        @endif
                        @error('varietyReferencePhoto') <p class="text-sm text-dex-delete-text mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 text-sm font-bold">
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-dex-gold text-dex-gold-ink shadow-[0_3px_0_#a8891f]">
                            {{ __('Save') }}
                        </button>
                        <button type="button" wire:click="cancelEditingVariety" class="px-3.5 py-1.5 rounded-xl bg-dex-card text-dex-label">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
