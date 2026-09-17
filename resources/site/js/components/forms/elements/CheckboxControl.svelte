<script>
    /**
     * Any number of choices. The value is always an array, including when it is
     * empty — the server's rules expect one, and a bare '' would fail `array`.
     */
    import { translate } from '@site/lib/forms/i18n';

    let {
        field,
        value = [],
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        id = undefined,
        invalid = false,
        describedBy = undefined,
        onchange = null,
        onfocus = null,
        onblur = null,
    } = $props();

    const options = $derived(field?.options ?? []);
    const inline = $derived(!!field?.settings?.inline);
    const selected = $derived(Array.isArray(value) ? value : []);

    function toggle(optionValue, checked) {
        onchange?.(
            field,
            checked ? [...selected, optionValue] : selected.filter((v) => v !== optionValue),
        );
    }
</script>

<!--
    role="group" is what makes the two ARIA attributes below mean anything. On a
    bare <div> they are inert: a screen-reader user tabbing onto the ticks was
    never told why the field had been rejected, because aria-describedby only
    resolves against something with a role. The individual inputs stay plain
    checkboxes; the group carries the label and the error.
-->
<div
    class="sisf-choices"
    class:sisf-choices--inline={inline}
    role="group"
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
>
    {#each options as option, index (option.value)}
        <div class="sisf-choice">
            <input
                id={`${id}-${index}`}
                type="checkbox"
                class="sisf-checkbox"
                value={option.value}
                checked={selected.includes(option.value)}
                {disabled}
                onchange={(e) => toggle(option.value, e.currentTarget.checked)}
                onfocus={() => onfocus?.(field)}
                onblur={() => onblur?.(field)}
            />
            <label class="sisf-choice-label" for={`${id}-${index}`}>
                {translate(option.label, locale, fallbackLocale) || option.value}
            </label>
        </div>
    {/each}
</div>
