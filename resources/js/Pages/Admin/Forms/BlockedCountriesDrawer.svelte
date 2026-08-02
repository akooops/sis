<script>
    /**
     * Form → blocked countries: where this form refuses to be served.
     *
     * Flat resource scoped by filter[form_id], so the parent travels in
     * `parentFilter` (read) and `storePayload` (write).
     *
     * THE WARNING IS THE POINT. A country block is resolved from a CDN's country
     * header, and GeoResolver trusts that header only behind a proxy TrustProxies
     * recognises — which is nothing at all until TRUSTED_PROXIES is set. With it
     * unset the resolver always returns null, every block passes everyone
     * through, and the list looks exactly the same as a working one. So the
     * drawer asks the server whether geo is live and says so above the picker
     * rather than letting an admin believe they are protected.
     *
     * The picker only offers countries this form does NOT already block — the
     * list here is paginated, so the exclusion is a server-side filter rather
     * than something the client could work out from the page it can see.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import { api } from '@/lib/api/client';

    let { open = $bindable(false), form = null } = $props();

    // null while unknown — neither reassure nor alarm before the answer is in.
    let geo = $state(null);
    let asked = false;

    $effect(() => {
        if (!open || asked) return;
        asked = true;
        loadGeoStatus();
    });

    async function loadGeoStatus() {
        try {
            geo = await api.get(route('api.v1.admin.form-blocked-countries.geo-status'));
        } catch {
            // A failed probe must not claim geo works; leaving it null just
            // shows no banner rather than a false all-clear.
            geo = null;
        }
    }

    const columns = [
        { key: 'country', label: 'Country', truncate: false, maxWidth: '220px' },
        { key: 'code', label: 'Code', truncate: false, width: '90px' },
    ];
</script>

<PivotDrawer
    bind:open
    title={`Blocked countries — ${form?.name ?? ''}`}
    parentId={form?.id}
    parentFilter={form ? { form_id: form.id } : null}
    storePayload={form ? { form_id: form.id } : null}
    indexRoute="api.v1.admin.form-blocked-countries.index"
    storeRoute="api.v1.admin.form-blocked-countries.store"
    destroyRoute="api.v1.admin.form-blocked-countries.destroy"
    resource="api.v1.admin.countries.index"
    resourceParams={{}}
    payloadKey="countries"
    relation="country"
    {columns}
    assignLabel="Block these countries"
    addLabel="Block"
    emptyTitle="No country is blocked"
    emptyBody="The form is served everywhere."
    searchPlaceholder="Search blocked countries…"
    confirmBody={(row) => `${row.country?.name ?? 'This country'} will be able to reach this form again.`}
    {banner}
    {cells}
    {resourceOption}
/>

{#snippet banner()}
    {#if geo && !geo.configured}
        <Alert variant="warning">
            <div class="flex flex-col gap-1">
                <span class="font-medium">Country blocking is not active on this server.</span>
                <span class="text-sm">
                    A visitor's country is read from a CDN header, and that header is only trusted when the
                    request arrives through a known proxy. <code>TRUSTED_PROXIES</code> is unset here, so every
                    country below is currently allowed through. Set it at deploy time to make these blocks take
                    effect.
                </span>
                {#if geo.headers?.length}
                    <span class="text-xs text-muted-foreground">Headers read: {geo.headers.join(', ')}</span>
                {/if}
            </div>
        </Alert>
    {/if}
{/snippet}

<!-- The picker: name and flag, the way the Countries index draws them. -->
{#snippet resourceOption(item)}
    <span class="flex min-w-0 items-center gap-2">
        <img src={item.raw?.flag_url} alt="" class="w-5 shrink-0 rounded-sm object-contain" />
        <span class="min-w-0 truncate" title={item.label}>{item.label}</span>
    </span>
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'country'}
        <div class="flex items-center gap-3">
            <img src={row.country?.flag_url} alt="" class="w-5 shrink-0 rounded-sm object-contain" />
            <span class="text-sm font-medium text-mono">{row.country?.name ?? ''}</span>
        </div>
    {:else if column.key === 'code'}
        <Badge variant="secondary">{row.country?.code ?? ''}</Badge>
    {:else}
        {row.country?.[column.key] ?? row[column.key] ?? ''}
    {/if}
{/snippet}
