@props(['name', 'label', 'checked' => false, 'help' => null])
@php
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $dot));
    $isChecked = (bool) old($dot, $checked);
    if (old('_token') !== null && old($dot) === null) {
        $isChecked = false; // unchecked boxes are absent from the previous submission
    }
@endphp
<div class="mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input @error($dot) is-invalid @enderror" type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="1" @checked($isChecked) {{ $attributes->except('id') }}>
        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
        @error($dot)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
