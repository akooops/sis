<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Facility;
use App\Models\FacilitySlot;
use App\Models\Form;
use App\Models\Page;
use App\Services\Forms\FormPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The venues: a listing, a page each, and two things you can do from that page.
 *
 * THREE URLS PER VENUE, ONE FORM PER PAGE. /facilities/{slug} describes it,
 * /facilities/{slug}/contact carries the contact form and
 * /facilities/{slug}/reserve the booking wizard. That split is not cosmetic: the
 * public form renderer emits one payload per page — fixed script ids and a single
 * mount root — so two forms on one page would have meant generalising the forms
 * module, including keying old() and the error bag per form. Splitting the URLs
 * avoids all of it, and is the clearer site anyway, since somebody arrives
 * wanting to do one of the two things.
 */
class FacilitiesController extends SiteController
{
    public function __construct(protected FormPresenter $presenter) {}

    public function index(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'facilities')->firstOrFail();

        // Ordered, not dated: these are a hand-arranged list, and `order` is what
        // an admin drags.
        $facilities = Facility::query()->live()->with('media')->orderBy('order')->orderBy('name')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.facilities.index';

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code]),
            ]),
        ];

        return view('site::pages.facilities.index', [
            'page' => $page,
            'facilities' => $facilities,
            'seo' => $seo,
            'breadcrumbs' => [['label' => $title, 'url' => null]],
        ]);
    }

    /**
     * One venue: what it is, plus the news and albums attached to it.
     *
     * Both lists are narrowed to LIVE rows here rather than in the view: an admin
     * can attach a draft article in anticipation of publishing it, and it must not
     * appear until it is live.
     */
    public function show(Request $request): View
    {
        $locale = $this->site()->locale();
        $facility = $this->facility($request);

        $facility->load([
            'articles' => fn ($query) => $query->live()->with('media')->latest('published_at'),
            'albums' => fn ($query) => $query->live()->with('media')->latest('published_at'),
        ]);

        $title = $facility->getTranslation('title', $locale, true) ?: $facility->name;

        return view('site::pages.facilities.show', [
            'facility' => $facility,
            'seo' => $this->seo($facility, 'web.site.facilities.show', $title, 'article'),
            'breadcrumbs' => $this->trail($title, null),
        ]);
    }

    /**
     * The contact form for one venue.
     *
     * ZERO JAVASCRIPT OF ITS OWN. The venue is known server-side, so `facility_id`
     * is an ordinary preset and the standard `data-sisf` marker lets site/forms.js
     * mount it exactly as it does on /inquiries.
     */
    public function contact(Request $request): View
    {
        $locale = $this->site()->locale();
        $facility = $this->facility($request);

        $title = $facility->getTranslation('title', $locale, true) ?: $facility->name;

        return view('site::pages.facilities.contact', [
            'facility' => $facility,
            'seo' => $this->seo($facility, 'web.site.facilities.contact', __('facilities.contact.title', ['venue' => $title]), 'website'),
            'breadcrumbs' => $this->trail($title, __('facilities.contact.crumb'), $facility),
        ] + $this->embed($request, config('facilities.contact_form'), $locale, $facility));
    }

    /**
     * The booking wizard for one venue: pick a time, then your details.
     *
     * `marker => false` in the view, because this page mounts the renderer itself
     * once a time has been picked — which is the one thing the server cannot
     * preset, since it happens in the browser after the page has rendered.
     */
    public function reserve(Request $request): View
    {
        $locale = $this->site()->locale();
        $facility = $this->facility($request);

        $title = $facility->getTranslation('title', $locale, true) ?: $facility->name;

        return view('site::pages.facilities.reserve', [
            'facility' => $facility,
            'seo' => $this->seo($facility, 'web.site.facilities.reserve', __('facilities.reserve.title', ['venue' => $title]), 'website'),
            'breadcrumbs' => $this->trail($title, __('facilities.reserve.crumb'), $facility),
        ] + $this->embed($request, config('facilities.reservation_form'), $locale, $facility) + $this->chosen());
    }

    /**
     * THE MONTH FEED the booking calendar reads.
     *
     * A calendar shows a month and a month is not a page, so this is unpaginated
     * and bounded by the window instead. NOTHING PERSONAL CROSSES IT: a time, a
     * limit, how many places are left and which of the four states the slot is in.
     * Who booked them is never part of the question a calendar asks.
     */
    public function slots(Request $request): JsonResponse
    {
        $facility = Facility::query()->live()->where('slug', $request->route('slug'))->first();

        if (! $facility) {
            return response()->json(['status' => 'error', 'message' => 'Not found', 'data' => []], 404);
        }

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $start = CarbonImmutable::parse($validated['start']);
        $end = CarbonImmutable::parse($validated['end']);
        $max = (int) config('facilities.feed_max_days', 62);

        if ($start->diffInDays($end) > $max) {
            $end = $start->addDays($max);
        }

        // Never earlier than now: a past time is not bookable, and shipping it
        // only to grey it out tells a visitor about times they cannot have.
        $from = $start->isPast() ? CarbonImmutable::now() : $start;

        $slots = FacilitySlot::query()
            // Eager-loaded so reservedCount() reads the collection instead of
            // firing a count per slot.
            ->with('reservations')
            ->where('facility_id', $facility->id)
            ->where('is_open', true)
            ->inRange($from, $end)
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'data' => $slots->map(fn (FacilitySlot $slot) => [
                'id' => $slot->id,
                'start' => $slot->starts_at->toIso8601String(),
                'end' => $slot->ends_at->toIso8601String(),
                'state' => $slot->state(),
                'remaining' => $slot->remaining(),
                'capacity' => (int) $slot->capacity,
            ])->all(),
        ]);
    }

    /**
     * The venue this request is about.
     *
     * BY NAME, NEVER AS A METHOD ARGUMENT. Laravel resolves non-class controller
     * parameters POSITIONALLY, so on the prefixed /{locale}/facilities/{slug} a
     * `show(string $slug)` would be handed the LOCALE — and the unprefixed twin
     * would work fine, which makes the bug look locale-specific when it is not.
     */
    protected function facility(Request $request): Facility
    {
        return Facility::query()->live()->with('media')->where('slug', $request->route('slug'))->firstOrFail();
    }

    /**
     * One form on one venue's page.
     *
     * `facility_id` is a PRESET rather than something the browser fills in,
     * because the page is the venue and the server already knows which. It is
     * still a hidden input and therefore tamperable, which is why
     * FacilityReservationIsAllowed re-reads it at submit and the projector reads
     * the venue off the SLOT afterwards — this only saves an honest visitor a step.
     *
     * @return array<string, mixed>
     */
    protected function embed(Request $request, string $slug, string $locale, Facility $facility): array
    {
        $form = Form::query()->live()->where('slug', $slug)->first();

        // Before the state check: the submission that just succeeded may be the
        // one that hit the form's cap, and the person who sent it must read their
        // confirmation rather than "no longer accepting responses".
        $submitted = $form !== null && session('sisf_submitted') === $form->id;

        $state = match (true) {
            ! $form => null,
            $submitted => 'ok',
            default => $this->presenter->state($request, $form),
        };

        return [
            'form' => $form,
            'notice' => $state === 'ok' ? null : $state,
            'presentation' => ($state === 'ok' && ! $submitted) ? $this->presenter->present($form, $locale) : null,
            'presets' => [config('facilities.facility_field') => $facility->id],
        ];
    }

    /**
     * The time a RETURNING visitor already picked, resolved server-side.
     *
     * A rejected submit comes back as a fresh page load, so the browser has
     * forgotten which time was chosen — but old() has not. Reading it here means
     * the card header is rendered by Blade with the real date in the visitor's own
     * language, rather than the page having to keep that state in sessionStorage.
     *
     * Re-read from the database rather than echoed back, so a slot deleted between
     * the two requests resolves to null and the header simply claims nothing.
     *
     * @return array<string, mixed>
     */
    protected function chosen(): array
    {
        $slotId = old('fields', [])[config('facilities.slot_field')] ?? null;

        return [
            'chosenSlot' => is_string($slotId) && $slotId !== '' ? FacilitySlot::find($slotId) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function seo(Facility $facility, string $routeName, string $title, string $type): array
    {
        $locale = $this->site()->locale();
        $parameters = ['slug' => $facility->slug];

        return [
            'title' => $title,
            'description' => $facility->getTranslation('description', $locale, true),
            'image' => $facility->thumbnail_url,
            'canonical' => route($routeName, $parameters),
            'robots' => 'index,follow',
            'type' => $type,
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $parameters),
            ]),
        ];
    }

    /**
     * Facilities → this venue → (this action).
     *
     * The venue's own crumb is a LINK on the two sub-pages and plain text on its
     * own, which is what tells a reader where they are without reading the URL.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function trail(string $title, ?string $leaf, ?Facility $facility = null): array
    {
        $trail = [['label' => __('site.breadcrumbs.facilities'), 'url' => route('web.site.facilities.index')]];

        $trail[] = [
            'label' => $title,
            'url' => $leaf === null ? null : route('web.site.facilities.show', ['slug' => $facility?->slug]),
        ];

        if ($leaf !== null) {
            $trail[] = ['label' => $leaf, 'url' => null];
        }

        return $trail;
    }
}
