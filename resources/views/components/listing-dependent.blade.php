@props(['field', 'value', 'errorsFor'])

@php([$name, $label, $parent, $endpoint, $default] = $field)
<x-listing-field :name="$name" :label="$label">
    <select id="{{ $name }}" name="{{ $name }}" data-parent="{{ $parent }}"
        data-options="{{ route('listing-options.'.$endpoint, '__parent__') }}" data-selected="{{ $value($name, $default) }}"
        data-placeholder="Choose {{ in_array($name, ['area_id']) ? 'an' : 'a' }} {{ strtolower($label) }}"
        {{ $errorsFor($name) }} required disabled>
        <option value="">Choose the parent first</option>
    </select>
    <div class="listing-option-status">
        <span class="listing-meta" role="status" data-option-status></span>
        <button type="button" class="market-nav-link" data-option-retry hidden>Retry</button>
    </div>
</x-listing-field>
