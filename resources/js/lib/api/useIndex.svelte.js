/**
 * Index/list data composable — one standard powering every table page.
 *
 * Owns the query state (filter / sort / page / per_page / include), fetches
 * against the JSON API using the spatie query-builder contract, and exposes
 * reactive rows + pagination meta. Also provides 3-state column sorting and
 * optional periodic polling ("live" tables).
 *
 * Usage (in a component <script>):
 *   const list = useIndex('api.v1.admin.users.index', {
 *       perPage: 15, sort: '-created_at', include: 'roles', pollMs: 15000,
 *   });
 *   // list.rows, list.meta, list.loading, list.error
 *   // list.setFilters({...}), list.setSearch('x'), list.toggleSort('email')
 *   // list.setSort('-created_at'), list.apply({ filter, sort })
 *   // list.goToPage(2), list.setPerPage(25), list.refresh()
 *
 * Sort is two-way: clicking a column header (toggleSort) and the Filters drawer
 * (apply) both write the same `params.sort`, so each reflects the other.
 *
 * `routeParams` accepts a function (`() => parentId`) for routes whose parent is
 * in the URL, e.g. a pivot drawer opened for one row after another.
 *
 * Must be called during component init (it uses $state/$effect internally).
 */

import { api } from './client';

/**
 * Read query state from the current URL so deep links / cross-page redirects
 * pre-seed the table (e.g. `/admin/permissions?filter[id]=<id>` opens already
 * filtered to that record). Supports `filter[key]=v`, `sort`, `page`, `per_page`.
 */
function readUrlQuery() {
    if (typeof window === 'undefined') return {};
    const sp = new URLSearchParams(window.location.search);
    const out = { filter: {} };
    for (const [key, value] of sp.entries()) {
        const m = key.match(/^filter\[(.+)\]$/);
        if (m) {
            out.filter[m[1]] = value;
        } else if (key === 'sort') {
            out.sort = value;
        } else if (key === 'page') {
            out.page = Number(value) || 1;
        } else if (key === 'per_page') {
            out.per_page = Number(value) || undefined;
        }
    }
    if (!Object.keys(out.filter).length) delete out.filter;
    return out;
}

export function useIndex(routeName, options = {}) {
    const {
        perPage = 15,
        sort = null,
        filter = {},
        include = null,
        pollMs = 0,
        immediate = true,
        routeParams = undefined,
        readUrl = true,
    } = options;

    let rows = $state([]);
    let meta = $state(null);
    let loading = $state(false);
    let error = $state(null);

    // URL query params win over the passed defaults so deep links take effect.
    const urlQuery = readUrl ? readUrlQuery() : {};

    const params = $state({
        page: urlQuery.page ?? 1,
        per_page: urlQuery.per_page ?? perPage,
        sort: urlQuery.sort ?? sort,
        include,
        filter: { ...filter, ...(urlQuery.filter ?? {}) },
    });

    let controller = null;

    function queryParams() {
        // Drop empty filter values so the URL stays clean.
        const cleanFilter = {};
        for (const [k, v] of Object.entries(params.filter ?? {})) {
            if (v !== null && v !== undefined && v !== '') cleanFilter[k] = v;
        }
        return {
            page: params.page,
            per_page: params.per_page,
            sort: params.sort || undefined,
            include: params.include || undefined,
            filter: Object.keys(cleanFilter).length ? cleanFilter : undefined,
        };
    }

    let reqId = 0;

    async function fetch({ silent = false } = {}) {
        // Token so a superseded (aborted) request never clobbers the newer one's
        // loading/rows state — otherwise the skeleton flickers off immediately.
        const my = ++reqId;
        controller?.abort();
        controller = new AbortController();
        if (!silent) loading = true;
        error = null;
        try {
            // routeParams may be a function so a drawer reused across rows can
            // point at the row it is currently open for — a plain value is read
            // once at init and would pin it to the first parent forever.
            const url = route(routeName, typeof routeParams === 'function' ? routeParams() : routeParams);
            const data = await api.get(url, queryParams(), { signal: controller.signal });
            if (my !== reqId) return; // stale
            rows = Array.isArray(data) ? data : (data?.data ?? []);
            meta = Array.isArray(data) ? null : (data?.meta ?? null);
        } catch (err) {
            if (err?.name === 'AbortError' || my !== reqId) return;
            error = err;
            rows = [];
            meta = null;
        } finally {
            if (my === reqId && !silent) loading = false;
        }
    }

    /** Refetch from page 1 (used after filter/search/sort changes). */
    function reload() {
        params.page = 1;
        return fetch();
    }

    function setSearch(value) {
        params.filter = { ...params.filter, search: value };
        return reload();
    }

    function setFilters(next) {
        params.filter = { ...next };
        return reload();
    }

    /** Set the sort outright: 'field' (asc), '-field' (desc), or null for none. */
    function setSort(next) {
        params.sort = next || null;
        return reload();
    }

    /**
     * Filters and sort in one request — the Filters drawer applies both at once,
     * and calling setFilters() then setSort() would fire two round trips and
     * leave the table briefly showing the old sort.
     */
    function apply({ filter, sort: nextSort } = {}) {
        if (filter !== undefined) params.filter = { ...filter };
        if (nextSort !== undefined) params.sort = nextSort || null;
        return reload();
    }

    function setPerPage(value) {
        params.per_page = value;
        return reload();
    }

    function goToPage(page) {
        if (!page || page === params.page) return;
        params.page = page;
        return fetch();
    }

    /**
     * 3-state sort on a field: none -> asc -> desc -> none.
     * Sends `sort=field` (asc) or `sort=-field` (desc) to the query builder.
     */
    function toggleSort(field) {
        if (params.sort === field) {
            params.sort = `-${field}`;
        } else if (params.sort === `-${field}`) {
            params.sort = null;
        } else {
            params.sort = field;
        }
        return reload();
    }

    /** Current sort direction for a field: 'asc' | 'desc' | null. */
    function sortDirection(field) {
        if (params.sort === field) return 'asc';
        if (params.sort === `-${field}`) return 'desc';
        return null;
    }

    function refresh() {
        return fetch();
    }

    // Initial load + polling. Polling refetches silently (no skeleton flash) and
    // pauses while the tab is hidden.
    $effect(() => {
        if (immediate) fetch();

        if (!pollMs) return;

        let timer = null;
        const tick = () => {
            if (!document.hidden && !loading) fetch({ silent: true });
        };
        timer = setInterval(tick, pollMs);
        const onVisible = () => {
            if (!document.hidden) fetch({ silent: true });
        };
        document.addEventListener('visibilitychange', onVisible);

        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisible);
            controller?.abort();
        };
    });

    return {
        get rows() {
            return rows;
        },
        get meta() {
            return meta;
        },
        get loading() {
            return loading;
        },
        get error() {
            return error;
        },
        get params() {
            return params;
        },
        get search() {
            return params.filter?.search ?? '';
        },
        /**
         * How many filters are actually applied, for the toolbar's filter button.
         * `search` has its own input and is excluded — counting it would light
         * the button up while the drawer showed nothing set.
         */
        get activeFilters() {
            return Object.entries(params.filter ?? {}).filter(
                ([key, value]) =>
                    key !== 'search' && value !== null && value !== undefined && value !== '',
            ).length;
        },
        get perPage() {
            return params.per_page;
        },
        get page() {
            return params.page;
        },
        get sort() {
            return params.sort ?? null;
        },
        setSearch,
        setFilters,
        setSort,
        apply,
        setPerPage,
        goToPage,
        toggleSort,
        sortDirection,
        reload,
        refresh,
    };
}
