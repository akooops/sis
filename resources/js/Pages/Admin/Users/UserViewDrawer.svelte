<script>
    /** Read-only user detail drawer (id row + barcode/QR via DetailDrawer). */
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), user = null } = $props();

    const fields = $derived(
        user
            ? [
                  { label: $t('users.fields.username'), value: user.username },
                  { label: $t('users.fields.email'), value: user.email },
                  { label: $t('users.fields.phone'), value: user.phone || '—' },
              ]
            : [],
    );
</script>

<DetailDrawer
    bind:open
    title={$t('users.singular')}
    id={user?.id}
    {fields}
    createdAt={user?.created_at}
    updatedAt={user?.updated_at}
    deletedAt={user?.deleted_at}
>
    {#snippet header()}
        <div class="flex items-center gap-3">
            <Avatar src={user?.avatar_url} name={`${user?.firstname} ${user?.lastname}`} size="lg" />
            <div class="flex flex-col gap-1">
                <span class="text-base font-semibold text-mono">{user?.firstname} {user?.lastname}</span>
                <Badge variant={user?.verified_at ? 'success' : 'warning'}>
                    {user?.verified_at ? $t('users.status.verified') : $t('users.status.pending')}
                </Badge>
            </div>
        </div>
    {/snippet}
</DetailDrawer>
