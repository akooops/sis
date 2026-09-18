<?php

namespace App\Services\Analytics;

use App\Enums\MorphType;
use App\Models\Language;
use App\Services\Site\SiteContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Throwable;

/**
 * Every number on the admin dashboard, in one call.
 *
 * EVERY NUMBER HERE IS COMPUTED BY MySQL. Nothing loads a page view and nothing
 * iterates a result set of rows - the only models ever instantiated are the
 * handful needed to turn the top ten content slugs into titles.
 *
 * TWO HALVES, ONE RANGE. `traffic` reads site_page_views, the table this module
 * writes. `activity` reads form_submissions, job_applications, visit_reservations
 * and facility_reservations - tables that already exist, with no new storage and
 * no new writes anywhere. Both halves answer for the same dates, which is the
 * whole reason they are one endpoint: a dashboard whose cards arrive at
 * different times shows two different truths at once.
 *
 * ACTIVITY IS PERMISSION-GATED PER SECTION, SERVER-SIDE. A section the caller
 * may not read is absent from the payload, not merely hidden in Svelte - which
 * is what stops a screener without job-applications.index reading the hiring
 * pipeline off the admin landing page.
 */
class DashboardMetrics
{
    /** Rows in any top-N breakdown. */
    protected int $topN = 10;

    /** So an all-time range on a long-retained table cannot explode the payload. */
    protected int $maxBuckets = 400;

    /**
     * @param  array{from: ?Carbon, to: ?Carbon}  $filters
     * @param  callable(string): bool  $can  permission check, injected so this
     *                                       class never touches auth itself
     */
    public function for(array $filters, callable $can): array
    {
        $totals = $this->totals($filters);

        return [
            'range' => [
                'from' => $filters['from']?->toIso8601String(),
                'to' => $filters['to']?->toIso8601String(),
                'first_at' => $totals['first_at'],
                'last_at' => $totals['last_at'],
            ],
            'traffic' => [
                'totals' => $totals,
                'bot_views' => $this->bots($filters),
                'timeline' => $this->timeline($filters, $totals),
                'top_content' => $this->topContent($filters),
                'entry_points' => $this->split($filters, 'entry_point'),
                'referrers' => $this->referrers($filters),
                'campaigns' => $this->campaigns($filters),
                'devices' => $this->split($filters, 'device_type'),
                'browsers' => $this->split($filters, 'browser', $this->topN),
                'systems' => $this->split($filters, 'os', $this->topN),
                'locales' => $this->split($filters, 'locale'),
                'countries' => $this->countries($filters),
            ],
            'activity' => $this->activity($filters, $can),
            'retention_days' => (int) config('analytics.retention_days', 365),
        ];
    }

    /*
     * ----------------------------------------------------------------------
     * Traffic
     * ----------------------------------------------------------------------
     */

    /**
     * The shared population: this range, humans only.
     *
     * `is_bot = 0` is baked in here rather than added per method so that no
     * future caller can forget it and quietly publish crawler traffic as
     * readership.
     */
    protected function base(array $f): Builder
    {
        $query = DB::table('site_page_views as v')->where('v.is_bot', 0);

        if ($f['from']) {
            $query->where('v.viewed_at', '>=', $f['from']);
        }

        if ($f['to']) {
            $query->where('v.viewed_at', '<=', $f['to']);
        }

        return $query;
    }

    /**
     * The tiles, in one pass.
     *
     * min/max(viewed_at) ride along so the timeline can pick its bucket without
     * a second scan of the same rows.
     */
    protected function totals(array $f): array
    {
        $row = $this->base($f)->selectRaw(<<<'SQL'
            count(*) as page_views,
            count(distinct v.visit_key) as visits,
            count(distinct v.visitor_key) as visitors,
            min(v.viewed_at) as first_at,
            max(v.viewed_at) as last_at
        SQL)->first();

        $views = (int) ($row->page_views ?? 0);
        $visits = (int) ($row->visits ?? 0);

        return [
            'page_views' => $views,
            'visits' => $visits,
            'visitors' => (int) ($row->visitors ?? 0),
            // Guarded rather than rounded from a division: no visits means there
            // is nothing to average, and 0.0 would read as "everyone bounced".
            'pages_per_visit' => $visits > 0 ? round($views / $visits, 2) : null,
            'first_at' => $row->first_at ?? null,
            'last_at' => $row->last_at ?? null,
        ];
    }

