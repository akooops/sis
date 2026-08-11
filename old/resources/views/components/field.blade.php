{{--
    Form row: optional leading icon, the control itself, and the validation
    message for `name`. The control comes in as the slot so this works for
    inputs, selects and textareas alike.
--}}
@props(['name', 'icon' => null])

<div>
    <div @class(['input-group' => $icon])>
        @if ($icon)
            <span class="input-addon"><i class="uil {{ $icon }}" aria-hidden="true"></i></span>
        @endif

        {{ $slot }}
    </div>

    @error($name)
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>
