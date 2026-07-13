<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productionSite = null;

    const notificationTypes = [
        { value: 'general', label: 'General' },
        { value: 'purchasing', label: 'Purchasing' },
        { value: 'production', label: 'Production' },
        { value: 'planning', label: 'Planning' },
        { value: 'qa', label: 'QA' },
        { value: 'qc', label: 'QC' },
        { value: 'qp', label: 'QP' },
        { value: 'warehouse', label: 'Warehouse' },
    ];

    let form = {
        type: '',
    };

    let selectedUsers = [];
    let errors = {};
    let loading = false;
    let userSelectComponent;

    function handleUserSelect(event) {
        const userId = event.detail.value;

        if (userId && !selectedUsers.find((user) => user.id === userId)) {
            const user = event.detail.data;

            if (user) {
                selectedUsers = [...selectedUsers, {
                    id: user.id,
                    name: user.text,
                    email: user.email || '',
                }];
            }
        }

        if (userSelectComponent) {
            userSelectComponent.setValue('');
        }
    }

    function removeSelectedUser(userId) {
        selectedUsers = selectedUsers.filter((user) => user.id !== userId);
    }

    async function handleSubmit() {
        if (!form.type) {
            errors = { type: 'Please select a notification type.' };
            return;
        }

        if (selectedUsers.length === 0) {
            errors = { user_ids: 'Please select at least one user.' };
            if (userSelectComponent) {
                userSelectComponent.setError(true);
            }
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = new FormData();
            formData.append('type', form.type);
            selectedUsers.forEach((user) => {
                formData.append('user_ids[]', user.id);
            });

            const response = await fetch(route('api.v1.admin.site-responsibles.store', { productionSite: productionSite.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                form = { type: '' };
                selectedUsers = [];
                errors = {};

                if (userSelectComponent) {
                    userSelectComponent.setValue('');
                    userSelectComponent.setError(false);
                }

                toast('Notification users added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;

                if (errors.user_ids && userSelectComponent) {
                    userSelectComponent.setError(true);
                }
            } else {
                errors = { general: data.message || 'An error occurred while adding notification users.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = { type: '' };
        selectedUsers = [];
        errors = {};

        if (userSelectComponent) {
            userSelectComponent.setValue('');
            userSelectComponent.setError(false);
        }

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

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="notification-type">
                Notification type <span class="text-destructive">*</span>
            </label>
            <select
                id="notification-type"
                class="kt-select {errors.type ? 'kt-input-error' : ''}"
                bind:value={form.type}
                disabled={loading}
            >
                <option value="">Select notification type...</option>
                {#each notificationTypes as notificationType}
                    <option value={notificationType.value}>{notificationType.label}</option>
                {/each}
            </select>
            {#if errors.type}
                <p class="text-sm text-destructive">{errors.type}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="user-select">
                Select users <span class="text-destructive">*</span>
            </label>
            <Select2
                bind:this={userSelectComponent}
                id="user-select"
                placeholder="Search and select users..."
                on:select={handleUserSelect}
                disabled={loading}
                ajax={{
                    url: route('api.v1.admin.users.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term,
                            per_page: 10,
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.users.map((user) => ({
                                id: user.id,
                                text: user.fullname || user.name,
                                email: user.email,
                            })),
                        };
                    },
                    cache: true,
                }}
            />
            {#if errors.user_ids}
                <p class="text-sm text-destructive">{errors.user_ids}</p>
            {/if}
        </div>

        {#if selectedUsers.length > 0}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">
                    Selected users ({selectedUsers.length})
                </label>
                <div class="space-y-2 max-h-40 overflow-y-auto border border-border rounded-lg p-3">
                    {#each selectedUsers as user (user.id)}
                        <div class="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-lg">
                            <div class="flex flex-col gap-1">
                                <p class="text-sm font-medium">{user.name}</p>
                                {#if user.email}
                                    <p class="text-xs text-muted-foreground">{user.email}</p>
                                {/if}
                            </div>
                            <button
                                type="button"
                                class="p-1 text-muted-foreground hover:text-destructive transition-colors cursor-pointer"
                                on:click={() => removeSelectedUser(user.id)}
                                disabled={loading}
                            >
                                <i class="fa-solid fa-times text-sm"></i>
                            </button>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading || selectedUsers.length === 0 || !form.type}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Adding...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add Users ({selectedUsers.length})
                {/if}
            </button>
        </div>
    </form>
</div>
