@props(['filled' => false, 'size' => 24])

<svg
    {{ $attributes->class(['shrink-0']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="{{ $filled ? '#e0c14c' : 'none' }}"
    stroke="{{ $filled ? '#e0c14c' : '#8a9270' }}"
    stroke-width="1.5"
    stroke-linejoin="round"
>
    <path d="M12 2.5l2.9 6.4 7 .7-5.3 4.7 1.6 6.9L12 17.6 5.8 21.2l1.6-6.9L2.1 9.6l7-.7z"/>
</svg>
