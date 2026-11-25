@props(['name', 'label', 'id' => null])

<div {{ $attributes->class('listing-field') }}>
    <label for="{{ $id ?? $name }}">{{ $label }}</label>
    {{ $slot }}
    @error($name)
        <p class="auth-error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
