<script>
    import AdminLayout from "../Layouts/AdminLayout.svelte";
    import { onMount } from "svelte";

    // Props
    export let newsletter;
    export let languages;
    export let translations;

    // Define breadcrumbs for this newsletter
    const breadcrumbs = [
        {
            title: "Newsletters",
            url: route("admin.newsletters.index"),
            active: false,
        },
        {
            title: newsletter?.name || "Newsletter Details",
            url: route("admin.newsletters.show", {
                newsletter: newsletter?.id,
            }),
            active: true,
        },
    ];

    const pageTitle = "Newsletter Details";

    // Get translation for a field and language
    function getTranslation(field, languageCode) {
        if (
            translations &&
            translations[field] &&
            translations[field][languageCode]
        ) {
            return translations[field][languageCode];
        }
        return `${field}.${languageCode}`;
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Newsletter Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">
                        Newsletter Information
                    </h1>
                    <p class="text-sm text-secondary-foreground">
                        View newsletter details and translations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href={route("admin.newsletters.index")}
                        class="kt-btn kt-btn-outline"
                    >
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back
                    </a>
                    {#if hasPermission("admin.newsletters.update")}
                        <a
                            href={route("admin.newsletters.edit", {
                                newsletter: newsletter?.id,
                            })}
                            class="kt-btn kt-btn-primary"
                        >
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Newsletter
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Newsletter Information Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Newsletter Information</h4>
                </div>
                <div class="kt-card-content">
                    <div class="flex flex-col lg:flex-row gap-6 w-full">
                        <!-- Newsletter Details -->
                        <div class="grid gap-4 w-full">
                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">
                                    Newsletter Name
                                </h4>
                                <p class="text-sm text-secondary-foreground">
                                    {newsletter?.name}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">
                                    Newsletter Url
                                </h4>
                                <span
                                    class="kt-badge kt-badge-outline kt-badge-primary w-fit"
                                >
                                    <a
                                        href={newsletter?.newsletterUrl}
                                        target="_blank"
                                    >
                                        Open <i
                                            class="ki-filled ki-arrow-up-right"
                                        ></i>
                                    </a>
                                </span>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">
                                    Created At
                                </h4>
                                <p class="text-sm text-secondary-foreground">
                                    {newsletter?.created_at
                                        ? new Date(
                                              newsletter.created_at,
                                          ).toLocaleDateString("en-US", {
                                              year: "numeric",
                                              month: "long",
                                              day: "numeric",
                                              hour: "2-digit",
                                              minute: "2-digit",
                                          })
                                        : "N/A"}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <h4 class="text-sm font-semibold text-mono">
                                    Updated At
                                </h4>
                                <p class="text-sm text-secondary-foreground">
                                    {newsletter?.updated_at
                                        ? new Date(
                                              newsletter.updated_at,
                                          ).toLocaleDateString("en-US", {
                                              year: "numeric",
                                              month: "long",
                                              day: "numeric",
                                              hour: "2-digit",
                                              minute: "2-digit",
                                          })
                                        : "N/A"}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Translations Card -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Language Tabs -->
                    <div
                        class="kt-tabs kt-tabs-line justify-between mb-6"
                        data-kt-tabs="true"
                    >
                        <div class="flex items-center gap-5">
                            {#each languages as language, index}
                                <button
                                    class="kt-tab-toggle py-3 {index === 0
                                        ? 'active'
                                        : ''}"
                                    data-kt-tab-toggle="#translation_tab_{language.code}"
                                >
                                    <i
                                        class="ki-filled ki-translate text-base me-2"
                                    ></i>
                                    {language.name}
                                </button>
                            {/each}
                        </div>
                    </div>

                    <!-- Tab Content -->
                    {#each languages as language, index}
                        <div
                            class="grow flex flex-col {index === 0
                                ? ''
                                : 'hidden'}"
                            id="translation_tab_{language.code}"
                        >
                            <div class="grid gap-6 w-full py-4">
                                <!-- Title Translation -->
                                <div class="flex flex-col gap-2">
                                    <h4 class="text-sm font-semibold text-mono">
                                        Newsletter {language.name} Title
                                    </h4>
                                    <p
                                        class="text-sm text-secondary-foreground p-3 bg-muted/50 rounded-lg"
                                    >
                                        {getTranslation("title", language.code)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout>
