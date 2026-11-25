@props(['name', 'label', 'choices' => [], 'selected' => null, 'error' => null, 'metadata' => [], 'placeholder' => null])

<div class="listing-field">
    <label for="browse-{{ $name }}">{{ $label }}</label>
    <select id="browse-{{ $name }}" name="{{ $name }}" {{ $attributes }}
        @if ($error) aria-invalid="true" aria-describedby="browse-filter-{{ $name }}-error" @endif>
        <option value="">{{ $placeholder ?? 'Any '.strtolower($label) }}</option>
        @foreach ($choices as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)
                @isset($metadata[$value]['state']) data-state="{{ $metadata[$value]['state'] }}" @endisset
                @isset($metadata[$value]['country']) data-country="{{ $metadata[$value]['country'] }}" @endisset>{{ $text }}</option>
        @endforeach
    </select>
    @if ($error)
        <p class="auth-error" id="browse-filter-{{ $name }}-error">{{ $error }}</p>
    @endif
    @if ($attributes->has('data-options'))
        <span class="listing-meta" data-browse-status role="status"></span>
    @endif
</div>
