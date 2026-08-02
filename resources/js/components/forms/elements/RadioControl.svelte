<script>
    /**
     * Exactly one choice. Rendered inside the fieldset Element.svelte provides,
     * so there is no legend here — that would nest two.
     *
     * Every input shares one `name`, which is what makes them one group to the
     * browser and to assistive tech; the id is per option so each label points
     * at its own input.
     */
    import { translate } from '@/lib/forms/i18n';

    let {
        field,
        value = '',
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
</script>

<div
    class="sisf-choices"
    class:sisf-choices--inline={inline}
    role="radiogroup"
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
>
    {#each options as option, index (option.value)}
        <div class="sisf-choice">
            <input
                id={`${id}-${index}`}
                type="radio"
                class="sisf-radio"
                name={id}
                value={option.value}
                checked={value === option.value}
                {disabled}
                required={!!field?.is_required}
                onchange={() => onchange?.(field, option.value)}
                onfocus={() => onfocus?.(field)}
                onblur={() => onblur?.(field)}
            />
            <label class="sisf-choice-label" for={`${id}-${index}`}>
                {translate(option.label, locale, fallbackLocale) || option.value}
            </label>
        </div>
    {/each}
</div>
