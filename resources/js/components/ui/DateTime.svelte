<script>
    /**
     * DateTime — render an ISO/UTC timestamp in the browser's local timezone via
     * the shared date formatters.
     *
     *   <DateTime value={row.created_at} />                // datetime (default)
     *   <DateTime value={row.created_at} format="relative" />
     */
    import { formatDate, formatDateTime, formatTime, formatRelative } from '@/lib/date';

    let { value, format = 'datetime', fallback = '—' } = $props();

    const formatters = {
        date: formatDate,
        datetime: formatDateTime,
        time: formatTime,
        relative: formatRelative,
    };

    const text = $derived((formatters[format] ?? formatDateTime)(value, { fallback, locale: 'en' }));
</script>

<time datetime={value ?? undefined} title={formatDateTime(value, { fallback, locale: 'en' })}>{text}</time>
