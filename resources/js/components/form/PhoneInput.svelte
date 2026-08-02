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
            // Strip the national trunk "0" (e.g. Algeria 0665… → +213665…).
            const digits = (el.value || '').replace(/\D/g, '').replace(/^0+/, '');
            const dial = iti.getSelectedCountryData()?.dialCode;
            value = digits ? (dial ? `+${dial}${digits}` : `+${digits}`) : '';
        };
        el.addEventListener('input', update);
        el.addEventListener('countrychange', update);
    });

    onDestroy(() => iti?.destroy());
</script>

<!--
    The wrapper is load-bearing, not layout.

    intl-tel-input builds its own `.iti` container and MOVES this input inside it
    (insertBefore + append), so the flag and dial code live in DOM that Svelte
    never created and will not remove. destroy() unwinds that by reading
    `telInput.parentNode` — but every step of it is optional-chained, so once
    Svelte has detached the input there is no parent to find and the call
    silently does nothing, stranding the container. That is the "+966" that hung
    over the Email field after switching a contact detail away from a phone.

    Wrapping the input in an element Svelte owns makes the whole subtree go at
    once, so the leak cannot depend on teardown order.
-->
<div class="w-full">
    <input bind:this={el} type="tel" class="kt-input w-full {invalid ? 'border-destructive' : ''}" {disabled} />
</div>

<style>
    :global(.iti) {
        width: 100%;
    }
</style>
