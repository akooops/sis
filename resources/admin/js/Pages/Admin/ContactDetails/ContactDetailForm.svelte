<script>
    /**
     * Contact detail create/edit.
     *
     * The form is DRIVEN BY THE TYPE, and the type catalogue is config. The value
     * input follows the SHAPE that registry declares for `value` ('phone' |
     * 'email' | 'url', or null for a type that carries no scalar at all); the
     * extra fields follow `extras`, which is the SERVER'S own branch shipped down
     * rather than re-derived here. Nothing below switches on a type code, so a new
     * type in config/contacts.php renders itself — and cannot render a field the
     * server would silently null on save.
     *
     * The catalogue and the platform list come down as props — the page this
     * opens inside has already read `contact-types`, and both arrays come out of
     * that one response (same as IntegrationForm taking its drivers from the
     * drawer). They may still be empty on the first render if the form is opened
     * before that request lands, which is why every derivation below tolerates it.
     *
     * Create asks for the DEFAULT language's copy only — one column, no tabs —
     * because there is nothing to translate until the record exists. Edit splits
     * into [Details | Translations], matching every other content module.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import PhoneInput from '@/components/form/PhoneInput.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { detail = null, types = [], platforms = [], onsaved, oncancel } = $props();

    const editing = $derived(!!detail);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm(
        detail
            ? {
                  type: detail.type ?? '',
                  name: detail.name ?? '',
                  title: { ...(detail.title ?? {}) },
                  value: detail.value ?? '',
                  address: { ...(detail.address ?? {}) },
                  platform: detail.platform ?? null,
                  map_url: detail.map_url ?? '',
                  latitude: detail.latitude ?? '',
                  longitude: detail.longitude ?? '',
              }
            : {
                  type: '',
                  name: '',
                  title: '',
                  value: '',
                  address: '',
                  platform: null,
                  map_url: '',
                  latitude: '',
                  longitude: '',
              },
    );

    $effect(() => {
        api.get(route('api.v1.admin.languages.index'), { filter: { is_enabled: 1 }, per_page: 100 })
            .then((d) => {
                // Default language first — it is the one whose copy is required.
                languages = (d?.data ?? []).sort((a, b) => Number(b.is_default) - Number(a.is_default) || a.name.localeCompare(b.name));
                if (!activeLocale && languages.length) activeLocale = languages[0].code;
                // Every enabled locale needs a key before a field binds to it —
                // binding to an undefined key throws props_invalid_value.
                if (editing) {
                    for (const l of languages) {
                        for (const f of ['title', 'address']) {
                            if (form.data[f][l.code] === undefined) form.data[f][l.code] = '';
                        }
                    }
                }
            })
            .catch(() => {});
    });

    const typeMap = $derived(new Map(types.map((t) => [t.code, t])));
    const typeOptions = $derived(types.map((t) => ({ value: t.code, label: t.name })));
    const platformMap = $derived(new Map(platforms.map((p) => [p.code, p])));
    const platformOptions = $derived(platforms.map((p) => ({ value: p.code, label: p.name })));

    const selectedType = $derived(typeMap.get(form.data.type) ?? null);
    // undefined while the registry loads; null for a type that carries no scalar.
    const valueKind = $derived(selectedType ? (selectedType.value ?? null) : undefined);
    // Which extra columns the type owns, straight from the server's own
    // predicate — NOT inferred from the shape. A second url-shaped type would
    // otherwise draw a Platform field that the controller nulls on save.
    const extras = $derived(selectedType?.extras ?? null);
    const isSocial = $derived(extras === 'social');
    const isAddress = $derived(extras === 'address');

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    const VALUE_LABELS = { phone: 'Phone number', email: 'Email address', url: 'Link' };
    const VALUE_HINTS = {
        phone: 'Pick the country, then type the number — it is stored in international format.',
        email: 'The address people write to.',
        url: 'The full link, including https://',
    };

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'address'].some((f) => !!form.errors[`${f}.${code}`]);
    }

    function pickType(next) {
        const before = valueKind;
        form.data.type = next;
        const after = typeMap.has(next) ? (typeMap.get(next).value ?? null) : undefined;

        // A leftover email must not be handed to the phone widget: the two are not
        // the same kind of string, and it would parse the letters into a number.
        if (before !== after) form.data.value = '';
    }

    function payload(data) {
        const out = { ...data };
        const entry = typeMap.get(out.type);

        // Blank coordinates are "not set", not zero.
        if (out.latitude === '') out.latitude = null;
        if (out.longitude === '') out.longitude = null;

        // Registry not in yet — the type picker is empty too, so there is nothing
        // to strip against; let the server judge.
        if (!entry) return out;

        const kind = entry.value ?? null;
        const group = entry.extras ?? null;

        // Send only what the selected type uses — stripped by the same two keys
        // the server blanks by, so what the form shows is what the row keeps.
        // Posting a stale phone under an address is how a type change leaves a
        // ghost value behind in the validator's messages.
        if (!kind) delete out.value;
        if (group !== 'social') delete out.platform;
        if (group !== 'address') {
            delete out.address;
            delete out.map_url;
            delete out.latitude;
            delete out.longitude;
        }

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.contact-details.update', detail.id)
            : route('api.v1.admin.contact-details.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            } else if (languages.some((l) => localeHasError(l.code))) {
                // The failing field may be behind a tab the user can't see.
                activeTab = 'translations';
                activeLocale = languages.find((l) => localeHasError(l.code))?.code ?? activeLocale;
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    {#if editing}
        <Tabs
            tabs={[
                { id: 'details', label: 'Details', icon: 'ki-filled ki-document' },
                { id: 'translations', label: 'Translations', icon: 'ki-filled ki-flag' },
            ]}
            bind:active={activeTab}
        />
    {/if}

    {#if !editing || activeTab === 'details'}
        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field
                    label="Type"
                    error={form.errors.type}
                    required
                    hint={editing
                        ? 'Changing the type clears the fields the new type does not use.'
                        : 'What kind of contact this is — it decides the rest of this form.'}
                >
                    <Select
                        options={typeOptions}
                        value={form.data.type}
                        clearable={false}
                        invalid={!!form.errors.type}
                        placeholder="Choose a type"
                        onchange={pickType}
                    />
                </Field>

                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
            </div>

            {#if valueKind}
                <Field
                    label={VALUE_LABELS[valueKind] ?? 'Value'}
                    error={form.errors.value}
                    required
                    hint={VALUE_HINTS[valueKind]}
                >
                    {#if valueKind === 'phone'}
                        <!-- Emits E.164, which is exactly the shape the server stores. -->
                        <PhoneInput bind:value={form.data.value} invalid={!!form.errors.value} />
                    {:else if valueKind === 'email'}
                        <Input type="email" bind:value={form.data.value} invalid={!!form.errors.value} />
                    {:else}
                        <Input bind:value={form.data.value} invalid={!!form.errors.value} placeholder="https://…" />
                    {/if}
                </Field>
            {/if}

            {#if isSocial}
                <!-- No icon field: the network already decides its own mark, and the
                     registry entry carries it. One less question with one right answer. -->
                <Field
                    label="Platform"
                    error={form.errors.platform}
                    required
                    hint="Which network this profile is on — its icon comes with it."
                >
                    <div class="flex items-center gap-2">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground">
                            {#if platformMap.get(form.data.platform)}
                                <i class="ki-filled {platformMap.get(form.data.platform).icon} text-base"></i>
                            {:else}
                                <span class="text-xs">—</span>
                            {/if}
                        </span>
                        <div class="min-w-0 grow">
                            <Select
                                options={platformOptions}
                                bind:value={form.data.platform}
                                invalid={!!form.errors.platform}
                                placeholder="Choose a platform"
                            >
                                {#snippet option(item)}
                                    <span class="flex min-w-0 items-center gap-2">
                                        <i class="ki-filled {platformMap.get(item.value)?.icon ?? 'ki-social-media'} text-base"></i>
                                        <span class="truncate">{item.label}</span>
                                    </span>
                                {/snippet}
                            </Select>
                        </div>
                    </div>
                </Field>
            {/if}

            {#if isAddress}
                <Field label="Map link" error={form.errors.map_url} hint="A Google Maps link people can open for directions.">
                    <Input bind:value={form.data.map_url} invalid={!!form.errors.map_url} placeholder="https://…" />
                </Field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Field
                        label="Latitude"
                        error={form.errors.latitude}
                        hint="Optional — with the longitude, the public site can draw its own map instead of only linking out."
                    >
                        <Input type="number" step="any" bind:value={form.data.latitude} invalid={!!form.errors.latitude} />
                    </Field>
                    <Field label="Longitude" error={form.errors.longitude}>
                        <Input type="number" step="any" bind:value={form.data.longitude} invalid={!!form.errors.longitude} />
                    </Field>
                </div>
            {/if}
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's copy, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The public label, e.g. Admissions office. Translatable once created.">
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>

            {#if isAddress}
                <Field label="Address" error={form.errors.address} required hint="The postal address as it should be read.">
                    <textarea
                        class="kt-input min-h-[90px]"
                        class:border-destructive={!!form.errors.address}
                        bind:value={form.data.address}
                    ></textarea>
                </Field>
            {/if}
        </div>
    {:else if activeTab === 'translations'}
        <div class="flex flex-col gap-5">
            <div class="kt-tabs kt-tabs-line overflow-x-auto" role="tablist">
                {#each languages as language (language.code)}
                    <button
                        type="button"
                        role="tab"
                        data-kt-tab-toggle
                        class="kt-tab-toggle {activeLocale === language.code ? 'active' : ''}"
                        aria-selected={activeLocale === language.code}
                        onclick={() => (activeLocale = language.code)}
                    >
                        {language.name}
                        {#if localeHasError(language.code)}
                            <span class="ms-1.5 inline-block size-1.5 rounded-full bg-destructive"></span>
                        {/if}
                    </button>
                {/each}
            </div>

            {#if activeLocale}
                <Field label="Title" error={form.errors[`title.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <Input
                        bind:value={form.data.title[activeLocale]}
                        invalid={!!form.errors[`title.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                    />
                </Field>

                {#if isAddress}
                    <Field label="Address" error={form.errors[`address.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                        <textarea
                            class="kt-input min-h-[90px]"
                            class:border-destructive={!!form.errors[`address.${activeLocale}`]}
                            dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                            bind:value={form.data.address[activeLocale]}
                        ></textarea>
                    </Field>
                {/if}
            {/if}
        </div>
    {/if}

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
