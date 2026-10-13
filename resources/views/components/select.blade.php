@props(['name', 'label' => null, 'options' => [], 'value' => null, 'help' => null, 'required' => false, 'placeholder' => null])
@php
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $dot));
    $selected = (string) old($dot, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($dot)]) }} @required($required)>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($dot)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
