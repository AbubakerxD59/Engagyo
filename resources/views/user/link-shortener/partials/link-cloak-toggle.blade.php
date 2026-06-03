@php
    $inputId = $inputId ?? 'urlCloakToggle';
    $checked = $checked ?? true;
@endphp
<div class="form-group mb-0">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="{{ $inputId }}" name="url_cloak" value="1"
            {{ $checked ? 'checked' : '' }}>
        <label class="custom-control-label" for="{{ $inputId }}">Link cloak</label>
    </div>
    <small class="form-text text-muted">
        When enabled, visitors see a preview bridge page before going to your destination. When disabled, they are
        redirected immediately.
    </small>
</div>
