<script>
    /**
     * Candidate edit — contact details only, and EDIT ONLY.
     *
     * There is no create: ApplicationProjector is the only thing that makes a
     * person, and there is no `candidates.store` permission. A candidate exists
     * because somebody applied.
     *
     * THE WARNING IS THE MOST IMPORTANT THING ON THIS FORM, and it is not
     * decoration. ApplicationProjector::candidate() calls `$candidate->fill()`
     * over exactly these columns on EVERY re-projection, so a correction here is
     * reverted the next time that person applies. And because the projector finds
     * people with `scopeIdentifiedBy(email, phone)`, changing the email moves the
     * dedup key: a later application from the old address creates a SECOND
     * candidate rather than updating this one.
     *
     * Email stays editable anyway — a typo'd address is exactly the thing worth
     * being able to fix, and the alternative is a field an admin can see is wrong
     * and cannot touch. The consequence is stated rather than prevented.
     *
     * The profile (education, experience, languages, skills), the CV, the summary
     * and the embedding are all absent: those are the projector's and the queues'.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import PhoneInput from '@/components/form/PhoneInput.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';

    let { candidate = null, ready = true, onsaved, oncancel } = $props();

    const form = useForm({
        first_name: candidate?.first_name ?? '',
        last_name: candidate?.last_name ?? '',
        email: candidate?.email ?? '',
        phone: candidate?.phone ?? '',
        address: candidate?.address ?? '',
        country_id: candidate?.country_id ?? null,
    });

    // The address the record was found by, so the warning can name it if it changes.
    const originalEmail = candidate?.email ?? '';
    const emailChanged = $derived(form.data.email !== originalEmail);

    async function submit(event) {
        event.preventDefault();

        if (!candidate?.id) return;

        try {
            const res = await form.put(route('api.v1.admin.candidates.update', candidate.id));
            if (res) {
                toast.success('Updated successfully.');
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <Alert variant="warning">
        <strong>These are the answers this person gave on their last application.</strong>
        If they apply again, the form's answers replace whatever is typed here. Changing the email
        also changes how they are recognised — a later application from the old address would
        create a second candidate.
    </Alert>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field label="First name" error={form.errors.first_name} required>
            <Input bind:value={form.data.first_name} invalid={!!form.errors.first_name} />
        </Field>
        <Field label="Last name" error={form.errors.last_name} required>
            <Input bind:value={form.data.last_name} invalid={!!form.errors.last_name} />
        </Field>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Field
            label="Email"
            error={form.errors.email}
            required
            hint={emailChanged
                ? 'Changed. This is the identifier the projector matches on — an application from the old address will create a second candidate.'
                : 'One of the two identifiers a repeat application is matched on.'}
        >
            <Input type="email" bind:value={form.data.email} invalid={!!form.errors.email} />
        </Field>
        <!-- Normalised to E164 server-side before the unique check runs, so
             "+966 50 123 4599" collides with an existing row rather than becoming
             a second person. -->
        <Field label="Phone" error={form.errors.phone} hint="Stored in international format.">
            <PhoneInput bind:value={form.data.phone} invalid={!!form.errors.phone} />
        </Field>
    </div>

    <Field label="Address" error={form.errors.address}>
        <Input bind:value={form.data.address} invalid={!!form.errors.address} />
    </Field>

    <Field label="Nationality" error={form.errors.country_id} hint="Used by the scorer to judge work authorisation, not as a preference.">
        <Select
            resource="api.v1.admin.countries.index"
            bind:value={form.data.country_id}
            labelKey="name"
            placeholder="Search countries…"
            initialOptions={candidate?.country
                ? [{ value: candidate.country.id, label: candidate.country.name }]
                : candidate?.country_id && candidate?.country_name
                  ? [{ value: candidate.country_id, label: candidate.country_name }]
                  : []}
        />
    </Field>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
