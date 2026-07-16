<script>
    /** Read-only user detail drawer (id row + barcode/QR via DetailDrawer). */
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import { USER_STATUS_LABELS, USER_STATUS_VARIANTS } from '@/lib/user';

    let { open = $bindable(false), user = null } = $props();

    const fields = $derived(
        user
            ? [
                  { label: 'Username', value: user.username },
                  { label: 'Email', value: user.email },
                  { label: 'Phone', value: user.phone || '—' },
              ]
            : [],
    );
</script>

<DetailDrawer
    bind:open
    title="User"
    id={user?.id}
    {fields}
    createdAt={user?.created_at}
    updatedAt={user?.updated_at}
>
    {#snippet header()}
        <div class="flex items-center gap-3">
            <Avatar src={user?.avatar_url} name={`${user?.firstname} ${user?.lastname}`} size="lg" />
            <div class="flex flex-col gap-1">
                <span class="text-base font-semibold text-mono">{user?.firstname} {user?.lastname}</span>
                <Badge variant={USER_STATUS_VARIANTS[user?.status] ?? 'secondary'}>
                    {USER_STATUS_LABELS[user?.status] ?? user?.status}
                </Badge>
            </div>
        </div>
    {/snippet}
</DetailDrawer>
