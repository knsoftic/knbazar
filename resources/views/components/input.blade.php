@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'prepend' => null, 'append' => null])
@php
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $dot));
    $current = $type === 'password' ? null : old($dot, $value);
@endphp
<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    @if ($prepend || $append)<div class="input-group has-validation">@endif
        @if ($prepend)<span class="input-group-text">{{ $prepend }}</span>@endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $current }}"
            {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($dot)]) }} @required($required)>
        @if ($append)<span class="input-group-text">{{ $append }}</span>@endif
        @error($dot)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($prepend || $append)</div>@endif
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
