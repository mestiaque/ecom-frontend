{{-- Font Awesome icon input with live preview. $name (dot key under theme.), $label, $value, $required --}}
@php($field = 'theme['.str_replace('.', '][', $name).']')
@php($old = old('theme.'.$name, $value))
<div class="mb-3">
    <label class="form-label small fw-semibold mb-1">{{ $label }}</label>
    <div class="input-group input-group-sm">
        <span class="input-group-text" style="width:38px;justify-content:center"><i class="{{ $old }}" data-icon-preview></i></span>
        <input type="text" name="{{ $field }}" value="{{ $old }}" class="form-control font-monospace @error('theme.'.$name) is-invalid @enderror" list="efIconList" placeholder="{{ empty($required) ? 'No icon' : 'fas fa-…' }}" data-icon-input @if(!empty($preview)) data-preview="{{ $preview }}" @endif>
    </div>
    @error('theme.'.$name)<div class="text-danger small">{{ $message }}</div>@enderror
</div>