    /**
     * Crawler traffic, as one number for the footnote.
     *
     * A separate query rather than a CASE WHEN threaded through every aggregate
     * above: it is a different population and it is one number.
     */
    protected function bots(array $f): int
    {
        $query = DB::table('site_page_views as v')->where('v.is_bot', 1);

        if ($f['from']) {
            $query->where('v.viewed_at', '>=', $f['from']);
        }

        if ($f['to']) {
            $query->where('v.viewed_at', '<=', $f['to']);
        }

        return (int) $query->count();
    }

    /**
     * Page views and visits per bucket, with the empty buckets filled in.
     *
     * The bucket adapts to the span - day up to two months, week up to about a
     * year, month beyond - exactly as SubmissionAnalytics does, so the two
     * dashboards read the same way.
     */
    protected function timeline(array $f, array $totals): array
    {
        if (! $totals['first_at']) {
            return [];
        }

        $first = Carbon::parse($totals['first_at']);
        $last = Carbon::parse($totals['last_at']);

        $start = ($f['from'] && $f['from']->gt($first) ? $f['from'] : $first)->copy()->startOfDay();
        $end = ($f['to'] && $f['to']->lt($last) ? $f['to'] : $last)->copy()->endOfDay();

        $days = $start->diffInDays($end) + 1;

        [$unit, $expression] = match (true) {
            $days <= 62 => ['day', 'date(v.viewed_at)'],
            $days <= 400 => ['week', 'date(v.viewed_at - interval weekday(v.viewed_at) day)'],
            default => ['month', "date_format(v.viewed_at, '%Y-%m-01')"],
        };

        $rows = $this->base($f)
            ->selectRaw($expression.' as bucket, count(*) as page_views, count(distinct v.visit_key) as visits')
            ->groupByRaw('bucket')
            ->orderByRaw('bucket')
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        /*
         * Gap-filled in PHP because SQL cannot invent a row for a day nobody
         * visited, and a line that simply skips those days lies about the shape
         * of the traffic.
         */
        $out = [];
        $cursor = $this->floor($start, $unit);

        while ($cursor->lte($end) && count($out) < $this->maxBuckets) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $out[] = [
                'date' => $key,
                'unit' => $unit,
                'page_views' => (int) ($row->page_views ?? 0),
                'visits' => (int) ($row->visits ?? 0),
            ];

            $cursor = match ($unit) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $out;
    }

