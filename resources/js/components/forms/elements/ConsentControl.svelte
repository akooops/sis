<script>
    /**
     * A single yes/no tick — terms, privacy, a mailing-list opt-in.
     *
     * Its label sits BESIDE the box rather than above it, which is why
     * Element.svelte marks this type unlabelled and the label is rendered here:
     * a consent statement read out of position stops meaning anything.
     */
    import { translate } from '@/lib/forms/i18n';

    let {
        field,
        value = false,
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

    const label = $derived(translate(field?.label, locale, fallbackLocale));
</script>

<div class="sisf-choice">
    <input
        {id}
        type="checkbox"
        class="sisf-checkbox"
        checked={!!value}
        {disabled}
        required={!!field?.is_required}
        aria-invalid={invalid || undefined}
        aria-describedby={describedBy}
        onchange={(e) => onchange?.(field, e.currentTarget.checked)}
        onfocus={() => onfocus?.(field)}
        onblur={() => onblur?.(field)}
    />
    <label class="sisf-choice-label" for={id}>
        {label}{#if field?.is_required}<span class="sisf-required" aria-hidden="true">*</span>{/if}
    </label>
</div>
