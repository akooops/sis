/**
 * The element registry, client side — mirrors config('forms.field_types').
 *
 * `component` is the control itself. Element.svelte owns the container around
 * it, so a control never renders its own wrapper, label or error: that is what
 * makes the CSS contract (`.my-class input`) hold for every type at once.
 *
 *  - input     : holds an answer. Headings and buttons do not.
 *  - labelled  : gets a <label for>. A group uses <legend> instead, because a
 *                label pointing at a group of inputs means nothing to a screen
 *                reader; display elements have nothing to label at all.
 *  - group     : renders as <fieldset>.
 *  - bare      : no container at all — the control and nothing else. Only for an
 *                element the visitor never sees, where an empty `.sisf-el` would
 *                still take a slot of the page's gap.
 */
import Heading from '@site/components/forms/elements/Heading.svelte';
import Paragraph from '@site/components/forms/elements/Paragraph.svelte';
import Html from '@site/components/forms/elements/Html.svelte';
import Separator from '@site/components/forms/elements/Separator.svelte';
import TextControl from '@site/components/forms/elements/TextControl.svelte';
import TextareaControl from '@site/components/forms/elements/TextareaControl.svelte';
import NumberControl from '@site/components/forms/elements/NumberControl.svelte';
import EmailControl from '@site/components/forms/elements/EmailControl.svelte';
import PhoneControl from '@site/components/forms/elements/PhoneControl.svelte';
import DateControl from '@site/components/forms/elements/DateControl.svelte';
import FileControl from '@site/components/forms/elements/FileControl.svelte';
import SelectControl from '@site/components/forms/elements/SelectControl.svelte';
import RadioControl from '@site/components/forms/elements/RadioControl.svelte';
import CheckboxControl from '@site/components/forms/elements/CheckboxControl.svelte';
import ConsentControl from '@site/components/forms/elements/ConsentControl.svelte';
import ButtonControl from '@site/components/forms/elements/ButtonControl.svelte';
import HiddenControl from '@site/components/forms/elements/HiddenControl.svelte';
import GroupControl from '@site/components/forms/elements/GroupControl.svelte';
import TagsControl from '@site/components/forms/elements/TagsControl.svelte';

export const ELEMENTS = {
    heading: { component: Heading, input: false, labelled: false, group: false },
    paragraph: { component: Paragraph, input: false, labelled: false, group: false },
    html: { component: Html, input: false, labelled: false, group: false },
    // `bare`: a rule IS the element — a label and an error wrapper around it
    // would add a gap above a line whose whole job is to be the gap.
    separator: { component: Separator, input: false, labelled: false, group: false, bare: true },

    text: { component: TextControl, input: true, labelled: true, group: false },
    textarea: { component: TextareaControl, input: true, labelled: true, group: false },
    number: { component: NumberControl, input: true, labelled: true, group: false },
    email: { component: EmailControl, input: true, labelled: true, group: false },
    phone: { component: PhoneControl, input: true, labelled: true, group: false },
    date: { component: DateControl, input: true, labelled: true, group: false },
    file: { component: FileControl, input: true, labelled: true, group: false },
    hidden: { component: HiddenControl, input: true, labelled: false, group: false, bare: true },
    tags: { component: TagsControl, input: true, labelled: true, group: false },

    select: { component: SelectControl, input: true, labelled: true, group: false },
    radio: { component: RadioControl, input: true, labelled: true, group: true },
    checkbox: { component: CheckboxControl, input: true, labelled: true, group: true },
    consent: { component: ConsentControl, input: true, labelled: false, group: false },

    // The one element whose answer is a list of OBJECTS, and the only one that
    // renders other elements. <fieldset>, like the other multi-control types.
    group: { component: GroupControl, input: true, labelled: true, group: true, repeater: true },

    button: { component: ButtonControl, input: false, labelled: false, group: false },
};

/** Mirrors GroupType::DEFAULT_MAX and GroupType::MAX_INSTANCES. */
export const GROUP_DEFAULT_MAX = 10;
export const GROUP_MAX_INSTANCES = 25;

/** The empty value a type starts from, so nothing binds to undefined. */
export function emptyValue(type, field = null) {
    if (type === 'checkbox') return [];
    if (type === 'tags') return [];
    if (type === 'consent') return false;
    if (type === 'select' && field?.settings?.is_multiple) return [];
    if (type === 'file' && field?.settings?.is_multiple) return [];

    // A group opens with its minimum already on screen. Starting at zero rows
    // would show a required "Education" section as a lone Add button with no
    // hint that anything is expected in it.
    if (type === 'group') {
        return Array.from({ length: minInstances(field) }, () => blankInstance(field));
    }

    return '';
}

/**
 * One empty repeat row: every child key present, each at its own empty value.
 *
 * Every key has to exist even when blank — a control binding to undefined throws
 * props_invalid_value, exactly as it would for a top-level field.
 */
export function blankInstance(field) {
    const out = {};

    for (const child of field?.children ?? []) {
        if (!ELEMENTS[child.type]?.input) continue;

        out[child.key] = child.value_resolved ?? emptyValue(child.type, child);
    }

    return out;
}

/** Rows shown up front. A required group always shows at least one. */
export function minInstances(field) {
    const min = Number(field?.settings?.min_instances ?? 0);

    return Math.max(Number.isFinite(min) ? min : 0, field?.is_required ? 1 : 0);
}

/** Clamped the same way GroupType::maxInstances() clamps it server-side. */
export function maxInstances(field) {
    const max = Number(field?.settings?.max_instances ?? GROUP_DEFAULT_MAX);

    return Math.max(1, Math.min(Number.isFinite(max) ? max : GROUP_DEFAULT_MAX, GROUP_MAX_INSTANCES));
}