    /** The start of the bucket a date falls in. */
    protected function floor(Carbon $date, string $unit): Carbon
    {
        return match ($unit) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            default => $date->copy()->startOfMonth(),
        };
    }

    /**
     * A one-column breakdown.
     *
     * GROUP BY THE EXPRESSION, NEVER THE ALIAS. `device_type` is also a real
     * column and MySQL resolves a GROUP BY name to the column first - which
     * files NULL and '' as two separate rows both labelled "unknown".
     */
    protected function split(array $f, string $column, ?int $limit = null): array
    {
        $expression = "coalesce(nullif(v.{$column}, ''), 'unknown')";

        return $this->base($f)
            ->selectRaw($expression.' as label, count(*) as page_views, count(distinct v.visit_key) as visits')
            /*
             * GROUPED BY THE ALIAS, NOT BY REPEATING THE EXPRESSION. Under
             * ONLY_FULL_GROUP_BY, production MySQL refused the repeated
             * expression with 1055 "v.entry_point isn't in GROUP BY" — it did not
             * treat the two copies as the same thing — while the dev server
             * accepted it, so this only failed after deploying. An alias is
             * unambiguous. It is safe here because `label` is not a column on
             * site_page_views; grouping on an alias that IS a column would make
             * MySQL resolve the name to the column and split NULL from ''.
             */
            ->groupBy('label')
            ->orderByRaw('visits desc')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'page_views' => (int) $row->page_views,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /**
     * Where visits came from, by host.
     *
     * A plain GROUP BY, because Acquisition::host() normalised the host when the
     * row was written. SubmissionAnalytics has to unpick one out of a raw URL in
     * SQL on every load because form_submissions only stores the full referrer.
     *
     * Direct traffic is excluded: it already has its own bar in the entry-point
     * chart, and repeating it here as the tallest "referrer" makes the chart
     * useless.
     */
    protected function referrers(array $f): array
    {
        return $this->base($f)
            ->whereNotNull('v.referrer_host')
            ->where('v.referrer_host', '<>', '')
            ->selectRaw('v.referrer_host as label, count(*) as page_views, count(distinct v.visit_key) as visits')
            ->groupBy('v.referrer_host')
            ->orderByRaw('visits desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'page_views' => (int) $row->page_views,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /** Tagged campaigns. A real column, so no JSON extraction and no null-string trap. */
    protected function campaigns(array $f): array
    {
        return $this->base($f)
            ->whereNotNull('v.utm_campaign')
            ->where('v.utm_campaign', '<>', '')
            ->selectRaw('v.utm_campaign as label, count(*) as page_views, count(distinct v.visit_key) as visits')
            ->groupBy('v.utm_campaign')
            ->orderByRaw('visits desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'page_views' => (int) $row->page_views,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /**
     * Where visitors are.
     *
     * max(c.name) rather than a bare c.name: MySQL 8's default sql_mode includes
     * ONLY_FULL_GROUP_BY and rejects the bare column. `countries.code` is unique,
     * so max() over the group IS the single value.
     *
     * `name` and not `title` - title is a translatable JSON column, so max() on
     * it would return a JSON document, and the admin is English-only anyway.
     *
     * LEFT JOIN so a code with no seeded row still counts, with a null name the
     * client falls back on.
     */
    protected function countries(array $f): array
    {
        return $this->base($f)
            ->leftJoin('countries as c', 'c.code', '=', 'v.country_code')
            ->whereNotNull('v.country_code')
            ->selectRaw('v.country_code as code, max(c.name) as name, count(*) as page_views, count(distinct v.visit_key) as visits')
            ->groupBy('v.country_code')
            ->orderByRaw('visits desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                'code' => (string) $row->code,
                'name' => $row->name !== null ? (string) $row->name : null,
                'page_views' => (int) $row->page_views,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /**
     * The most-read pages, as CONTENT rather than as URLs.
     *
     * "Page" here means what an administrator means by it: an article, a job
     * posting, a programme, a facility - not a path. The rows are grouped on the
     * normalised route name plus slug, so /contact, /en/contact and /ar/contact
     * are one line rather than three, and then the top ten are decorated with
     * real titles.
     */
    protected function topContent(array $f): array
    {
        $rows = $this->base($f)
            ->selectRaw('v.route_name as route_name, v.slug as slug, min(v.path) as path, count(*) as page_views, count(distinct v.visit_key) as visits')
            ->groupBy('v.route_name', 'v.slug')
            ->orderByRaw('page_views desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                'route_name' => (string) $row->route_name,
                'slug' => (string) $row->slug,
                'path' => (string) $row->path,
                'page_views' => (int) $row->page_views,
                'visits' => (int) $row->visits,
                'type' => null,
                'title' => null,
                'url' => null,
            ])
            ->all();

        return $this->decorate($rows);
    }

    /**
     * Turn (route_name, slug) into a type, a title and a public link.
     *
     * INVERTS SiteContext::LINK_ROUTES RATHER THAN RESTATING IT. That constant
     * already maps a MorphType alias to its route name, and it is the map an
     * editor's menu links resolve through - so adding a content type to the site
     * makes it show up here for free, and there is no second list to forget.
     *
     * One query per distinct model type in the top ten, which is two to four in
     * practice and can never exceed ten.
     */
    protected function decorate(array $rows): array
    {
        $types = $this->contentTypes();
        $locale = Language::defaultCode();

        // Collect the slugs we need, grouped by the model that owns them.
        $wanted = [];

        foreach ($rows as $row) {
            $alias = $types[$row['route_name']] ?? null;

            if ($alias !== null && $row['slug'] !== '') {
                $wanted[$alias][] = $row['slug'];
            }
        }

        $found = [];

        foreach ($wanted as $alias => $slugs) {
            $class = MorphType::classFor($alias);

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            $found[$alias] = $class::query()
                ->whereIn('slug', array_unique($slugs))
                ->get()
                ->keyBy('slug');
        }

        foreach ($rows as $index => $row) {
            $alias = $types[$row['route_name']] ?? null;
            $rows[$index]['type'] = $alias;
            $rows[$index]['url'] = $this->url($row['route_name'], $row['slug']);

            $model = $alias !== null ? ($found[$alias][$row['slug']] ?? null) : null;

            if ($model === null) {
                continue;
            }

            // Untranslated in the admin's language is a null the client words,
            // not a blank string that would render as an unlabelled row.
            $title = method_exists($model, 'getTranslation')
                ? $model->getTranslation('title', $locale, false)
                : ($model->title ?? null);

            $rows[$index]['title'] = is_string($title) && $title !== '' ? $title : null;
        }

        return $rows;
    }

    /**
     * Normalised route name => MorphType alias, from the one map that exists.
     *
     * Normalised through the SAME RouteName::normalise() the middleware used to
     * write route_name. Two copies of that regex would silently stop matching
     * the day one changed, and the symptom would be a top-content table with no
     * titles rather than an error.
     *
     * @return array<string, string>
     */
    protected function contentTypes(): array
    {
        $out = [];

        foreach (SiteContext::LINK_ROUTES as $alias => $route) {
            $name = RouteName::normalise($route);

            if ($name !== null) {
                $out[$name] = $alias;
            }
        }

        return $out;
    }

    /**
     * The public URL for a recorded page.
     *
     * THE ROOT NAME, NOT THE PREFIXED ONE. route('web.site.articles.show', ...)
     * THROWS "Missing required parameter" from an API request: SetLocale never
     * runs on /api, so URL::defaults(['locale' => ...]) is unset and the
     * {locale} placeholder cannot be filled. The unprefixed registration exists
     * precisely because it has no such parameter.
     *
     * Returns null rather than throwing - a link is chrome and must never 500 a
     * dashboard, the same rule SiteContext::url() follows.
     */
    protected function url(string $route, string $slug): ?string
    {
        $name = 'web.site.root.'.$route;

        if (! RouteFacade::has($name)) {
            return null;
        }

        try {
            return route($name, $slug === '' ? [] : ['slug' => $slug]);
        } catch (Throwable) {
            return null;
        }
    }

    /*
     * ----------------------------------------------------------------------
     * Activity
     * ----------------------------------------------------------------------
     */

    /**
     * What came in over the same range, from the tables that already hold it.
     *
     * NO NEW STORAGE AND NO NEW WRITES. These are four GROUP BY status queries
     * over form_submissions, job_applications, visit_reservations and
     * facility_reservations. All four use spatie/model-states, which stores the
     * state's own $name string in a plain `status` column, so grouping on it
     * raw is correct and no state object is ever instantiated.
     *
     * EACH SECTION IS GATED BEFORE IT IS COMPUTED. A caller without the matching
     * permission gets no key at all - not an empty one and not a hidden one -
     * so the numbers never reach a browser that may not see them.
     *
     * Each section ranges on the date that means "when this happened" for its
     * own domain: a reservation is booked_at, an application is applied_at, a
     * submission has only created_at.
     */
    protected function activity(array $f, callable $can): array
    {
        $sections = [
            'submissions' => ['form_submissions', 'created_at', 'form-submissions.index'],
            'applications' => ['job_applications', 'applied_at', 'job-applications.index'],
            'visit_reservations' => ['visit_reservations', 'booked_at', 'visit-reservations.index'],
            'facility_reservations' => ['facility_reservations', 'booked_at', 'facility-reservations.index'],
        ];

        $out = [];

        foreach ($sections as $key => [$table, $column, $permission]) {
            if (! $can($permission)) {
                continue;
            }

            $out[$key] = $this->statuses($f, $table, $column);
        }

        return $out;
    }

    /**
     * One domain's totals, broken down by status.
     *
     * @return array{total: int, statuses: array<int, array{status: string, total: int}>}
     */
    protected function statuses(array $f, string $table, string $column): array
    {
        $query = DB::table($table);

        if ($f['from']) {
            $query->where($column, '>=', $f['from']);
        }

        if ($f['to']) {
            $query->where($column, '<=', $f['to']);
        }

        $rows = $query
            // Grouped by the `label` alias, as the traffic splits are: repeating
            // the expression fails ONLY_FULL_GROUP_BY on production MySQL. Never
            // alias it `status` — that IS a column, and MySQL would resolve the
            // GROUP BY name to it.
            ->selectRaw("coalesce(nullif(`status`, ''), 'unknown') as label, count(*) as total")
            ->groupBy('label')
            ->orderByRaw('total desc')
            ->get();

        return [
            'total' => (int) $rows->sum('total'),
            'statuses' => $rows
                ->map(fn ($row) => ['status' => (string) $row->label, 'total' => (int) $row->total])
                ->all(),
        ];
    }
}
