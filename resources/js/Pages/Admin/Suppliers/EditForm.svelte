<script>
    import { createEventDispatcher } from 'svelte';
    import PhoneInput from '../../Shared/Utils/Forms/PhoneInput.svelte';
    import ImageInput from '../../Shared/Utils/Forms/ImageInput.svelte';
    import CodeInput from '../../Shared/Utils/Forms/CodeInput.svelte';

    const dispatch = createEventDispatcher();

    export let supplier = null;

    let form = {
        name: '',
        code: '',
        email: '',
        phone: '',
        logo: null,
        address: '',
        city: '',
        state: '',
        zip: '',
        country: '',
        website: '',
        contact_person: '',
        contact_person_email: '',
        contact_person_phone: '',
        contact_person_position: '',
        contact_person_department: '',
        contact_person_department_email: '',
        contact_person_department_phone: '',
        contact_person_department_position: '',
    };

    let errors = {};
    let loading = false;

    $: if (supplier) {
        form = {
            name: supplier.name || '',
            code: supplier.code || '',
            email: supplier.email || '',
            phone: supplier.phone || '',
            logo: null,
            address: supplier.address || '',
            city: supplier.city || '',
            state: supplier.state || '',
            zip: supplier.zip || '',
            country: supplier.country || '',
            website: supplier.website || '',
            contact_person: supplier.contact_person || '',
            contact_person_email: supplier.contact_person_email || '',
            contact_person_phone: supplier.contact_person_phone || '',
            contact_person_position: supplier.contact_person_position || '',
            contact_person_department: supplier.contact_person_department || '',
            contact_person_department_email: supplier.contact_person_department_email || '',
            contact_person_department_phone: supplier.contact_person_department_phone || '',
            contact_person_department_position: supplier.contact_person_department_position || '',
        };
    }

    function handleImageChange(event) {
        form.logo = event.detail.file;
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            let formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.suppliers.update', supplier.id), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                toast('Supplier updated successfully', 'success');
                dispatch('updated');
            } else {
                if (data.errors) {
                    errors = data.errors;
                } else {
                    console.error('Error updating supplier:', data.message || 'Unknown error');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (supplier) {
            form = {
                name: supplier.name || '',
                code: supplier.code || '',
                email: supplier.email || '',
                phone: supplier.phone || '',
                logo: null,
                address: supplier.address || '',
                city: supplier.city || '',
                state: supplier.state || '',
                zip: supplier.zip || '',
                country: supplier.country || '',
                website: supplier.website || '',
                contact_person: supplier.contact_person || '',
                contact_person_email: supplier.contact_person_email || '',
                contact_person_phone: supplier.contact_person_phone || '',
                contact_person_position: supplier.contact_person_position || '',
                contact_person_department: supplier.contact_person_department || '',
                contact_person_department_email: supplier.contact_person_department_email || '',
                contact_person_department_phone: supplier.contact_person_department_phone || '',
                contact_person_department_position: supplier.contact_person_department_position || '',
            };
        }
        errors = {};
        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        <!-- Supplier Card -->
        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Supplier</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <ImageInput
                        bind:value={form.logo}
                        defaultImage={supplier?.logo_url}
                        disabled={loading}
                        on:change={handleImageChange}
                    />

                    {#if errors.logo}
                        <p class="text-sm text-destructive">{errors.logo}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="name">
                        Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        class="kt-input {errors.name ? 'kt-input-error' : ''}"
                        placeholder="Enter supplier name"
                        bind:value={form.name}
                        disabled={loading}
                    />
                    {#if errors.name}
                        <p class="text-sm text-destructive">{errors.name}</p>
                    {/if}
                </div>

                <CodeInput
                    bind:value={form.code}
                    label="Code"
                    placeholder="Enter integration code"
                    generateFrom={[]}
                    disabled={loading}
                    error={errors.code}
                />
                <p class="text-xs text-muted-foreground -mt-3">
                    Used for external systems and integrations (e.g. SAP, Power BI).
                </p>

                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="email">
                            Email <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="email"
                            type="email"
                            class="kt-input {errors.email ? 'kt-input-error' : ''}"
                            placeholder="Enter email address"
                            bind:value={form.email}
                            disabled={loading}
                        />
                        {#if errors.email}
                            <p class="text-sm text-destructive">{errors.email}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="phone">
                            Phone
                        </label>
                        <PhoneInput
                            bind:value={form.phone}
                            placeholder="Enter phone number"
                            disabled={loading}
                        />
                        {#if errors.phone}
                            <p class="text-sm text-destructive">{errors.phone}</p>
                        {/if}
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="website">
                        Website
                    </label>
                    <input
                        id="website"
                        type="url"
                        class="kt-input {errors.website ? 'kt-input-error' : ''}"
                        placeholder="Enter website URL"
                        bind:value={form.website}
                        disabled={loading}
                    />
                    {#if errors.website}
                        <p class="text-sm text-destructive">{errors.website}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="address">
                        Address
                    </label>
                    <input
                        id="address"
                        type="text"
                        class="kt-input {errors.address ? 'kt-input-error' : ''}"
                        placeholder="Enter address"
                        bind:value={form.address}
                        disabled={loading}
                    />
                    {#if errors.address}
                        <p class="text-sm text-destructive">{errors.address}</p>
                    {/if}
                </div>

                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="city">
                            City
                        </label>
                        <input
                            id="city"
                            type="text"
                            class="kt-input {errors.city ? 'kt-input-error' : ''}"
                            placeholder="Enter city"
                            bind:value={form.city}
                            disabled={loading}
                        />
                        {#if errors.city}
                            <p class="text-sm text-destructive">{errors.city}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="state">
                            State
                        </label>
                        <input
                            id="state"
                            type="text"
                            class="kt-input {errors.state ? 'kt-input-error' : ''}"
                            placeholder="Enter state"
                            bind:value={form.state}
                            disabled={loading}
                        />
                        {#if errors.state}
                            <p class="text-sm text-destructive">{errors.state}</p>
                        {/if}
                    </div>
                </div>

                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="zip">
                            Zip
                        </label>
                        <input
                            id="zip"
                            type="text"
                            class="kt-input {errors.zip ? 'kt-input-error' : ''}"
                            placeholder="Enter zip code"
                            bind:value={form.zip}
                            disabled={loading}
                        />
                        {#if errors.zip}
                            <p class="text-sm text-destructive">{errors.zip}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="country">
                            Country
                        </label>
                        <input
                            id="country"
                            type="text"
                            class="kt-input {errors.country ? 'kt-input-error' : ''}"
                            placeholder="Enter country"
                            bind:value={form.country}
                            disabled={loading}
                        />
                        {#if errors.country}
                            <p class="text-sm text-destructive">{errors.country}</p>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Person Card -->
        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Contact Person</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="contact_person">
                        Name
                    </label>
                    <input
                        id="contact_person"
                        type="text"
                        class="kt-input {errors.contact_person ? 'kt-input-error' : ''}"
                        placeholder="Enter contact person name"
                        bind:value={form.contact_person}
                        disabled={loading}
                    />
                    {#if errors.contact_person}
                        <p class="text-sm text-destructive">{errors.contact_person}</p>
                    {/if}
                </div>

                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="contact_person_email">
                            Email
                        </label>
                        <input
                            id="contact_person_email"
                            type="email"
                            class="kt-input {errors.contact_person_email ? 'kt-input-error' : ''}"
                            placeholder="Enter contact person email"
                            bind:value={form.contact_person_email}
                            disabled={loading}
                        />
                        {#if errors.contact_person_email}
                            <p class="text-sm text-destructive">{errors.contact_person_email}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="contact_person_phone">
                            Phone
                        </label>
                        <PhoneInput
                            bind:value={form.contact_person_phone}
                            placeholder="Enter contact person phone"
                            disabled={loading}
                        />
                        {#if errors.contact_person_phone}
                            <p class="text-sm text-destructive">{errors.contact_person_phone}</p>
                        {/if}
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="contact_person_position">
                        Position
                    </label>
                    <input
                        id="contact_person_position"
                        type="text"
                        class="kt-input {errors.contact_person_position ? 'kt-input-error' : ''}"
                        placeholder="Enter contact person position"
                        bind:value={form.contact_person_position}
                        disabled={loading}
                    />
                    {#if errors.contact_person_position}
                        <p class="text-sm text-destructive">{errors.contact_person_position}</p>
                    {/if}
                </div>

                <div class="border-t border-border w-full"></div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="contact_person_department">
                        Department
                    </label>
                    <input
                        id="contact_person_department"
                        type="text"
                        class="kt-input {errors.contact_person_department ? 'kt-input-error' : ''}"
                        placeholder="Enter department name"
                        bind:value={form.contact_person_department}
                        disabled={loading}
                    />
                    {#if errors.contact_person_department}
                        <p class="text-sm text-destructive">{errors.contact_person_department}</p>
                    {/if}
                </div>

                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="contact_person_department_email">
                            Department Email
                        </label>
                        <input
                            id="contact_person_department_email"
                            type="email"
                            class="kt-input {errors.contact_person_department_email ? 'kt-input-error' : ''}"
                            placeholder="Enter department email"
                            bind:value={form.contact_person_department_email}
                            disabled={loading}
                        />
                        {#if errors.contact_person_department_email}
                            <p class="text-sm text-destructive">{errors.contact_person_department_email}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="contact_person_department_phone">
                            Department Phone
                        </label>
                        <PhoneInput
                            bind:value={form.contact_person_department_phone}
                            placeholder="Enter department phone"
                            disabled={loading}
                        />
                        {#if errors.contact_person_department_phone}
                            <p class="text-sm text-destructive">{errors.contact_person_department_phone}</p>
                        {/if}
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="contact_person_department_position">
                        Department Position
                    </label>
                    <input
                        id="contact_person_department_position"
                        type="text"
                        class="kt-input {errors.contact_person_department_position ? 'kt-input-error' : ''}"
                        placeholder="Enter department position"
                        bind:value={form.contact_person_department_position}
                        disabled={loading}
                    />
                    {#if errors.contact_person_department_position}
                        <p class="text-sm text-destructive">{errors.contact_person_department_position}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button
                type="button"
                class="kt-btn kt-btn-secondary"
                on:click={handleCancel}
                disabled={loading}
            >
                Cancel
            </button>
            <button
                type="submit"
                class="kt-btn kt-btn-primary"
                disabled={loading}
            >
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Updating...
                {:else}
                    <i class="fa-solid fa-save mr-2"></i>
                    Update Supplier
                {/if}
            </button>
        </div>
    </form>
</div>
