@props(['size' => 32])

<div
    {{ $attributes->class(['rounded-[9px] bg-dex-red shadow-[0_3px_0_#a5392b] flex items-center justify-center shrink-0']) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
>
    <div class="relative" style="width: {{ $size * 0.5 }}px; height: {{ $size * 0.5 }}px;">
        <div class="absolute rounded-full bg-dex-text" style="width: 78%; height: 78%; bottom: 0; left: 8%;"></div>
        <div class="absolute bg-dex-gold rotate-[20deg]" style="width: 38%; height: 22%; border-radius: 0 60% 0 60%; top: 0; right: 6%;"></div>
    </div>
</div>
