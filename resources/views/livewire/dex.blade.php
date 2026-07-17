<?php

use App\Models\Variety;
use App\Support\EatenSynonyms;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dex')] class extends Component
{
    public string $search = '';

    public string $sort = 'recent';

    public string $eatenWord = 'eaten';

    public string $caughtWord = 'caught';

    public string $uncaughtWord = 'caught';

    public function mount(): void
    {
        $this->sort = session('dex_sort', 'recent');
        [$this->eatenWord, $this->caughtWord, $this->uncaughtWord] = EatenSynonyms::pickMany(3);

        if (session()->has('toast')) {
            $this->dispatch('toast', message: session()->pull('toast'));
        }
    }

    public function updatedSort(): void
    {
        session(['dex_sort' => $this->sort]);
        unset($this->caught);
    }

    #[Computed]
    public function allVisible(): Collection
    {
        $user = Auth::user();

        return Variety::query()
            ->visibleTo($user)
            ->with([
                'media',
                'catches' => fn ($query) => $query->where('user_id', $user->id)->with('media'),
            ])
            ->get();
    }

    #[Computed]
    public function caughtCount(): int
    {
        return $this->allVisible->filter(fn (Variety $variety) => $variety->catches->isNotEmpty())->count();
    }

    #[Computed]
    public function filtered(): Collection
    {
        $varieties = $this->allVisible;

        if ($this->search === '') {
            return $varieties;
        }

        $needle = Str::lower(Str::ascii($this->search));

        return $varieties->filter(
            fn (Variety $variety) => str_contains(Str::lower(Str::ascii($variety->name)), $needle)
        );
    }

    #[Computed]
    public function caught(): Collection
    {
        $caught = $this->filtered->filter(fn (Variety $variety) => $variety->catches->isNotEmpty());

        return match ($this->sort) {
            'az' => $caught->sortBy(fn (Variety $variety) => Str::lower($variety->name))->values(),
            default => $caught->sortByDesc(fn (Variety $variety) => $variety->catches->first()->caught_at)->values(),
        };
    }

    #[Computed]
    public function uncaught(): Collection
    {
        return $this->filtered
            ->filter(fn (Variety $variety) => $variety->catches->isEmpty())
            ->sortBy(fn (Variety $variety) => Str::lower($variety->name))
            ->values();
    }
}; ?>

<div class="pb-28">
    <div class="px-4 pt-5 space-y-4 anim-fade-rise" style="animation-delay: .02s">
        <div class="flex items-baseline gap-2">
            <span class="font-display font-bold text-[28px] leading-none text-dex-gold">{{ $this->caughtCount }}</span>
            <span class="text-dex-label text-sm">{{ Str::plural('variety', $this->caughtCount) }} {{ $eatenWord }}</span>
        </div>
    </div>

    <div class="px-4 pt-4 anim-fade-rise" style="animation-delay: .1s">
        <div class="relative">
            <input
                type="search"
                wire:model.live.debounce.250ms="search"
                placeholder="{{ __('Search varieties…') }}"
                class="w-full rounded-2xl border-0 bg-dex-card text-dex-text placeholder-dex-meta focus:ring-2 focus:ring-dex-gold text-sm py-3 px-4"
            >
            <svg wire:loading wire:target="search" class="animate-spin absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-dex-meta" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    <div class="px-4 mt-6 space-y-10">
        @if ($this->caught->isEmpty() && $this->uncaught->isEmpty())
            <div class="text-center py-16 space-y-4">
                <p class="text-dex-meta">
                    {{ __('No varieties match ":search".', ['search' => $search]) }}
                </p>

                @if ($search !== '')
                    <a
                        href="{{ route('catch.new', ['name' => $search]) }}"
                        wire:navigate
                        class="inline-block px-4 py-2 rounded-xl bg-dex-gold text-dex-gold-ink font-display font-bold shadow-[0_4px_0_#a8891f]"
                    >
                        {{ __("➕ Create ':name' as new variety", ['name' => $search]) }}
                    </a>
                @endif
            </div>
        @else
            @if ($this->caught->isNotEmpty())
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-bold text-sm text-dex-label">{{ Str::ucfirst($caughtWord) }}</h2>

                        <div class="flex gap-2 text-xs font-bold">
                            @foreach (['recent' => __('Recent'), 'az' => __('A–Z')] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('sort', '{{ $value }}')"
                                    @class([
                                        'px-4 py-1.5 rounded-full transition-colors',
                                        'bg-dex-gold text-dex-gold-ink shadow-[0_3px_0_#a8891f]' => $sort === $value,
                                        'bg-dex-card text-dex-label' => $sort !== $value,
                                    ])
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3.5">
                        @foreach ($this->caught as $variety)
                            @php
                                $catch = $variety->catches->first();
                                $rotation = ['-rotate-2', 'rotate-[1.5deg]', '-rotate-1'][$loop->index % 3];
                                $delay = 0.18 + min($loop->index, 9) * 0.02;
                                $photoUrl = $catch->getFirstMediaUrl('photo', 'thumb');
                            @endphp

                            <a
                                href="{{ route('varieties.show', $variety) }}"
                                wire:navigate
                                wire:key="variety-{{ $variety->id }}"
                                class="block rounded-2xl overflow-hidden anim-fade-in bg-dex-card shadow-[0_4px_0_#171d10] {{ $rotation }}"
                                style="animation-delay: {{ $delay }}s"
                            >
                                <div class="aspect-[4/3] bg-cover bg-center bg-dex-surface" @if ($photoUrl) style="background-image: url('{{ $photoUrl }}')" @endif>
                                    @unless ($photoUrl)
                                        <div class="w-full h-full flex items-center justify-center text-3xl">🍎</div>
                                    @endunless
                                </div>

                                <div class="px-2.5 py-2">
                                    <p class="font-display font-semibold text-dex-text text-[12.5px] truncate">{{ $variety->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->uncaught->isNotEmpty())
                <div class="space-y-3">
                    <h2 class="font-display font-bold text-sm text-dex-label">{{ __('Not yet :word', ['word' => $uncaughtWord]) }}</h2>

                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3.5">
                        @foreach ($this->uncaught as $variety)
                            @php
                                $delay = 0.18 + min($loop->index, 9) * 0.02;
                                $refUrl = $variety->getFirstMediaUrl('reference_photo', 'thumb');
                            @endphp

                            <a
                                href="{{ route('varieties.show', $variety) }}"
                                wire:navigate
                                wire:key="variety-{{ $variety->id }}"
                                class="block rounded-2xl overflow-hidden anim-fade-in-dim bg-dex-dim"
                                style="animation-delay: {{ $delay }}s"
                            >
                                <div class="aspect-[4/3] flex items-center justify-center">
                                    @if ($refUrl)
                                        <div class="w-full h-full bg-cover bg-center grayscale opacity-60" style="background-image: url('{{ $refUrl }}')"></div>
                                    @else
                                        <div class="w-8 h-8 rounded-[50%_50%_50%_6px] bg-dex-silhouette -rotate-45"></div>
                                    @endif
                                </div>

                                <div class="px-2.5 py-2">
                                    <p class="font-display font-semibold text-dex-warm text-[12px] truncate">{{ $variety->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    <a
        href="{{ route('catch.new') }}"
        wire:navigate
        class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-dex-red-btn text-white text-2xl font-bold flex items-center justify-center shadow-[0_5px_0_#a5392b] active:translate-y-1 active:shadow-[0_1px_0_#a5392b] transition-[transform,box-shadow]"
        aria-label="{{ __('Log a new entry') }}"
    >
        +
    </a>
</div>
