<script>
    /**
     * ExportButton — client-side CSV export of the given rows.
     *   <ExportButton {rows} columns={[{key,label}]} filename="users" />
     */
    import Button from '@/components/ui/Button.svelte';

    let { rows = [], columns = [], filename = 'export' } = $props();

    function resolve(row, key) {
        // Support dotted keys (e.g. "preferred_supplier.name").
        return key.split('.').reduce((acc, part) => (acc == null ? '' : acc[part]), row) ?? '';
    }

    function escape(value) {
        const s = String(value ?? '');
        return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    }

    function exportCsv() {
        const header = columns.map((c) => escape(c.label ?? c.key)).join(',');
        const body = rows.map((row) => columns.map((c) => escape(resolve(row, c.key))).join(',')).join('\n');
        const csv = `${header}\n${body}`;

        const blob = new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${filename}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    }
</script>

<Button variant="outline" size="sm" onclick={exportCsv} disabled={!rows.length}>
    <i class="ki-filled ki-exit-down"></i>
    Export
</Button>
