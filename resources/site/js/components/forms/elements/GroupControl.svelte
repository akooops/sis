<script>
    /**
     * A repeatable group — the only control that renders other elements.
     *
     * Its answer is a LIST OF OBJECTS keyed by child key, matching what
     * GroupType::store() writes:
     *
     *     [{institution: 'KFUPM', degree: 'BSc'}, …]
     *
     * Children go back through Element.svelte, so a child keeps the container
     * contract, its label, its own error and its own type's control — a text
     * field inside a repeat is the same component as a text field on the page,
     * and nothing here knows what markup any given type produces.
     *
     * TWO THINGS THIS OWNS THAT NOTHING ELSE CAN. The answer path
     * (`education.0.institution`) is passed down as `keyPath`, because only this
     * component knows which row a child is in — and that path is both what the
     * server keys its error by and what the renderer scrolls to. And the child's
     * onchange is intercepted: a child calling the renderer's handler directly
     * would write `answers.institution` as a phantom top-level answer instead of
     * updating the row it belongs to.
     */
    import Element from '../Element.svelte';
    import { blankInstance, maxInstances, minInstances } from '@site/lib/forms/elements';
    import { translate } from '@site/lib/forms/i18n';

    let {
        field,
        value = [],
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        labels = {},
        errors = {},
        onchange = null,
        onfocus = null,
        onblur = null,
        onupload = null,
    } = $props();

    const children = $derived(field?.children ?? []);
    const min = $derived(minInstances(field));
    const max = $derived(maxInstances(field));

    // Always an array: a group whose answer arrived as '' (an older submission,
    // a hand-rolled post) must still render rather than throw on .map.
    const instances = $derived(Array.isArray(value) ? value : []);

    const canAdd = $derived(!disabled && instances.length < max);
    const canRemove = $derived(!disabled && instances.length > min);

    const groupLabel = $derived(translate(field?.label, locale, fallbackLocale) ?? '');

    /** ":label :number" → "Education 2". Laravel-style, like the step counter. */
    const itemLabel = (index) =>
        (labels.groupItem ?? ':label :number')
            .replace(':label', groupLabel)
            .replace(':number', String(index + 1));

    const addLabel = $derived((labels.groupAdd ?? 'Add :label').replace(':label', groupLabel).trim());

    function emit(next) {
        onchange?.(field, next);
    }

    function setChild(index, childKey, childValue) {
        emit(instances.map((row, i) => (i === index ? { ...row, [childKey]: childValue } : row)));
    }

    function add() {
        if (!canAdd) return;

        emit([...instances, blankInstance(field)]);
    }

    /*
     * Removing SPLICES, which renumbers every row after it — and the server's
     * error keys are positional, so a message aimed at row 2 would now point at
     * whatever moved into that slot. Errors are dropped for this group on
     * removal for exactly that reason; the next submit re-reports whatever is
     * still wrong against the new numbering.
     */
    function remove(index) {
        if (!canRemove) return;

        emit(instances.filter((_, i) => i !== index));
    }
</script>

<div class="sisf-group">
    {#each instances as row, index (index)}
        <div class="sisf-group-item" data-sisf-instance={index}>
            <!-- Absolutely positioned into the card's corner, so it never takes a
                 line of its own. It is an ICON with an accessible name rather
                 than the word "Remove": five entries each carrying the word twice
                 over is what made the header-row version noisy. -->
            {#if canRemove}
                <button
                    type="button"
                    class="sisf-group-remove"
                    onclick={() => remove(index)}
                    aria-label={`${labels.groupRemove ?? 'Remove'} — ${itemLabel(index)}`}
                    title={labels.groupRemove ?? 'Remove'}
                >
                    <i class="uil uil-times" aria-hidden="true"></i>
                </button>
            {/if}

            <div class="sisf-group-item-fields">
                {#each children as child (child.id)}
                    {@const path = `${field.key}.${index}.${child.key}`}
                    <Element
                        field={child}
                        value={row?.[child.key]}
                        error={errors[path] ?? null}
                        keyPath={path}
                        instance={index}
                        {locale}
                        {fallbackLocale}
                        {disabled}
                        {labels}
                        {errors}
                        onchange={(childField, childValue) => setChild(index, childField.key, childValue)}
                        {onfocus}
                        {onblur}
                        {onupload}
                    />
                {/each}
            </div>
        </div>
    {/each}

    {#if canAdd}
        <button type="button" class="sisf-group-add" onclick={add}>
            <i class="uil uil-plus" aria-hidden="true"></i>
            {addLabel}
        </button>
    {/if}
</div>
