<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Category;
use App\Models\Form;
use App\Models\JobOffer;
use App\Models\Page;
use App\Services\Forms\FormPresenter;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobsController extends SiteController
{
    public function __construct(protected FormPresenter $presenter) {}

    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'jobs')->firstOrFail();

        $search = trim((string) $request->query('search')) ?: null;
        $category = trim((string) $request->query('category')) ?: null;

        // The two enum-ish filters are whitelisted against the model's constants
        // BEFORE they reach the query: an unknown value becomes no filter, so a
        // hand-edited URL reads as the unfiltered listing rather than as "no
        // vacancies", and nothing unchecked is compared against a column.
        $employmentType = $this->among($request->query('employment_type'), JobOffer::EMPLOYMENT_TYPES);
        $workMode = $this->among($request->query('work_mode'), JobOffer::WORK_MODES);

        $jobs = JobOffer::query()->open()
            ->with(['category', 'media'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere("title->{$locale}", 'like', "%{$search}%")))
            ->when($category, fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $inner) => $inner->where('categories.code', $category),
            ))
            ->when($employmentType, fn (Builder $query) => $query->where('employment_type', $employmentType))
            ->when($workMode, fn (Builder $query) => $query->where('work_mode', $workMode))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        // Only ever offer a filter that returns something: a chip or a row that
        // leads to the empty state reads as a broken listing, not as a choice.
        $categories = Category::query()
            ->whereHas('jobOffers', fn (Builder $query) => $query->open())
            ->orderBy('name')
            ->get();

        $employmentTypes = $this->offered('employment_type', JobOffer::EMPLOYMENT_TYPES);
        $workModes = $this->offered('work_mode', JobOffer::WORK_MODES);

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.jobs.index';

        // Every filter rides in here, which is what puts it in the canonical, in
        // all nine hreflang alternates and — via withQueryString() above — on
        // page 2. It is also the noindex test: an unfiltered page 1 is the only
        // shape of this listing worth indexing, every filter combination being a
        // slice of it rather than a page of its own.
        $routeParameters = array_filter([
            'search' => $search,
            'category' => $category,
            'employment_type' => $employmentType,
            'work_mode' => $workMode,
            'page' => $jobs->currentPage() > 1 ? $jobs->currentPage() : null,
        ]);

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => $routeParameters === [] ? 'index,follow' : 'noindex,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.jobs.index', [
            'page' => $page,
            'jobs' => $jobs,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
            'employmentType' => $employmentType,
            'workMode' => $workMode,
            'employmentTypes' => $employmentTypes,
            'workModes' => $workModes,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * A query value narrowed to one the model declares, or null for no filter.
     *
     * is_string() rather than a cast because `?work_mode[]=remote` hands back an
     * array, and casting one to a string is a warning that HandleExceptions turns
     * into a 500 — on a filter whose whole contract is to shrug off bad input.
     *
     * @param  array<int, string>  $allowed
     */
    protected function among(mixed $value, array $allowed): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * The values OPEN postings actually use, in the order the model declares them.
     *
     * Two reasons the constant drives the order rather than the DB: the sidebar
     * then reads full-time → volunteer every time instead of following whatever
     * order rows come back in, and intersecting also drops a value no longer
     * declared — an old row holding one has no `jobs.employment_type.*` key to be
     * labelled with, so a chip for it would render blank.
     *
     * @param  array<int, string>  $declared
     * @return array<int, string>
     */
    protected function offered(string $column, array $declared): array
    {
        $used = JobOffer::query()->open()->distinct()->pluck($column)->all();

        return array_values(array_intersect($declared, $used));
    }

    public function show(Request $request): View
    {
        // By name, not as an argument — see PagesController::show().
        $slug = $request->route('slug');

        $locale = $this->site()->locale();

        $job = JobOffer::query()->live()->with(['category', 'media'])->where('slug', $slug)->firstOrFail();

        $title = $job->getTranslation('title', $locale, true) ?: $job->name;

        $routeName = 'web.site.jobs.show';
        $routeParameters = ['slug' => $job->slug];

        $seo = [
            'title' => $title,
            'description' => $job->getTranslation('description', $locale, true),
            'image' => $job->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.jobs'), 'url' => route('web.site.jobs.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.jobs.show', [
            'job' => $job,
            // `skills` is a ;;;-joined translatable STRING on this model, not an
            // array — splitSkills is the only correct reader.
            'skills' => JobOffer::splitSkills($job->skills),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ] + $this->application($request, $job, $locale));
    }

    /**
     * The apply form, bound to THIS posting.
     *
     * The seeded `job-application` form rendered through the ordinary form
     * renderer, which is what brings the captcha, the honeypot, the country and
     * IP blocks, the per-applicant cap, the multi-step navigation, the scanned
     * upload and nine locales with it. Nothing here reimplements any of that.
     *
     * `presets` fills the hidden `job_offer_id` so the submission knows what it
     * is against. It is a hidden input and therefore tamperable, which is why
     * ApplicationIsAllowed re-reads the posting at submit and ApplicationProjector
     * re-reads it again afterwards — this only saves the honest visitor a step.
     *
     * NULL PRESENTATION WHEN THE POSTING IS CLOSED, or when the form is missing
     * because nobody has seeded it: form-embed draws the notice and no form. A
     * closed posting still renders its description — people arrive from search
     * long after a vacancy fills, and a page that 404s tells them nothing.
     *
     * @return array<string, mixed>
     */
    protected function application(Request $request, JobOffer $job, string $locale): array
    {
        $form = Form::query()->live()->where('slug', config('jobs.form'))->first();

        $state = match (true) {
            ! $form => null,
            ! $job->isOpen() => 'closed',
            $this->presenter->state($request, $form) !== 'ok' => $this->presenter->state($request, $form),
            default => 'ok',
        };

        return [
            'form' => $form,
            'notice' => $state === 'ok' ? null : $state,
            'presentation' => $state === 'ok' ? $this->presenter->present($form, $locale) : null,
            'presets' => [config('jobs.job_offer_field') => $job->id],
        ];
    }
}
