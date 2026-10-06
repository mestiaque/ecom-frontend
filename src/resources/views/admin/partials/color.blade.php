{{-- Colour picker. $name (dot key under theme.), $label, $value, $help --}}
@php($field = 'theme['.str_replace('.', '][', $name).']')
@php($old = old('theme.'.$name, $value))
<div class="mb-3">
    <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
    <div class="input-group input-group-sm ef-color">
        <input type="color" class="form-control form-control-color" value="{{ $old }}" data-color-for="{{ $name }}" aria-label="{{ $label }}">
        <input type="text" name="{{ $field }}" value="{{ $old }}" class="form-control font-monospace @error('theme.'.$name) is-invalid @enderror" maxlength="7" data-color-text="{{ $name }}" @if(!empty($preview)) data-preview="{{ $preview }}" @endif>
    </div>
    @if(!empty($help))<small class="text-muted">{{ $help }}</small>@endif
    @error('theme.'.$name)<div class="text-danger small">{{ $message }}</div>@enderror
</div>
