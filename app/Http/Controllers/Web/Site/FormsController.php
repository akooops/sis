<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Form;
use App\Services\Forms\FormPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * A public form's page.
 *
 * An ordinary site page like any other — hero, breadcrumb, body, then the form —
 * built from the form's OWN thumbnail, title, description and content, which are
 * the same four columns a Page or an Article renders from. It replaces four
 * standalone views (show/thanks/closed/blocked) that were never more than a
 * placeholder bolted onto the layout.
 *
 * THE SUBMISSION PIPELINE IS NOT HERE. Web\FormsController still owns the POST
 * endpoints and their twelve gates, and their URIs did not move. This owns the
 * page a visitor reads, and nothing else.
 *
 * Every outcome is an alert on this same page rather than a separate URL — see
 * site::partials.form-embed, which /contact and /inquiries include too, so all
 * three behave identically.
 */
class FormsController extends SiteController
{
    public function __construct(protected FormPresenter $presenter) {}

    public function show(Request $request): View|Response
    {
        $locale = $this->site()->locale();

        /*
         * By name, never as a method argument. Laravel resolves a non-class
         * controller parameter POSITIONALLY, so on the /{locale}/forms/{slug}
         * registration a show(Request, string $slug) is handed the LOCALE and
         * every form 404s hunting a slug of "en". The unprefixed twin has one
         * parameter and works fine, which makes it look locale-specific.
         */
        $slug = $request->route('slug');

        $form = Form::query()->live()->with('media')->where('slug', $slug)->firstOrFail();

        /*
         * A seeded form has no page of its own: it exists embedded in the site
         * page that owns it (/contact, /inquiries), and that page is the only
         * place it may be read. Enforced here rather than hidden, because the
         * URL is guessable from the slug.
         */
        abort_if($form->is_system, 404);

        /*
         * THE SUCCESS FLASH IS CHECKED BEFORE THE STATE, and the order matters.
         *
         * A submission is the thing most likely to have just closed the form:
         * on a form capped at N, the Nth submit increments submissions_count to
         * the cap, back() issues this GET, and asking the presenter first would
         * answer "closed" — telling the person who just succeeded that the form
         * is no longer accepting responses, and swallowing their confirmation
         * and its reference. The deleted /thanks page could not hit this because
         * it never re-evaluated anything.
         */
        $submitted = session('sisf_submitted') === $form->id;

        $state = $submitted ? 'ok' : $this->presenter->state($request, $form);

        /*
         * submit() already returns back()->withErrors(['form' => …]) for a
         * capped or blocked post, and the renderer prints that above the form.
         * Without this the visitor would read the same sentence twice, in two
         * different alerts, on the one page.
         */
        $hasFormError = session('errors')?->getBag('default')->has('form') ?? false;

        $title = $form->getTranslation('title', $locale, true) ?: $form->name;

        $routeName = 'web.site.forms.show';
        $routeParameters = ['slug' => $form->slug];

        $seo = [
            'title' => $title,
            'description' => $form->getTranslation('description', $locale, true),
            'image' => $form->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => $state === 'ok' ? 'index,follow' : 'noindex,nofollow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        $view = view('site::pages.forms.show', [
            'form' => $form,
            'title' => $title,
            // Null on success too: the confirmation replaces the form, exactly
            // as the thanks page used to.
            'presentation' => ($state === 'ok' && ! $submitted)
                ? $this->presenter->present($form, $locale)
                : null,
            'notice' => ($state === 'ok' || $hasFormError) ? null : $state,
            // The page owns the title, description and content, so the renderer
            // must not print its own copy of all three underneath them.
            'chrome' => false,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);

        /*
         * The page renders in full either way — a visitor who cannot submit
         * still gets the school's chrome and an explanation, not a dead end —
         * but the status code stays honest for anything that is not a browser.
         */
        return match ($state) {
            'blocked' => response($view, 403),
            'closed' => response($view, 410),
            default => $view,
        };
    }
}
