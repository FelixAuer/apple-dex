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

    public function mount(): void
    {
        $this->sort = session('dex_sort', 'recent');
        $this->eatenWord = EatenSynonyms::pick();

        if (session()->has('toast')) {
            $this->dispatch('toast', message: session()->pull('toast'));
        }
    }

    public function updatedSort(): void
    {
        session(['dex_sort' => $this->sort]);
        unset($this->allVisible);
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
    public function grid(): Collection
    {
        $varieties = $this->allVisible;

        if ($this->search !== '') {
            $needle = Str::lower(Str::ascii($this->search));

            $varieties = $varieties->filter(
                fn (Variety $variety) => str_contains(Str::lower(Str::ascii($variety->name)), $needle)
            );
        }

        [$caught, $uncaught] = $varieties->partition(fn (Variety $variety) => $variety->catches->isNotEmpty());

        $caught = $caught->sortByDesc(fn (Variety $variety) => $variety->catches->first()->caught_at)->values();
        $uncaught = $uncaught->sortBy(fn (Variety $variety) => Str::lower($variety->name))->values();

        return match ($this->sort) {
            'az' => $varieties->sortBy(fn (Variety $variety) => Str::lower($variety->name))->values(),
            'uncaught_first' => $uncaught->concat($caught),
            default => $caught->concat($uncaught),
        };
    }
}; ?>

<div class="pb-28">
    <div class="px-4 pt-4 space-y-4">
        <h1 class="text-2xl font-bold">
            {{ $this->caughtCount }} {{ Str::plural('variety', $this->caughtCount) }} {{ $eatenWord }}
        </h1>

        <div class="relative">
            <input
                type="search"
                wire:model.live.debounce.250ms="search"
                placeholder="{{ __('Search varieties…') }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:border-green-500 focus:ring-green-500"
            >
            <svg wire:loading wire:target="search" class="animate-spin absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <div class="flex gap-2 text-sm">
            @foreach (['recent' => __('Recently caught'), 'az' => __('A–Z'), 'uncaught_first' => __('Uncaught first')] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('sort', '{{ $value }}')"
                    @class([
                        'px-3 py-1 rounded-full border',
                        'bg-green-600 text-white border-green-600' => $sort === $value,
                        'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300' => $sort !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="px-4 mt-4">
        @if ($this->grid->isEmpty())
            <div class="text-center py-16 space-y-4">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('No varieties match ":search".', ['search' => $search]) }}
                </p>

                @if ($search !== '')
                    <a
                        href="{{ route('catch.new', ['name' => $search]) }}"
                        wire:navigate
                        class="inline-block px-4 py-2 rounded-lg bg-green-600 text-white font-medium"
                    >
                        {{ __("➕ Create ':name' as new variety", ['name' => $search]) }}
                    </a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                @foreach ($this->grid as $variety)
                    @php $catch = $variety->catches->first(); @endphp

                    <a
                        href="{{ route('varieties.show', $variety) }}"
                        wire:navigate
                        wire:key="variety-{{ $variety->id }}"
                        class="block rounded-xl overflow-hidden aspect-square relative bg-gray-200 dark:bg-gray-800"
                    >
                        @if ($catch)
                            @php $photoUrl = $catch->getFirstMediaUrl('photo', 'thumb'); @endphp

                            @if ($photoUrl)
                                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $photoUrl }}')"></div>
                            @else
                                <div class="absolute inset-0 flex items-center justify-center bg-green-100 dark:bg-green-900">
                                    <span class="text-4xl">🍎</span>
                                </div>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-1.5">
                                <p class="text-white text-xs font-medium truncate">{{ $variety->name }}</p>
                                <p class="text-white/80 text-[10px]">{{ $catch->caught_at->format('d.m.Y') }}</p>
                            </div>
                        @else
                            @php $refUrl = $variety->getFirstMediaUrl('reference_photo', 'thumb'); @endphp

                            <div class="absolute inset-0 bg-cover bg-center grayscale opacity-50" @if ($refUrl) style="background-image: url('{{ $refUrl }}')" @endif>
                                @unless ($refUrl)
                                    <div class="w-full h-full flex items-center justify-center p-6 text-gray-400 dark:text-gray-600">
                                        <img src="{{ asset('images/apple-silhouette.svg') }}" alt="" class="w-full h-full">
                                    </div>
                                @endunless
                            </div>

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-1.5">
                                <p class="text-white text-xs font-medium truncate">{{ $variety->name }}</p>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <a
        href="{{ route('catch.new') }}"
        wire:navigate
        class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-green-600 text-white text-2xl font-bold flex items-center justify-center shadow-lg"
        aria-label="{{ __('Log a new catch') }}"
    >
        +
    </a>
</div>
