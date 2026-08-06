/**
 * Chart palette, theme plumbing and ApexCharts defaults.
 *
 * The admin runs in light OR dark (`<html class="dark">`, toggled by KTUI's
 * theme switch), and a chart is SVG: it does not inherit the page's colours the
 * way a `kt-*` component does. So every colour a chart draws comes from here,
 * picked per mode rather than flipped automatically — a light hue dimmed for a
 * dark surface is a different colour, not the same one darker.
 *
 * The hexes are the validated reference palette: the categorical slots pass the
 * colour-blindness and normal-vision separation gates as ORDERED PAIRS in both
 * modes, and the status trio is the fixed good/warning/critical set. The one
 * knowingly-relieved value is `warning` on a light surface (1.83:1) — it is a
 * status colour, so it never travels without its legend label, and the one card
 * that stacks it also ships a table view.
 *
 * Nothing here reads a CSS custom property. Metronic's tokens are `oklch()`,
 * which an SVG `fill` only understands in recent browsers, and a chart that
 * silently paints black on an older one is worse than a chart that ignores the
 * token.
 */

/** Fixed, never themed. Reserved for state — never reused as "series 4". */
export const CHART_STATUS = {
    good: '#0ca30c',
    warning: '#fab219',
    serious: '#ec835a',
    critical: '#d03b3b',
};

/** Categorical slots, stepped per surface. Assigned in order, never cycled. */
export const CHART_SERIES = {
    light: { blue: '#2a78d6', orange: '#eb6834', aqua: '#1baf7a', violet: '#4a3aa7' },
    dark: { blue: '#3987e5', orange: '#d95926', aqua: '#199e70', violet: '#9085e9' },
};

/** Grid, axis and label ink. Recessive by design — the marks carry the chart. */
export const CHART_INK = {
    light: { text: '#52514e', muted: '#898781', grid: '#e1e0d9', axis: '#c3c2b7' },
    dark: { text: '#c3c2b7', muted: '#898781', grid: '#2c2c2a', axis: '#383835' },
};

export function isDarkTheme() {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
}

/**
 * Call `onChange` whenever the theme flips.
 *
 * KTUI's switch adds/removes the class on <html> with no event of its own, so
 * the class attribute IS the signal. Returns an unsubscribe, which is what an
 * `$effect` wants back.
 */
export function watchTheme(onChange) {
    if (typeof MutationObserver === 'undefined') return () => {};

    let current = isDarkTheme();

    const observer = new MutationObserver(() => {
        const next = isDarkTheme();
        if (next !== current) {
            current = next;
            onChange(next);
        }
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    return () => observer.disconnect();
}

/** The colours for one mode, in one object, so a component asks once. */
export function palette(dark) {
    return {
        ink: dark ? CHART_INK.dark : CHART_INK.light,
        series: dark ? CHART_SERIES.dark : CHART_SERIES.light,
        status: CHART_STATUS,
    };
}

/**
 * The house style every chart starts from: no toolbar (it offers a PNG export
 * of a chart the page can already export as data), no data label on every mark,
 * hairline grid, and the tooltip theme that matches the surface.
 */
export function baseOptions(dark) {
    const { ink } = palette(dark);

    return {
        chart: {
            fontFamily: 'inherit',
            foreColor: ink.muted,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 350 },
            parentHeightOffset: 0,
        },
        dataLabels: { enabled: false },
        // Apex paints bars at 0.85 opacity by default, which washes every hex
        // toward the surface — the palette was validated at full strength, so
        // the marks have to be drawn at full strength.
        fill: { opacity: 1 },
        grid: {
            borderColor: ink.grid,
            strokeDashArray: 0,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 0, right: 8, bottom: 0, left: 8 },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            fontSize: '12px',
            // v4 names this `size` (the old width/height/radius trio is gone).
            markers: { size: 7, shape: 'square', strokeWidth: 0 },
            itemMargin: { horizontal: 10, vertical: 4 },
            labels: { colors: ink.text },
        },
        stroke: { curve: 'smooth', width: 2, lineCap: 'round' },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            style: { fontSize: '12px' },
            marker: { show: true },
        },
        xaxis: {
            axisBorder: { color: ink.axis },
            axisTicks: { color: ink.axis },
            labels: { style: { colors: ink.muted, fontSize: '11px' } },
            tooltip: { enabled: false },
        },
        yaxis: {
            labels: { style: { colors: ink.muted, fontSize: '11px' } },
        },
        states: {
            hover: { filter: { type: 'lighten' } },
            active: { filter: { type: 'none' } },
        },
        noData: {
            text: 'No data',
            style: { color: ink.muted, fontSize: '13px' },
        },
    };
}

/**
 * Deep-merge chart options. Arrays REPLACE rather than concatenate — merging
 * `colors` or `series` element-wise would silently blend two palettes.
 */
export function mergeOptions(base, extra) {
    const out = { ...base };

    for (const [key, value] of Object.entries(extra ?? {})) {
        const current = out[key];

        out[key] =
            value && typeof value === 'object' && !Array.isArray(value) && current && typeof current === 'object' && !Array.isArray(current)
                ? mergeOptions(current, value)
                : value;
    }

    return out;
}

/* ------------------------------------------------------------------ */
/* Formatters — shared by the tiles, the tables and the tooltips.      */
/* ------------------------------------------------------------------ */

const NUMBER = new Intl.NumberFormat('en');

export function formatNumber(value) {
    return value === null || value === undefined ? '—' : NUMBER.format(value);
}

/** A percentage, already 0–100 from the server. Null means "nothing to divide". */
export function formatPercent(value, digits = 1) {
    return value === null || value === undefined ? '—' : `${Number(value).toFixed(digits)}%`;
}

/** Seconds as the shortest thing a human reads: 45s, 1m 21s, 1h 04m. */
export function formatDuration(seconds) {
    if (seconds === null || seconds === undefined) return '—';

    const total = Math.max(0, Math.round(Number(seconds)));

    if (total < 60) return `${total}s`;

    const minutes = Math.floor(total / 60);
    const rest = total % 60;

    if (minutes < 60) return rest ? `${minutes}m ${rest}s` : `${minutes}m`;

    return `${Math.floor(minutes / 60)}h ${String(minutes % 60).padStart(2, '0')}m`;
}

/** Milliseconds, kept in ms below a second so a 400ms focus does not read "0s". */
export function formatMs(ms) {
    if (ms === null || ms === undefined) return '—';

    const value = Math.max(0, Math.round(Number(ms)));

    return value < 1000 ? `${value}ms` : formatDuration(value / 1000);
}
