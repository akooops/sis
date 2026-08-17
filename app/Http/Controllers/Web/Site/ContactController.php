<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\ContactDetail;
use App\Models\Form;
use App\Models\Page;
use App\Services\Forms\FormPresentation;
use App\Services\Forms\FormPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contact and admissions — two pages that embed a seeded system form.
 *
 * The old site had bespoke ContactSubmission and Inquiry models with their own
 * controllers, validation and admin screens. This app already has a form
 * builder, so those two forms are seeded as is_system rows (config/forms.php)
 * and rendered by the ordinary renderer: one submission pipeline, one spam
 * filter, one export, one notification route.
 *
 * WHERE THE POST GOES. The renderer posts to the UNCHANGED
 * /forms/{locale}/{slug} endpoint. On a validation failure that endpoint calls
 * back(), whose previous URL is /{locale}/contact — so the withErrors and
 * old-input round-trip lands the visitor back here with their answers intact,
 * with no change to the submit pipeline at all. That is what makes embedding
 * safe rather than a fork.
 *
 * THE FORM IS OPTIONAL on both pages. If the seeded row has been unpublished, or
 * the visitor is blocked, or the install never seeded it, the page still renders
 * its copy — a missing form must not 404 the school's contact page. When there
 * is a REASON the form is absent, the page says so: `notice` carries it and
 * site::partials.forms.embed draws the alert.
 *
 * AND THERE IS NO CONFIRMATION URL. A successful submit comes back here, and the
 * same partial swaps the form for the confirmation message — which is what makes
 * these two pages and a standalone form behave identically.
 */
class ContactController extends SiteController
{
    public function __construct(protected FormPresenter $presenter) {}

    public function contact(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'contact')->firstOrFail();

        $form = Form::query()->live()->where('slug', 'contact')->first();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.contact';

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

        $address = $this->site()->contacts()->firstWhere('type', ContactDetail::ADDRESS_TYPE);

        $embed = $this->embed($request, $form, $locale);

        return view('site::pages.contact', [
            'page' => $page,
            'form' => $embed['form'],
            'presentation' => $embed['presentation'],
            'notice' => $embed['notice'],
            // The three channels the page prints beside the map. Phones and
            // WhatsApp numbers share one column, which is why they arrive joined.
            'address' => $address,
            'emails' => $this->site()->contacts()->where('type', 'email')->values(),
            'phones' => $this->site()->contacts()->whereIn('type', ['phone', 'whatsapp'])->values(),
            /*
             * A keyless embed built from the address row's own coordinates. The
             * old site stored a whole pasted `google_maps_embed_url` setting; the
             * ContactDetail module already models lat/lng properly, and this URL
             * form needs no API key.
             */
            'mapEmbed' => ($address?->latitude !== null && $address?->longitude !== null)
                ? 'https://maps.google.com/maps?q='.$address->latitude.','.$address->longitude.'&z=16&output=embed'
                : null,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function inquiries(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'inquiries')->firstOrFail();

        $form = Form::query()->live()->where('slug', 'inquiries')->first();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.inquiries';

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

        $embed = $this->embed($request, $form, $locale);

        return view('site::pages.inquiries', [
            'page' => $page,
            'form' => $embed['form'],
            'presentation' => $embed['presentation'],
            'notice' => $embed['notice'],
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * The renderer payload, and any reason there is none.
     *
     * Exactly what Web\Site\FormsController computes for a standalone form, in
     * the same order and for the same reasons — both feed the one
     * site::partials.forms.embed, so a form behaves identically wherever it is
     * read. A visitor who may not submit gets the page plus an alert saying so,
     * rather than a page the form has silently vanished from.
     *
     * @return array{form: ?Form, presentation: ?FormPresentation, notice: ?string}
     */
    protected function embed(Request $request, ?Form $form, string $locale): array
    {
        if ($form === null) {
            return ['form' => null, 'presentation' => null, 'notice' => null];
        }

        // Before the state check: the submission that just succeeded may be the
        // one that hit the form's cap, and the person who sent it must read
        // their confirmation rather than "no longer accepting responses".
        $submitted = session('sisf_submitted') === $form->id;

        $state = $submitted ? 'ok' : $this->presenter->state($request, $form);

        // submit() already returned the same sentence in the error bag; the
        // renderer prints that above the form, so a notice would say it twice.
        $hasFormError = session('errors')?->getBag('default')->has('form') ?? false;

        return [
            'form' => $form,
            'presentation' => ($state === 'ok' && ! $submitted)
                ? $this->presenter->present($form, $locale)
                : null,
            'notice' => ($state === 'ok' || $hasFormError) ? null : $state,
        ];
    }
}
