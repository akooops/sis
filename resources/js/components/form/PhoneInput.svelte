<script>
    /**
     * PhoneInput — intl-tel-input (npm, no CDN), Svelte 5. Emits the full E.164
     * number via bound `value`. Initialised ONCE on mount (an $effect that read
     * `value` was recreating the widget on every keystroke → focus loss).
     */
    import { onMount, onDestroy } from 'svelte';
    import intlTelInput from 'intl-tel-input';
    import 'intl-tel-input/build/css/intlTelInput.css';

    let { value = $bindable(''), disabled = false, initialCountry = 'sa', invalid = false } = $props();

    let el;
    let iti;

    onMount(() => {
        iti = intlTelInput(el, {
            initialCountry,
            separateDialCode: true,
            countryOrder: ['sa', 'ae', 'kw', 'bh', 'om', 'qa'],
            loadUtils: () => import('intl-tel-input/utils'),
        });
        if (value) iti.setNumber(value);

        // Always emit E.164 (+<dialcode><national>). getNumber() gives that once
        // the utils script has loaded; before then we compose it from the selected
        // country's dial code + the typed digits so the value is never national-only.
        const update = () => {
            const full = iti.getNumber();
            if (full) {
                value = full;
                return;
            }
            const digits = (el.value || '').replace(/\D/g, '');
            const dial = iti.getSelectedCountryData()?.dialCode;
            value = digits ? (dial ? `+${dial}${digits}` : `+${digits}`) : '';
        };
        el.addEventListener('input', update);
        el.addEventListener('countrychange', update);
    });

    onDestroy(() => iti?.destroy());
</script>

<input bind:this={el} type="tel" class="kt-input w-full {invalid ? 'border-destructive' : ''}" {disabled} />

<style>
    :global(.iti) {
        width: 100%;
    }
</style>
