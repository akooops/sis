<?php

namespace App\Services\Site;

use App\Enums\MorphType;
use App\Models\ContactDetail;
use App\Models\Form;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Stream;
use App\Services\Integrations\Analytics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * The GLOBAL half of a public request — and only that half.
 *
 * Bound onto every `site::` view as `$site` by the composer in
 * SiteServiceProvider, so the header, the footer and the drawer read it without
 * any controller having threaded it down. That is the whole remit: things every
 * page needs and no page owns.
 *
 * WHAT A PAGE OWNS NOW LIVES IN ITS CONTROLLER. This used to carry the SEO
 * builder as well — `seo()`, `meta()`, `breadcrumbs()`, `structuredData()`,
 * `canonical()`, `alternates()` and a private `translated()` reader — which
 * meant that answering "what does this page put in its <title>?" started in the
 * controller, went through a mutable builder, and ended in a partial. Each
 * controller now assembles its own `$seo` array and its own `$breadcrumbs` and
 * hands both to the view: one method to read per page.
 *
 * Everything here is a GETTER and everything past the constructor loads lazily,
 * so a redirect or a sitemap pays for nothing it does not read. Registered as a
 * container singleton, which IS the per-request memoisation: no static state and
 * no `Cache::` layer (this app has none by design).
 */
final class SiteContext
{
    /**
     * MorphType alias => the route that shows it.
     *
     * Code, not config: it maps a closed enum onto route names declared in
     * routes/web.php, and both halves want to be grep-able together. A model
     * missing from here has no public page, which is a fact about the site.
     *
     * @var array<string, string>
     */
    public const LINK_ROUTES = [
        'page' => 'web.site.pages.show',
        'article' => 'web.site.articles.show',
        'achievement' => 'web.site.achievements.show',
        'album' => 'web.site.albums.show',
        'event' => 'web.site.events.show',
        'job_offer' => 'web.site.jobs.show',
        'program' => 'web.site.programs.show',
        'brand' => 'web.site.brands.show',
        'facility' => 'web.site.facilities.show',
        'form' => 'web.site.forms.show',
    ];

    /**
     * A seeded form's real page.
     *
     * A system form has NO standalone page — /forms/{slug} 404s for one, because
     * it exists only embedded in the site page that owns it. Without this a menu
     * item pointing at the seeded contact form would resolve to a link straight
     * into that 404, which is worse than the unlinked label a null produces.
     *
     * Keyed by slug because a system form's slug is frozen (UpdateFormData pins
     * it with Rule::in), so it already IS the stable reference.
     *
     * @var array<string, string>
     */
    public const SYSTEM_FORM_ROUTES = [
        'contact' => 'web.site.contact',
        'inquiries' => 'web.site.inquiries',
    ];

    /** @var Collection<string, Setting>|null keyed "group.key" */
    private ?Collection $settings = null;

    /**
     * Menus already loaded this request, keyed by code. A null value is a code
     * with no menu behind it — cached too, so a missing menu is asked for once.
     *
     * @var array<string, Menu|null>
     */
    private array $menus = [];

    /** @var Collection<int, ContactDetail>|null */
    private ?Collection $contacts = null;

    private ?Analytics $analytics = null;

    /**
     * @param  Language|null  $language  the row for the active locale — it carries
     *                                   the code AND the direction, so neither is
     *                                   stored a second time here
     * @param  Collection<int, Language>  $languages  enabled rows, for the switcher
     */
    private function __construct(
        private readonly ?Language $language,
        private readonly Collection $languages,
    ) {}

    /**
     * Resolve the request's locale and build the context.
     *
     * SetLocale has normally already run and App::getLocale() is the URL's
     * locale. It has NOT run when this is built outside the site group — an
     * exception rendered by the handler, say — so fall back to the database
     * default rather than config('app.locale'), which knows nothing about which
     * languages are enabled.
     */
    public static function make(): self
    {
        $enabled = Language::enabledCodes();

        $locale = in_array(App::getLocale(), $enabled, true)
            ? App::getLocale()
            : Language::defaultCode();

        /*
         * The single most load-bearing line here.
         *
         * SetLocale sets this too, but the requests that never reach it are
         * exactly the ones that most need it: a 404 raised outside the locale
         * group renders the site's error page, whose header calls
         * route('web.site.home'). Without a default locale that throws "Missing
         * required parameter" INSIDE the exception handler, and the visitor gets
         * a blank 500 instead of the 404 page. Idempotent, so twice costs nothing.
         */
        URL::defaults(['locale' => $locale]);

        $languages = Language::query()->enabled()->orderBy('is_default')->orderBy('code')->get();

        return new self(
            language: $languages->firstWhere('code', $locale),
            languages: $languages,
        );
    }

