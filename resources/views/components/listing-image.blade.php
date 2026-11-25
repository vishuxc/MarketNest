@props(['image' => null, 'alt' => '', 'loading' => 'lazy'])

@php
    $placeholder = asset('images/listing-placeholder.png');
    $source = $image?->publicUrl() ?? $placeholder;
@endphp

<img {{ $attributes->class(['listing-image']) }}
    src="{{ $source }}"
    @if ($source !== $placeholder) data-image-fallback="{{ $placeholder }}" @endif
    alt="{{ $source !== $placeholder ? $alt : 'No image available' }}" loading="{{ $loading }}" width="800" height="500">
