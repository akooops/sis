<script>
    import { bindPhoneInput } from '../phone';

    /**
     * The visible input shows the national format; `value` receives the E.164
     * string, which is what the API expects.
     */
    let { value = $bindable(''), placeholder = '', invalid = false } = $props();

    function phone(node) {
        const { destroy } = bindPhoneInput(node, (next) => (value = next));

        return { destroy };
    }
</script>

<input type="tel" class="input" class:is-invalid={invalid} {placeholder} aria-label={placeholder} use:phone />