    /* -----------------------------------------
     1. Language
    ------------------------------------------*/

    /**
     * The active language ROW, which is where the locale and the direction both
     * come from — a Language already carries `code` and `is_rtl`, so storing
     * either of them again here would be a second copy that could disagree.
     *
     * Null only when the languages table is empty or the active code has no row,
     * which is why every reader below has a fallback rather than a `?->`.
     */
    public function language(): ?Language
    {
        return $this->language;
    }

    public function locale(): string
    {
        return $this->language?->code ?? Language::defaultCode();
    }

    public function direction(): string
    {
        return $this->language?->direction ?? 'ltr';
    }

    public function isRtl(): bool
    {
        return $this->direction() === 'rtl';
    }

    /**
     * The enabled languages, for the switcher and the hreflang set.
     *
     * There is no hasAlternateLocales() beside this: a caller that wants to know
     * whether the switcher is worth rendering counts what it is about to loop.
     *
     * @return Collection<int, Language>
     */
    public function languages(): Collection
    {
        return $this->languages;
    }

    /**
     * The site's default language.
     *
     * Read off the already-loaded `$languages` collection rather than a fresh
     * query: `make()` loads every enabled language once, so this is a lookup,
     * not a lookup-plus-round-trip. Null only when no enabled language is
     * flagged default, which is a data problem the caller should surface
     * rather than this silently guessing one.
     */
    public function defaultLanguage(): ?Language
    {
        return $this->languages->firstWhere('is_default', true);
    }

    /* -----------------------------------------
     2. Settings
    ------------------------------------------*/

    /**
     * A setting's stored value, and nothing else.
     *
     * ONE query for the whole catalogue on first read — the header, the footer
     * and the <head> want several between them, and a lookup per call site would
     * be a query per call site. That is also why there is no global setting()
     * helper: a helper cannot hold the result.
     *
     * Null and '' both mean "unset", so neither defeats a ?? downstream.
     *
     * A `model` setting stores a bare id, so it comes back as that id and the
     * caller loads what it wants — HomeController does exactly one
     * `Program::find($site->setting('homepage.pathway_program'))`. The
     * settingModel()/settingModels() resolvers that used to live here existed to
     * do that generically for two call sites, and read config/settings.php to
     * work out the class first.
     */
    public function setting(string $path, mixed $default = null): mixed
    {
        $this->settings ??= Setting::query()->get()
            ->keyBy(fn (Setting $setting) => $setting->group.'.'.$setting->key);

        $value = $this->settings->get($path)?->value;

        return ($value === null || $value === '') ? $default : $value;
    }

    /* -----------------------------------------
     3. Navigation and shared content
    ------------------------------------------*/

    /**
     * ONE menu's top-level items, already ordered, EACH WITH ITS URL RESOLVED.
     *
     * BY CODE, and the code is the argument. A system menu's code is frozen —
     * MenusController blocks the change and the delete — so it IS the stable
     * reference, and there is no slot map in between: `menuItems('footer_primary')`
     * says which menu it means, where `menuItems('footer')` needed a second file
     * open to find out.
     *
     * `$item->url` is the destination — no second call. It arrives holding the
     * linked record's URL when the item points at one and the typed external URL
     * otherwise, which is the precedence menuUrl() used to apply at every call
     * site: the morph wins because it stays correct when a slug changes. The two
     * are mutually exclusive by the admin form's rules anyway.
     *
     * A null `url` is an item that renders as an unlinked label, and an unknown
     * or empty code returns an empty collection rather than null. Navigation is
     * chrome and must never be the thing that 500s a page.
     *
     * ONE query per menu, memoised by code, with the whole two-level tree
     * eager-loaded — so the footer asking for the same menu in two columns still
     * costs one. A page only pays for the menus it actually renders, which is why
     * the seeded-but-unused `footer_secondary` is never loaded at all. `linkable`
     * is a morphTo, so it costs one extra query per DISTINCT linked model type —
     * bounded, and the alternative is one query per item.
     *
     * @return Collection<int, MenuItem>
     */
    public function menuItems(string $code): Collection
    {
        if (! array_key_exists($code, $this->menus)) {
            $menu = Menu::query()
                ->where('code', $code)
                ->with(['rootItems' => fn ($q) => $q->with(['linkable', 'children.linkable'])])
                ->first();

            $menu?->rootItems->each(function (MenuItem $item) {
                $this->resolveUrl($item);

                $item->children->each(fn (MenuItem $child) => $this->resolveUrl($child));
            });

            $this->menus[$code] = $menu;
        }

        return $this->menus[$code]?->rootItems ?? collect();
    }

