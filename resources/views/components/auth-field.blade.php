@props(['name', 'label', 'type' => 'text', 'autocomplete'])

<div class="auth-field">
    <label for="{{ $name }}">{{ $label }}</label>
    <div @class(['auth-password' => $type === 'password'])>
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            autocomplete="{{ $autocomplete }}"
            @if ($type !== 'password') value="{{ old($name) }}" @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes }}
            required
        >
        @if ($type === 'password')
            <button class="auth-toggle" type="button" aria-controls="{{ $name }}" aria-pressed="false" hidden>
                <span class="sr-only">Show {{ strtolower($label) }}</span>
                <span aria-hidden="true" data-toggle-text>Show</span>
            </button>
        @endif
    </div>
    @error($name)
        <p class="auth-error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
