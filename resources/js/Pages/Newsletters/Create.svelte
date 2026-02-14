<script>
    import AdminLayout from "../Layouts/AdminLayout.svelte";
    import { onMount, tick } from "svelte";
    import { router } from "@inertiajs/svelte";

    // Props from the server
    export let defaultLanguage;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: "Newsletters",
            url: route("admin.newsletters.index"),
            active: false,
        },
        {
            title: "Create",
            url: route("admin.newsletters.create"),
            active: true,
        },
    ];

    const pageTitle = "Create Newsletter";

    // Newsletter data
    let newsletter = {
        name: "",
        file: null,
        title: "",
    };

    // Newsletter errors
    let errors = {};

    // Loading state
    let loading = false;

    // Handle file input change
    function handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            newsletter.file = file;
        }
    }

    // Handle newsletter submission
    function handleSubmit() {
        loading = true;

        const formData = new FormData();

        // Add newsletter fields
        Object.keys(newsletter).forEach((key) => {
            if (newsletter[key] !== null && newsletter[key] !== "") {
                if (key === "file" && newsletter.file) {
                    formData.append(key, newsletter.file);
                } else if (key !== "file") {
                    formData.append(key, newsletter[key]);
                }
            }
        });

        router.post(route("admin.newsletters.store"), formData, {
            onError: (err) => {
                errors = err;
                loading = false;
            },
            onFinish: () => {
                loading = false;
            },
        });
    }

    // Initialize components after mount
    onMount(async () => {
        await tick();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Newsletter Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">
                        Create New Newsletter
                    </h1>
                    <p class="text-sm text-secondary-foreground">
                        Add a new newsletter to your website
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href={route("admin.newsletters.index")}
                        class="kt-btn kt-btn-outline"
                    >
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Newsletters
                    </a>
                </div>
            </div>

            <!-- Newsletter Form -->
            <form
                on:submit|preventDefault={handleSubmit}
                class="grid gap-5 lg:gap-7.5"
            >
                <!-- Basic Information Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Basic Information</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Newsletter Name -->
                            <div class="flex flex-col gap-2">
                                <label
                                    class="text-sm font-medium text-mono"
                                    for="name"
                                >
                                    Newsletter Name <span
                                        class="text-destructive">*</span
                                    >
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    class="kt-input {errors.name
                                        ? 'kt-input-error'
                                        : ''}"
                                    placeholder="Enter newsletter name"
                                    bind:value={newsletter.name}
                                />
                                {#if errors.name}
                                    <p class="text-sm text-destructive">
                                        {errors.name}
                                    </p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Newsletter File Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">Newsletter File</h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <div class="flex flex-col gap-2">
                                <label
                                    class="text-sm font-medium text-mono"
                                    for="file"
                                >
                                    Upload File <span class="text-destructive"
                                        >*</span
                                    >
                                </label>

                                <input
                                    id="file"
                                    type="file"
                                    class="kt-input"
                                    accept="*"
                                    on:change={handleFileChange}
                                />
                                {#if errors.file}
                                    <p class="text-sm text-destructive">
                                        {errors.file}
                                    </p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h4 class="kt-card-title">
                            Newsletter Content ({defaultLanguage.name})
                        </h4>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-4">
                            <!-- Newsletter Title -->
                            <div class="flex flex-col gap-2">
                                <label
                                    class="text-sm font-medium text-mono"
                                    for="title"
                                >
                                    Newsletter Title <span
                                        class="text-destructive">*</span
                                    >
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    class="kt-input {errors.title
                                        ? 'kt-input-error'
                                        : ''}"
                                    placeholder="Enter newsletter title"
                                    bind:value={newsletter.title}
                                />
                                {#if errors.title}
                                    <p class="text-sm text-destructive">
                                        {errors.title}
                                    </p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Newsletter Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a
                        href={route("admin.newsletters.index")}
                        class="kt-btn kt-btn-outline"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="kt-btn kt-btn-primary"
                        disabled={loading}
                    >
                        {#if loading}
                            <i
                                class="ki-outline ki-loading text-base animate-spin"
                            ></i>
                            Creating...
                        {:else}
                            <i class="ki-filled ki-plus text-base"></i>
                            Create Newsletter
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