    /**
     * Every contact detail, ordered.
     *
     * ONE query and ONE getter, filtered by the caller: the footer wants emails,
     * phones, WhatsApp numbers, the address and the social row at once, and five
     * scoped methods for a handful of rows would be four too many.
     *
     * @return Collection<int, ContactDetail>
     */
    public function contacts(): Collection
    {
        return $this->contacts ??= ContactDetail::query()->ordered()->get();
    }

    /**
     * The site's analytics tag, from the `integrations.analytics` setting.
     *
     * ONE PROPERTY FOR THE WHOLE SITE. A form could once pin its own, which
     * meant the same visitor's pageviews and form events landed in two accounts
     * that no report could join into a funnel — so Analytics::forForm() and the
     * forms.analytics_integration_id column are both gone.
     *
     * Resolved once per request: the layout asks whether it is enabled and then
     * renders it, which would otherwise be two lookups.
     */
    public function analytics(): Analytics
    {
        return $this->analytics ??= Analytics::default();
    }

    /* -----------------------------------------
     4. URLs
    ------------------------------------------*/

    /**
     * The public URL for a record, or null when it has no page.
     *
     * The one piece of link building that is not a plain route() call: it maps a
     * MORPH onto a route name, so a menu item or a banner pointing at "some
     * model" resolves without the view knowing which model it got. Everything
     * with a known destination calls Laravel's route() directly.
     *
     * Null rather than an exception on purpose: this runs while rendering a menu,
     * and an item pointing at a deleted record must render as an unlinked label
     * rather than take the page down with it.
     *
     * `$locale` is normally omitted — URL::defaults supplies the current one, and
     * SiteServiceProvider then strips it back off for the default language.
     */
    public function url(?Model $model, ?string $locale = null): ?string
    {
        if ($model === null) {
            return null;
        }

        /*
         * A Stream has no page of its own — it is a tab on its programme. Resolve
         * to the programme and carry the stream as a query parameter, which is
         * what ProgramsController reads back to preselect the tab.
         */
        if ($model instanceof Stream) {
            $program = $model->program;

            return $program === null ? null : $this->route(
                self::LINK_ROUTES['program'],
                ['slug' => $program->slug, 'stream' => $model->slug],
                $locale,
            );
        }

        /*
         * A system form is embedded in a page of its own, and has no
         * /forms/{slug} to link to — see SYSTEM_FORM_ROUTES.
         */
        if ($model instanceof Form && $model->is_system) {
            $name = self::SYSTEM_FORM_ROUTES[$model->slug] ?? null;

            return $name === null ? null : $this->route($name, [], $locale);
        }

        $name = self::LINK_ROUTES[MorphType::aliasFor($model::class)] ?? null;

        return ($name === null || ! isset($model->slug))
            ? null
            : $this->route($name, ['slug' => $model->slug], $locale);
    }

    /* -----------------------------------------
     Internals
    ------------------------------------------*/

    /**
     * Overwrite a menu item's `url` with its resolved destination.
     *
     * In place, on the loaded instance and never saved: the column and the
     * resolved value mean the same thing to every consumer — "where does this
     * item go" — so a second attribute beside it would only invite a view to
     * read the wrong one.
     */
    private function resolveUrl(MenuItem $item): void
    {
        $item->url = $this->url($item->linkable) ?: ($item->url ?: null);
    }

    /**
     * route(), but a bad name returns null instead of throwing — see url().
     *
     * @param  array<string, mixed>  $parameters
     */
    private function route(string $name, array $parameters, ?string $locale): ?string
    {
        if (! RouteFacade::has($name)) {
            return null;
        }

        if ($locale !== null) {
            $parameters['locale'] = $locale;
        }

        try {
            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }
}
