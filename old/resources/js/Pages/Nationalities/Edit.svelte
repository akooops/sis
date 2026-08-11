<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';

    export let nationality;
    export let languages;
    export let translations;

    const breadcrumbs = [
        {
            title: 'Nationalities',
            url: route('admin.nationalities.index'),
            active: false
        },
        {
            title: 'Edit',
            url: route('admin.nationalities.edit', { nationality: nationality?.id }),
            active: true
        }
    ];

    const pageTitle = 'Edit Nationality';

    let form = {
        name: nationality?.name || '',
        code: nationality?.code || ''
    };

    let errors = {};
    let loading = false;

    let translationForms = {};
    let translationErrors = {};
    let translationLoading = {};

    if (languages && Array.isArray(languages)) {
        languages.forEach(language => {
            const title = translations?.title?.[language.code] || '';
            translationForms[language.code] = { title };
            translationErrors[language.code] = {};
            translationLoading[language.code] = false;
        });
    }

    function handleSubmit() {
        loading = true;

        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('name', form.name);
        formData.append('code', form.code);

        router.post(route('admin.nationalities.update', { nationality: nationality.id }), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            }
        });
    }

    function handleTranslationSubmit(languageCode, languageId) {
        translationLoading[languageCode] = true;
        translationErrors[languageCode] = {};

        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        formData.append('_method', 'PATCH');
        formData.append('language_id', languageId);
        formData.append('title', translationForms[languageCode].title);

        fetch(route('admin.nationalities.update-translation', { nationality: nationality.id }), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw data;
                    });
                }
                return response.json();
            })
            .then(() => {
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: `Translation for ${languageCode} updated successfully.`,
                    variant: 'success',
                    position: 'bottom-right',
                });
            })
            .catch(error => {
                if (error.errors) {
                    translationErrors[languageCode] = error.errors;
                } else {
                    translationErrors[languageCode] = { general: ['An error occurred. Please try again.'] };
                }
            })
            .finally(() => {
                translationLoading[languageCode] = false;
            });
    }

    onMount(async () => {
        await tick();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Edit Nationality</h1>
                    <p class="text-sm text-secondary-foreground">
                        Update nationality code and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href={route('admin.nationalities.index')} class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Nationalities
                    </a>
                </div>
            </div>

            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                        <div class="flex items-center gap-5">
                            <button class="kt-tab-toggle py-3 active" data-kt-tab-toggle="#nationality_form_tab">
                                <i class="ki-filled ki-document text-base me-2"></i>
                                Edit nationality
                            </button>
                            <button class="kt-tab-toggle py-3" data-kt-tab-toggle="#translations_tab">
                                <i class="ki-filled ki-geolocation text-base me-2"></i>
                                Translations
                            </button>
                        </div>
                    </div>

                    <div class="grow flex flex-col" id="nationality_form_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <form on:submit|preventDefault={handleSubmit} class="kt-card">
                                <div class="kt-card-header">
                                    <h4 class="kt-card-title">Basic Information</h4>
                                </div>
                                <div class="kt-card-content">
                                    <div class="grid gap-4">
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="name">
                                                Nationality Name <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="name"
                                                type="text"
                                                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                                                placeholder="e.g. Saudi Arabia"
                                                bind:value={form.name}
                                            />
                                            {#if errors.name}
                                                <p class="text-sm text-destructive">{errors.name}</p>
                                            {/if}
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="code">
                                                Nationality Code <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="code"
                                                type="text"
                                                maxlength="2"
                                                class="kt-input {errors.code ? 'kt-input-error' : ''}"
                                                placeholder="e.g. SA"
                                                bind:value={form.code}
                                            />
                                            {#if errors.code}
                                                <p class="text-sm text-destructive">{errors.code}</p>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="flex items-center justify-end gap-3">
                                <a href={route('admin.nationalities.index')} class="kt-btn kt-btn-outline">
                                    Cancel
                                </a>
                                <button
                                    type="submit"
                                    class="kt-btn kt-btn-primary"
                                    disabled={loading}
                                    on:click|preventDefault={handleSubmit}
                                >
                                    {#if loading}
                                        <i class="ki-outline ki-loading text-base animate-spin"></i>
                                        Updating...
                                    {:else}
                                        <i class="ki-filled ki-check text-base"></i>
                                        Update Nationality
                                    {/if}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grow flex flex-col hidden" id="translations_tab">
                        <div class="grid gap-5 lg:gap-7.5 w-full py-4">
                            <div class="kt-tabs kt-tabs-line justify-between mb-6" data-kt-tabs="true">
                                <div class="flex items-center gap-5">
                                    {#each languages as language, index}
                                        <button
                                            class="kt-tab-toggle py-3 {index === 0 ? 'active' : ''}"
                                            data-kt-tab-toggle="#translation_tab_{language.code}"
                                        >
                                            <i class="ki-filled ki-translate text-base me-2"></i>
                                            {language.name}
                                        </button>
                                    {/each}
                                </div>
                            </div>

                            {#each languages as language, index}
                                <div class="grow flex flex-col {index === 0 ? '' : 'hidden'}" id="translation_tab_{language.code}">
                                    <form on:submit|preventDefault={() => handleTranslationSubmit(language.code, language.id)} class="grid gap-4">
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-mono" for="title-{language.id}">
                                                Title <span class="text-destructive">*</span>
                                            </label>
                                            <input
                                                id="title-{language.id}"
                                                type="text"
                                                class="kt-input {translationErrors[language.code]?.title ? 'kt-input-error' : ''}"
                                                placeholder="Enter nationality title"
                                                bind:value={translationForms[language.code].title}
                                            />
                                            {#if translationErrors[language.code]?.title}
                                                <p class="text-sm text-destructive">{translationErrors[language.code].title[0]}</p>
                                            {/if}
                                        </div>

                                        <div class="flex items-center justify-end gap-3 pt-4">
                                            <button
                                                type="submit"
                                                class="kt-btn kt-btn-success"
                                                disabled={translationLoading[language.code]}
                                            >
                                                {#if translationLoading[language.code]}
                                                    <i class="ki-outline ki-loading text-base animate-spin"></i>
                                                    Saving...
                                                {:else}
                                                    <i class="ki-filled ki-check text-base"></i>
                                                    Save Translation
                                                {/if}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout>
