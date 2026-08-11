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
 * its copy — a missing form must not 404 the school's contact page.
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

        return view('site::pages.contact', [
            'page' => $page,
            'presentation' => $this->presentation($request, $form, $locale),
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

        return view('site::pages.inquiries', [
            'page' => $page,
            'presentation' => $this->presentation($request, $form, $locale),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * The renderer payload, or null when the form must not be shown.
     *
     * Same guards as the standalone form page: a visitor who may not submit is
     * shown the page WITHOUT the form rather than a rendered dead end.
     */
    protected function presentation(Request $request, ?Form $form, string $locale): ?FormPresentation
    {
        return ($form && $this->presenter->state($request, $form) === 'ok')
            ? $this->presenter->present($form, $locale)
            : null;
    }
}
