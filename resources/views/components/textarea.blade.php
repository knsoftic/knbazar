@props(['name', 'label' => null, 'value' => null, 'help' => null, 'required' => false, 'rows' => 4])
@php
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $dot));
@endphp
<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
        {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($dot)]) }} @required($required)>{{ old($dot, $value) }}</textarea>
    @error($dot)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
