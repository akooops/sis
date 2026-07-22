<script>
    /**
     * Topbar notification bell — unread badge + inbox drawer in the classic
     * flat style: full-width search section, edge-to-edge divided rows (read
     * rows faded), time-ago · type footer line, and "Load more" pagination
     * that appends pages. Removing a notification is a page-only action —
     * the drawer is for reading and clicking through.
     *
     * The bell polls the unread-count endpoint (paused while the tab is
     * hidden) and beeps when the count rises; while the drawer is open a
     * silent refresh re-fetches everything currently loaded, so the list stays
     * fresh without collapsing back to page one. The badge lives in the shared
     * `unread` store so marking read anywhere stays in sync.
     */
    import { page, router } from '@inertiajs/svelte';
    import { get } from 'svelte/store';
    import Drawer from '@/components/ui/Drawer.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { unread, refreshUnread, notificationIcon } from '@/lib/notifications.svelte';
    import { formatRelative } from '@/lib/date';

    const POLL_MS = 15000;
    const PER_PAGE = 10;

    let drawerOpen = $state(false);
    let items = $state([]);
    let meta = $state(null);
    let search = $state('');
    let loading = $state(false);
    let loadingMore = $state(false);
    let markingAll = $state(false);
    let searchTimer;

    const canLoadMore = $derived(!!meta && meta.current_page < meta.last_page);

    async function load({ reset = true, silent = false } = {}) {
        if (reset && !silent) loading = true;
        if (!reset) loadingMore = true;

        const pageNo = reset ? 1 : (meta?.current_page ?? 1) + 1;
        // A silent refresh re-fetches everything already on screen in one go,
        // so an open drawer updates in place instead of collapsing to page one.
        const perPage = silent && reset ? Math.max(PER_PAGE, items.length) : PER_PAGE;

        try {
            const data = await api.get(route('api.v1.admin.notifications.index'), {
                page: pageNo,
                per_page: perPage,
                sort: '-created_at',
                filter: { search: search || undefined },
            });
            const rows = data?.data ?? [];
            items = reset ? rows : [...items, ...rows];
            meta = data?.meta ?? null;
        } catch {
            // keep whatever is shown on a transient failure
        } finally {
            loading = false;
            loadingMore = false;
        }
    }

    function openDrawer() {
        drawerOpen = true;
        search = '';
        load();
    }

    function onSearchInput(event) {
        search = event.currentTarget.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => load(), 400);
    }

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
        if (markingAll) return;
        markingAll = true;
        try {
            await api.post(route('api.v1.admin.notifications.read-all'));
            items = items.map((i) => ({ ...i, is_read: true }));
            unread.reset();
        } catch {
            // ignore
        } finally {
            markingAll = false;
        }
    }

    function viewAll() {
        drawerOpen = false;
        router.visit(route('web.admin.notifications.index'));
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
                if (drawerOpen) load({ reset: true, silent: true });
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

<Drawer bind:open={drawerOpen} width="w-[450px]">
    {#snippet header()}
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-mono">Notifications</span>
            {#if unread.count > 0}
                <span class="kt-badge kt-badge-sm kt-badge-destructive">{unread.count > 99 ? '99+' : unread.count}</span>
            {/if}
        </div>
    {/snippet}

    <!-- -m-5 cancels the drawer body padding: the search section and every row
         run edge to edge with full-width dividers, classic style. -->
    <div class="-m-5 flex min-w-0 flex-col">
        <div class="shrink-0 border-b border-border px-5 pb-3 pt-2">
            <div class="kt-input max-w-full">
                <i class="ki-filled ki-magnifier text-muted-foreground"></i>
                <input type="text" placeholder="Search notifications..." value={search} oninput={onSearchInput} />
            </div>
        </div>

        {#if loading}
            <div class="flex min-w-0 flex-col">
                {#each Array(6) as _}
                    <div class="flex min-w-0 gap-2.5 border-b border-border px-5 py-3 last:border-b-0">
                        <div class="size-9 shrink-0 animate-pulse rounded-full bg-muted"></div>
                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="h-4 w-3/4 animate-pulse rounded bg-muted"></div>
                            <div class="h-3 w-full animate-pulse rounded bg-muted"></div>
                            <div class="h-3 w-1/2 animate-pulse rounded bg-muted"></div>
                        </div>
                    </div>
                {/each}
            </div>
        {:else if items.length === 0}
            <div class="px-5 py-6">
                <EmptyState
                    icon="ki-filled ki-notification-status"
                    title="No notifications"
                    body={search ? 'No notifications match your search.' : "You're all caught up."}
                />
            </div>
        {:else}
            <div class="flex min-w-0 flex-col">
                {#each items as item (item.id)}
                    <div
                        class="group flex min-w-0 cursor-pointer gap-2.5 border-b border-border px-5 py-3 transition-colors last:border-b-0 hover:bg-muted {item.is_read ? 'opacity-75' : ''}"
                        onclick={() => openItem(item)}
                        onkeydown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                openItem(item);
                            }
                        }}
                        role="button"
                        tabindex="0"
                    >
                        <div class="shrink-0">
                            <div class="flex size-9 items-center justify-center rounded-full bg-primary/10">
                                <i class="ki-filled {notificationIcon(item)} text-sm text-primary"></i>
                            </div>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1 overflow-hidden">
                            <span class="min-w-0 text-sm font-semibold text-mono line-clamp-1 {item.is_read ? 'text-muted-foreground' : ''}">
                                {item.title}
                            </span>

                            {#if item.body}
                                <p class="text-xs text-secondary-foreground line-clamp-2 break-words">{item.body}</p>
                            {/if}

                            <div class="mt-0.5 flex min-w-0 items-center gap-1.5">
                                <span class="shrink-0 text-xs font-medium text-muted-foreground">{formatRelative(item.created_at)}</span>
                                {#if item.type_name}
                                    <span class="size-1 shrink-0 rounded-full bg-muted-foreground/40"></span>
                                    <span class="min-w-0 text-xs font-medium text-muted-foreground line-clamp-1">{item.type_name}</span>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/each}

                {#if canLoadMore}
                    <div class="flex items-center justify-center p-3">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => load({ reset: false })} disabled={loadingMore}>
                            {#if loadingMore}
                                Loading…
                            {:else}
                                <i class="ki-filled ki-down"></i>Load more
                            {/if}
                        </button>
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    {#snippet footer()}
        <div class="grid w-full grid-cols-2 gap-2.5">
            <button class="kt-btn kt-btn-sm kt-btn-outline justify-center" onclick={markAll} disabled={markingAll || unread.count === 0}>
                <i class="ki-filled ki-check"></i>{markingAll ? 'Marking…' : 'Mark all read'}
            </button>
            <button class="kt-btn kt-btn-sm kt-btn-primary justify-center" onclick={viewAll}>
                <i class="ki-filled ki-notification-status"></i>View all
            </button>
        </div>
    {/snippet}
</Drawer>
