@props(['name', 'label', 'required' => false, 'hint' => null])

<div {{ $attributes->class(['court-field']) }}>
    <label for="{{ $name }}">{{ $label }} @if($required)<span class="text-violet-500" aria-hidden="true">*</span>@endif</label>
    {{ $slot }}
    @if($hint)<p class="field-hint">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
</div>
