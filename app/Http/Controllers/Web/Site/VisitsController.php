<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Form;
use App\Models\Page;
use App\Models\VisitService;
use App\Models\VisitSlot;
use App\Services\Forms\FormPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitsController extends SiteController
{
    public function __construct(protected FormPresenter $presenter) {}

    /**
     * ONE PAGE FOR THE WHOLE FLOW.
     *
     * The card grid, the calendar and the form all live on /visits, and the
     * booking panel below them is revealed rather than navigated to — the same
     * shape the jobs page uses for Apply, and the same shape the old site had.
     * There is deliberately no /visits/{slug}: a visit service has a paragraph and
     * a photograph, which is a card and a popup, not a page.
     */
    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'visits')->firstOrFail();

        // Ordered, not dated: these are a hand-arranged list, and `order` is what
        // an admin drags. Live but NOT ->bookable(): a visit with no times left
        // still describes what the school offers, and hiding it would make the
        // page look broken between terms. Its card says there are no times.
        $services = VisitService::query()->live()->with('media')->orderBy('order')->orderBy('name')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.visits.index';

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

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.visits.index', [
            'page' => $page,
            'services' => $services,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ] + $this->booking($request, $locale));
    }

    /**
     * THE MONTH FEED the public calendar reads.
     *
     * A calendar shows a month and a month is not a page, so this is unpaginated
     * and bounded by the window instead — the same contract the admin feed keeps.
     *
     * NOTHING PERSONAL CROSSES THIS ENDPOINT. It answers with a time, a limit, how
     * many seats are left and which of the four states the slot is in; who booked
     * them is never part of the question a calendar asks.
     */
    public function slots(Request $request): JsonResponse
    {
        // By name, not as an argument — the prefixed twin of this route has
        // {locale} first and Laravel binds non-class parameters POSITIONALLY, so a
        // slots(string $service) would be handed the locale on every Arabic page.
        $slug = $request->route('service');

        $service = VisitService::query()->live()->where('slug', $slug)->first();

        if (! $service) {
            return response()->json(['status' => 'error', 'message' => 'Not found', 'data' => []], 404);
        }

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $start = CarbonImmutable::parse($validated['start']);
        $end = CarbonImmutable::parse($validated['end']);
        $max = (int) config('visits.feed_max_days', 62);

        /*
         * Never earlier than now: a past slot is not bookable, and shipping one
         * only to grey it out tells a visitor about times they cannot have. A
         * calendar showing the current month always asks for a start in the past,
         * so this is the normal case rather than an edge one.
         */
        $from = $start->isPast() ? CarbonImmutable::now() : $start;

        /*
         * CLAMPED FROM THE EFFECTIVE START, not the requested one.
         *
         * Clamping `$end` against `$start` before moving `$from` forward inverted
         * the window on any wide request — asked for a year from January, the feed
         * narrowed the end to March, then moved the start to today, and answered a
         * range that ends before it begins. Which is zero slots, no error, and a
         * calendar that simply looks empty.
         */
        if ($from->diffInDays($end) > $max) {
            $end = $from->addDays($max);
        }

        $slots = VisitSlot::query()
            // Eager-loaded so reservedCount() reads the collection instead of
            // firing a count per slot — a month of times would otherwise be a
            // query each.
            ->with('reservations')
            ->where('visit_service_id', $service->id)
            ->where('is_open', true)
            ->inRange($from, $end)
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => '',
            'data' => $slots->map(fn (VisitSlot $slot) => [
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
     * The booking form, rendered through the ordinary form renderer.
     *
     * Which is what brings the captcha, the honeypot, the country and IP blocks,
     * the per-visitor cap, the repeatable students group and nine locales with it.
     * Nothing here reimplements any of that — the old booking flow posted JSON to
     * a bespoke endpoint and had none of it.
     *
     * NO PRESETS. The jobs page knows its posting server-side and can fill the
     * hidden field here; this page cannot, because which visit and which time are
     * chosen in the browser after the page has rendered. site/visits.js mounts the
     * renderer with them as initial VALUES instead, the same seam the CV chooser
     * uses to hand it parsed answers. They are re-read at submit and again in the
     * projector, so nothing rests on the browser having been honest.
     *
     * @return array<string, mixed>
     */
    protected function booking(Request $request, string $locale): array
    {
        $form = Form::query()->live()->where('slug', config('visits.form'))->first();

        $state = match (true) {
            ! $form => null,
            $this->presenter->state($request, $form) !== 'ok' => $this->presenter->state($request, $form),
            default => 'ok',
        };

        return [
            'form' => $form,
            'notice' => $state === 'ok' ? null : $state,
            'presentation' => $state === 'ok' ? $this->presenter->present($form, $locale) : null,
        ] + $this->chosen();
    }

    /**
     * What a RETURNING visitor already picked, resolved server-side.
     *
     * A rejected submit comes back as a fresh page load, so the browser has
     * forgotten which visit and which time were chosen — but old() has not. Reading
     * them here means the summary above the form is rendered by Blade with the real
     * date in the visitor's own language, rather than the page having to keep that
     * state in sessionStorage and hope the tab survived.
     *
     * Both are re-read from the database rather than echoed back, so a slot deleted
     * or a visit hidden between the two requests resolves to null and the summary
     * simply does not claim anything.
     *
     * @return array<string, mixed>
     */
    protected function chosen(): array
    {
        $answers = old('fields', []);

        $slotId = $answers[config('visits.slot_field')] ?? null;
        $serviceId = $answers[config('visits.service_field')] ?? null;
        $visitors = (int) ($answers[config('visits.visitors_field')] ?? 0);

        $slot = is_string($slotId) && $slotId !== '' ? VisitSlot::find($slotId) : null;
        $service = is_string($serviceId) && $serviceId !== '' ? VisitService::query()->live()->find($serviceId) : null;

        return [
            'chosenSlot' => $slot,
            'chosenService' => $service,
            'chosenVisitors' => max(1, $visitors),
        ];
    }
}
