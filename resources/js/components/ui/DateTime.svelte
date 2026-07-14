<script>
    /**
     * DateTime — render an ISO/UTC timestamp in the browser's local timezone via
     * the shared date formatters. Reactive to locale changes.
     *
     *   <DateTime value={row.created_at} />                // datetime (default)
     *   <DateTime value={row.created_at} format="relative" />
     */
    import { formatDate, formatDateTime, formatTime, formatRelative } from '@/lib/date';
    import { locale } from '@/lib/i18n';

    let { value, format = 'datetime', fallback = '—' } = $props();

    const formatters = {
        date: formatDate,
        datetime: formatDateTime,
        time: formatTime,
        relative: formatRelative,
    };

    // Depend on $locale so the display re-formats when the app locale changes.
    const text = $derived((formatters[format] ?? formatDateTime)(value, { fallback, locale: $locale }));
</script>

<time datetime={value ?? undefined} title={formatDateTime(value, { fallback, locale: $locale })}>{text}</time>
