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
import Heading from '@/components/forms/elements/Heading.svelte';
import Paragraph from '@/components/forms/elements/Paragraph.svelte';
import Html from '@/components/forms/elements/Html.svelte';
import TextControl from '@/components/forms/elements/TextControl.svelte';
import TextareaControl from '@/components/forms/elements/TextareaControl.svelte';
import NumberControl from '@/components/forms/elements/NumberControl.svelte';
import EmailControl from '@/components/forms/elements/EmailControl.svelte';
import PhoneControl from '@/components/forms/elements/PhoneControl.svelte';
import DateControl from '@/components/forms/elements/DateControl.svelte';
import FileControl from '@/components/forms/elements/FileControl.svelte';
import SelectControl from '@/components/forms/elements/SelectControl.svelte';
import RadioControl from '@/components/forms/elements/RadioControl.svelte';
import CheckboxControl from '@/components/forms/elements/CheckboxControl.svelte';
import ConsentControl from '@/components/forms/elements/ConsentControl.svelte';
import ButtonControl from '@/components/forms/elements/ButtonControl.svelte';
import HiddenControl from '@/components/forms/elements/HiddenControl.svelte';

export const ELEMENTS = {
    heading: { component: Heading, input: false, labelled: false, group: false },
    paragraph: { component: Paragraph, input: false, labelled: false, group: false },
    html: { component: Html, input: false, labelled: false, group: false },

    text: { component: TextControl, input: true, labelled: true, group: false },
    textarea: { component: TextareaControl, input: true, labelled: true, group: false },
    number: { component: NumberControl, input: true, labelled: true, group: false },
    email: { component: EmailControl, input: true, labelled: true, group: false },
    phone: { component: PhoneControl, input: true, labelled: true, group: false },
    date: { component: DateControl, input: true, labelled: true, group: false },
    file: { component: FileControl, input: true, labelled: true, group: false },
    hidden: { component: HiddenControl, input: true, labelled: false, group: false, bare: true },

    select: { component: SelectControl, input: true, labelled: true, group: false },
    radio: { component: RadioControl, input: true, labelled: true, group: true },
    checkbox: { component: CheckboxControl, input: true, labelled: true, group: true },
    consent: { component: ConsentControl, input: true, labelled: false, group: false },

    button: { component: ButtonControl, input: false, labelled: false, group: false },
};

/** The empty value a type starts from, so nothing binds to undefined. */
export function emptyValue(type, field = null) {
    if (type === 'checkbox') return [];
    if (type === 'consent') return false;
    if (type === 'select' && field?.settings?.is_multiple) return [];
    if (type === 'file' && field?.settings?.is_multiple) return [];

    return '';
}
