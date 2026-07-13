<script>
    import MainLayout from "../../Shared/Layouts/MainLayout.svelte";
    import Pagination from "../../Shared/Utils/Pagination.svelte";
    import SearchBar from "../../Shared/Utils/Forms/SearchBar.svelte";
    import ExportButton from "../../Shared/Utils/ExportButton.svelte";
    import Select2 from "../../Shared/Utils/Forms/Select2.svelte";

    import FiltersDrawer from "./FiltersDrawer.svelte";

    import { onMount, tick } from "svelte";
    import { fly } from "svelte/transition";

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: "Settings",
            url: route("web.admin.settings.index"),
            active: false,
        },
        {
            title: "Index",
            url: route("web.admin.settings.index"),
            active: true,
        },
    ];

    const pageTitle = "Settings";

    // Props
    export let search = "";

    // Reactive variables
    let settings = [];
    let pagination = {};
    let loading = true;
    let perPage = 10;
    let currentPage = 1;

    // Filter state
    let filters = {
        sort_direction: "asc",
    };

    // Form state for inline editing
    let forms = {};
    let loadingStates = {};

    // Fetch settings data
    async function fetchSettings() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            const response = await fetch(
                route("api.v1.admin.settings.index", queryParams),
                {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                },
            );

            const data = await response.json();
            settings = data.settings;
            pagination = data.pagination;

            // Initialize forms
            forms = {};
            settings.forEach((setting) => {
                let val = setting.value;
                if (setting.type === "array") {
                    try {
                        val = typeof val === "string" ? JSON.parse(val) : val;
                        if (!Array.isArray(val)) val = [];
                    } catch {
                        val = [];
                    }
                } else if (setting.type === "boolean") {
                    val =
                        val === "1" ||
                        val === 1 ||
                        val === true ||
                        val === "true";
                } else if (setting.type === "multi-select") {
                    try {
                        val = typeof val === "string" ? JSON.parse(val) : val;
                        if (!Array.isArray(val)) val = [];
                    } catch {
                        val = [];
                    }
                }
                forms[setting.id] = val;
            });

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error("Error fetching settings:", error);
        } finally {
            loading = false;
        }
    }

    // Handlers
    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchSettings();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchSettings();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchSettings();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchSettings();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector(
            '[data-kt-drawer-toggle="#filters_drawer"]',
        );
        if (toggleButton) {
            toggleButton.click();
        }
    }

    // Inline inputs helpers
    function getRouteForType(type) {
        const map = {
            "App\\Models\\Role": "api.v1.admin.roles.index",
            role: "api.v1.admin.roles.index",
        };
        return map[type] ? route(map[type]) : "";
    }

    function addArrayItem(settingId) {
        if (!Array.isArray(forms[settingId])) forms[settingId] = [];
        forms[settingId] = [...forms[settingId], ""];
    }

    function removeArrayItem(settingId, index) {
        forms[settingId] = forms[settingId].filter((_, i) => i !== index);
    }

    // Update Setting
    async function updateSetting(setting) {
        loadingStates[setting.id] = true;

        try {
            // Prepare form data using global helper
            // It handles boolean to "1"/"0" and array to JSON string conversions
            const payload = {
                value: forms[setting.id],
            };

            const formData = prepareFormData(payload, true);

            const response = await fetch(
                route("api.v1.admin.settings.update", { setting: setting.id }),
                {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content"),
                    },
                    body: formData,
                },
            );

            const data = await response.json();

            if (response.ok) {
                toast(data.message || "Setting updated", "success");
            } else {
                toast(data.message || "Error updating setting", "error");
            }
        } catch (error) {
            console.error(error);
            toast("Network error", "error");
        } finally {
            loadingStates[setting.id] = false;
        }
    }

    onMount(() => {
        fetchSettings();
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <!-- Settings Table Card -->
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div
                        class="kt-card-toolbar flex items-center justify-between w-full"
                    >
                        <div class="flex items-center gap-2">
                            <SearchBar
                                bind:value={search}
                                placeholder="Search settings..."
                                debounceMs={500}
                                on:search={handleSearchFromComponent}
                            />

                            <!-- Filter Button -->
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost"
                                on:click={openFiltersDrawer}
                                title="Filter settings"
                            >
                                <i class="fa-solid fa-filter"></i>
                            </button>

                            <ExportButton
                                tableData={settings}
                                headers={[
                                    { key: "id", label: "ID" },
                                    { key: "key", label: "Key" },
                                    { key: "value", label: "Value" },
                                    { key: "group", label: "Group" },
                                    { key: "updated_at", label: "Updated" },
                                ]}
                                filename="settings"
                                totalRecords={pagination?.total || 0}
                                currentPerAlbum={perPage}
                                {filters}
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="kt-card-content p-0"
                    in:fly={{ x: "-100%", duration: 750 }}
                >
                    <div class="kt-scrollable-x-auto kt-card-table">
                        <table
                            class="kt-table kt-table-auto kt-table-border text-sm"
                        >
                            <thead>
                                <tr>
                                    <th style="width: 80px;"
                                        ><span
                                            class="kt-table-col whitespace-nowrap"
                                            >ID</span
                                        ></th
                                    >
                                    <th
                                        ><span
                                            class="kt-table-col whitespace-nowrap"
                                            >Key</span
                                        ></th
                                    >
                                    <th
                                        ><span
                                            class="kt-table-col whitespace-nowrap"
                                            >Group</span
                                        ></th
                                    >
                                    <th
                                        ><span
                                            class="kt-table-col whitespace-nowrap"
                                            >Value</span
                                        ></th
                                    >
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    <!-- Skeleton -->
                                    {#each Array(perPage) as _}
                                        <tr>
                                            <td class="p-4"
                                                ><div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div></td
                                            >
                                            <td class="p-4"
                                                ><div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div></td
                                            >
                                            <td class="p-4"
                                                ><div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div></td
                                            >
                                            <td class="p-4"
                                                ><div
                                                    class="kt-skeleton w-full h-4 rounded"
                                                ></div></td
                                            >
                                        </tr>
                                    {/each}
                                {:else if settings.length === 0}
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="p-10 text-center"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <i
                                                    class="ki-filled ki-setting-3 text-4xl text-muted-foreground mb-4"
                                                ></i>
                                                <h3
                                                    class="text-lg font-semibold text-mono"
                                                >
                                                    No settings found
                                                </h3>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    {#each settings as setting}
                                        <tr class="hover:bg-muted/50">
                                            <td
                                                ><span
                                                    class="text-xs font-medium text-primary"
                                                    >#{setting.id}</span
                                                ></td
                                            >
                                            <td>
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-medium font-mono text-sm"
                                                        >{setting.key}</span
                                                    >
                                                    {#if setting.description}
                                                        <span
                                                            class="text-xs text-muted-foreground"
                                                            >{setting.description}</span
                                                        >
                                                    {/if}
                                                </div>
                                            </td>
                                            <td
                                                ><span
                                                    class="kt-badge kt-badge-sm kt-badge-outline kt-badge-info"
                                                    >{setting.group}</span
                                                ></td
                                            >

                                            <!-- Editable Column -->
                                            <td class="py-2">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    {#if ["text", "number", "email", "url", "password"].includes(setting.type)}
                                                        <input
                                                            type="text"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {:else if setting.type === "color"}
                                                        <input
                                                            type="color"
                                                            class="h-10 w-20 p-1 border rounded"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                        <input
                                                            type="text"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {:else if setting.type === "boolean"}
                                                        <label
                                                            class="flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                class="kt-switch"
                                                                bind:checked={
                                                                    forms[
                                                                        setting
                                                                            .id
                                                                    ]
                                                                }
                                                            />
                                                            <span
                                                                class="text-sm"
                                                                >{forms[
                                                                    setting.id
                                                                ]
                                                                    ? "Enabled"
                                                                    : "Disabled"}</span
                                                            >
                                                        </label>
                                                    {:else if setting.type === "code" || setting.type === "rich_text"}
                                                        <textarea
                                                            class="kt-input flex-1 h-20 font-mono text-xs"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        ></textarea>
                                                    {:else if setting.type === "select"}
                                                        <select
                                                            class="kt-select flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        >
                                                            <option value=""
                                                                >Select...</option
                                                            >
                                                            {#if setting.options?.choices}
                                                                {#each setting.options.choices as choice}
                                                                    <option
                                                                        value={choice.value}
                                                                        >{choice.label}</option
                                                                    >
                                                                {/each}
                                                            {/if}
                                                        </select>
                                                    {:else if setting.type === "multi-select"}
                                                        <select
                                                            class="kt-select flex-1 h-auto"
                                                            multiple
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        >
                                                            {#if setting.options?.choices}
                                                                {#each setting.options.choices as choice}
                                                                    <option
                                                                        value={choice}
                                                                        >{choice}</option
                                                                    >
                                                                {/each}
                                                            {/if}
                                                        </select>
                                                    {:else if setting.type === "date"}
                                                        <input
                                                            type="date"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {:else if setting.type === "time"}
                                                        <input
                                                            type="time"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {:else if setting.type === "datetime"}
                                                        <input
                                                            type="datetime-local"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {:else if setting.type === "array"}
                                                        <div
                                                            class="flex flex-col gap-2 w-full"
                                                        >
                                                            {#each forms[setting.id] as item, idx}
                                                                <div
                                                                    class="flex gap-2"
                                                                >
                                                                    <input
                                                                        type="text"
                                                                        class="kt-input flex-1"
                                                                        bind:value={
                                                                            forms[
                                                                                setting
                                                                                    .id
                                                                            ][
                                                                                idx
                                                                            ]
                                                                        }
                                                                    />
                                                                    <button
                                                                        type="button"
                                                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive"
                                                                        on:click={() =>
                                                                            removeArrayItem(
                                                                                setting.id,
                                                                                idx,
                                                                            )}
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-times"
                                                                        ></i>
                                                                    </button>
                                                                </div>
                                                            {/each}
                                                            <button
                                                                type="button"
                                                                class="kt-btn kt-btn-xs kt-btn-outline w-fit"
                                                                on:click={() =>
                                                                    addArrayItem(
                                                                        setting.id,
                                                                    )}
                                                            >
                                                                <i
                                                                    class="fa-solid fa-plus mr-1"
                                                                ></i> Add
                                                            </button>
                                                        </div>
                                                    {:else if getRouteForType(setting.type)}
                                                        <div
                                                            class="w-full min-w-[200px]"
                                                        >
                                                            {#if forms[setting.id] && !setting.showingSelect}
                                                                <div
                                                                    class="flex items-center gap-2"
                                                                >
                                                                    <span
                                                                        class="kt-badge kt-badge-outline kt-badge-success py-3 px-4"
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-check-circle mr-2"
                                                                        ></i>
                                                                        #{forms[
                                                                            setting
                                                                                .id
                                                                        ]}
                                                                    </span>
                                                                    <button
                                                                        class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-primary"
                                                                        on:click={() =>
                                                                            (setting.showingSelect = true)}
                                                                        title="Change"
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-pen"
                                                                        ></i>
                                                                    </button>
                                                                </div>
                                                            {:else}
                                                                <div
                                                                    class="flex items-center gap-2"
                                                                >
                                                                    <div
                                                                        class="flex-1"
                                                                    >
                                                                        <Select2
                                                                            id="inline-select-{setting.id}"
                                                                            placeholder="Search..."
                                                                            bind:value={
                                                                                forms[
                                                                                    setting
                                                                                        .id
                                                                                ]
                                                                            }
                                                                            on:select={(
                                                                                e,
                                                                            ) => {
                                                                                // Update the model for display
                                                                                setting.model =
                                                                                    e.detail.data;
                                                                                setting.showingSelect = false;
                                                                                forms[
                                                                                    setting.id
                                                                                ] =
                                                                                    e.detail.value;
                                                                            }}
                                                                            ajax={{
                                                                                url: getRouteForType(
                                                                                    setting.type,
                                                                                ),
                                                                                dataType:
                                                                                    "json",
                                                                                data: (
                                                                                    params,
                                                                                ) => ({
                                                                                    search: params.term,
                                                                                    per_page: 10,
                                                                                }),
                                                                                processResults:
                                                                                    (
                                                                                        data,
                                                                                    ) => ({
                                                                                        results:
                                                                                            (
                                                                                                data.roles ||
                                                                                                []
                                                                                            ).map(
                                                                                                (
                                                                                                    item,
                                                                                                ) => ({
                                                                                                    id: item.id,
                                                                                                    text:
                                                                                                        item.name ||
                                                                                                        `Role #${item.id}`,
                                                                                                    ...item,
                                                                                                }),
                                                                                            ),
                                                                                    }),
                                                                            }}
                                                                        />
                                                                    </div>
                                                                    {#if forms[setting.id]}
                                                                        <button
                                                                            class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-danger"
                                                                            on:click={() =>
                                                                                (setting.showingSelect = false)}
                                                                            title="Cancel"
                                                                        >
                                                                            <i
                                                                                class="fa-solid fa-times"

                                                                            ></i>
                                                                        </button>
                                                                    {/if}
                                                                </div>
                                                            {/if}
                                                        </div>
                                                    {:else}
                                                        <input
                                                            type="text"
                                                            class="kt-input flex-1"
                                                            bind:value={
                                                                forms[
                                                                    setting.id
                                                                ]
                                                            }
                                                        />
                                                    {/if}

                                                    <!-- Save Button -->
                                                    <button
                                                        class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost ml-2 text-primary"
                                                        on:click={() =>
                                                            updateSetting(
                                                                setting,
                                                            )}
                                                        disabled={loadingStates[
                                                            setting.id
                                                        ]}
                                                        title="Save"
                                                    >
                                                        {#if loadingStates[setting.id]}
                                                            <i
                                                                class="fa-solid fa-spinner fa-spin"
                                                            ></i>
                                                        {:else}
                                                            <i
                                                                class="fa-solid fa-save"
                                                            ></i>
                                                        {/if}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {/if}
                            </tbody>
                        </table>
                    </div>
                    {#if pagination && pagination.total > 0}
                        <Pagination
                            {pagination}
                            {perPage}
                            onPageChange={goToPage}
                            onPerPageChange={handlePerPageChange}
                        />
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden triggers -->
    <button style="display:none" data-kt-drawer-toggle="#filters_drawer"
    ></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
</MainLayout>
