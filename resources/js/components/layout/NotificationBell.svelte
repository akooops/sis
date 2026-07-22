<script>
    /**
     * Topbar notification bell — unread badge + inbox drawer.
     *
     * Polls the unread-count endpoint on an interval (paused while the tab is
     * hidden), plays the beep when the count rises, and lists the signed-in user's
     * recent notifications. Read state is per-user: opening an item marks only
     * this user's row and clicks through via Ziggy when the notification carries a
     * route. The badge count lives in the shared `unread` store so it stays in
     * sync with marking read anywhere.
     */
    import { page, router } from '@inertiajs/svelte';
    import { get } from 'svelte/store';
    import Drawer from '@/components/ui/Drawer.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { unread, refreshUnread, notificationIcon } from '@/lib/notifications.svelte';
    import { formatRelative } from '@/lib/date';

    const POLL_MS = 15000;

    let drawerOpen = $state(false);
    let items = $state([]);
    let loading = $state(false);

    // Lazily created so the file isn't fetched until the first beep is needed.
    let beep;
    function playBeep() {
        try {
            beep ??= new Audio('/assets/media/audio/beep.mp3');
            beep.currentTime = 0;
            // Autoplay may be blocked until the user interacts — that's fine.
            beep.play().catch(() => {});
        } catch {
            // No audio available; the badge still updates.
        }
    }

    async function loadInbox() {
        loading = true;
        try {
            const data = await api.get(route('api.v1.admin.notifications.inbox'), { per_page: 12, sort: '-created_at' });
            items = data?.data ?? [];
        } catch {
            // Keep whatever is shown on a transient failure.
        } finally {
            loading = false;
        }
    }

    function openDrawer() {
        drawerOpen = true;
        loadInbox();
    }

    function hasRoute(name) {
        try {
            return !!name && route().has(name);
        } catch {
            return false;
        }
    }

    async function openItem(item) {
        if (!item.is_read) {
            try {
                await api.patch(route('api.v1.admin.notifications.read', item.id));
                item.is_read = true;
                unread.dec();
            } catch {
                // ignore — the poll will reconcile the count
            }
        }

        if (hasRoute(item.route_name)) {
            drawerOpen = false;
            router.visit(route(item.route_name, item.route_params ?? undefined));
        }
    }

    async function markAll() {
        try {
            await api.post(route('api.v1.admin.notifications.read-all'));
            items = items.map((i) => ({ ...i, is_read: true }));
            unread.reset();
        } catch {
            // ignore
        }
    }

    // Seed from the shared prop, then poll. No reactive state is read in the
    // effect body, so it wires up exactly once.
    $effect(() => {
        let prevCount = get(page).props?.auth?.unread_notifications ?? 0;
        unread.set(prevCount);

        const tick = async () => {
            if (document.hidden) return;
            const after = await refreshUnread();
            if (after > prevCount) {
                playBeep();
                if (drawerOpen) loadInbox();
            }
            prevCount = after;
        };

        const timer = setInterval(tick, POLL_MS);
        const onVisible = () => {
            if (!document.hidden) tick();
        };
        document.addEventListener('visibilitychange', onVisible);

        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisible);
        };
    });
</script>

<button class="kt-btn kt-btn-icon kt-btn-ghost relative" onclick={openDrawer} aria-label="Notifications">
    <i class="ki-filled ki-notification-status text-lg"></i>
    {#if unread.count > 0}
        <span class="kt-badge kt-badge-circle kt-badge-destructive absolute -top-1 -end-1 min-w-[1rem] h-4 px-1 text-2xs">
            {unread.count > 99 ? '99+' : unread.count}
        </span>
    {/if}
</button>

<Drawer bind:open={drawerOpen} width="w-[420px]">
    {#snippet header()}
        <div class="flex w-full items-center justify-between gap-2">
            <span class="text-sm font-semibold text-mono">Notifications</span>
            {#if unread.count > 0}
                <button class="kt-btn kt-btn-xs kt-btn-light" onclick={markAll}>Mark all read</button>
            {/if}
        </div>
    {/snippet}

    {#if loading && items.length === 0}
        <div class="flex flex-col gap-2">
            {#each Array(5) as _}
                <div class="h-16 rounded-lg bg-muted animate-pulse"></div>
            {/each}
        </div>
    {:else if items.length === 0}
        <EmptyState icon="ki-filled ki-notification-status" title="No notifications" body="You're all caught up." />
    {:else}
        <div class="flex flex-col gap-1">
            {#each items as item (item.id)}
                <button
                    class="flex w-full gap-3 rounded-lg p-3 text-start transition-colors hover:bg-muted {item.is_read ? '' : 'bg-muted/40'}"
                    onclick={() => openItem(item)}
                >
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <i class="ki-filled {notificationIcon(item)}"></i>
                    </span>
                    <div class="min-w-0 grow">
                        <div class="flex items-center gap-2">
                            <span class="grow truncate text-sm font-medium text-mono">{item.title}</span>
                            {#if !item.is_read}<span class="size-2 shrink-0 rounded-full bg-primary"></span>{/if}
                        </div>
                        {#if item.body}<p class="line-clamp-2 text-xs text-muted-foreground">{item.body}</p>{/if}
                        <span class="text-2xs text-muted-foreground">{formatRelative(item.created_at)}</span>
                    </div>
                </button>
            {/each}
        </div>
    {/if}
</Drawer>
