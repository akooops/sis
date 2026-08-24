<?php

namespace App\Services\Forms;

use App\Services\Analytics\GeoResolver;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Language;
use App\Services\Integrations\Captcha;
use Illuminate\Http\Request;

/**
 * Builds everything a page needs to render a public form.
 *
 * Extracted VERBATIM from Web\FormsController when the site's contact and
 * admissions pages started embedding the renderer. That controller's own
 * docblock is explicit that its Blade views were always a placeholder but the
 * JSON schema, the token and the guard order are a permanent contract — so this
 * moved the presentation and changed none of the contract.
 *
 * The controller still owns the GUARDS and the abort codes. This owns only the
 * assembly, so there is exactly one implementation of the schema shape rather
 * than a second one growing quietly inside the site controllers.
 */
class FormPresenter
{
    public function __construct(
        protected SubmissionGuard $guard,
        protected GeoResolver $geo,
    ) {}

    /**
     * Whether this visitor may see this form at all.
     *
     * Blocked on the GET as well as the POST: rendering a form somebody may not
     * submit is just a slower rejection.
     *
     * @return 'ok'|'blocked'|'closed'
     */
    public function state(Request $request, Form $form): string
    {
        if ($this->guard->blocksCountry($form, $this->geo->countryCode($request))
            || $this->guard->blocksIp($form->load('blockedIps'), $request->ip())) {
            return 'blocked';
        }

        return $form->hasReachedLimit() ? 'closed' : 'ok';
    }

    /** Mint a token and assemble the payload the renderer boots from. */
    public function present(Form $form, string $locale): FormPresentation
    {
        /*
         * Keep the visitor's upload session across a rejected submit — see
         * SubmissionToken::mint(). Only their OWN token counts, only while it is
         * still valid, and only for this form; anything else mints fresh.
         */
        $previous = SubmissionToken::read(old('submission_token'));

        $carried = $previous !== null
            && $previous['form'] === $form->id
            && ! SubmissionToken::isStale($previous)
                ? $previous['sid']
                : null;

        $minted = SubmissionToken::mint($form, $carried);

        return new FormPresentation(
            form: $form,
            locale: $locale,
            schema: $this->schema($form, $locale),
            token: $minted['token'],
            honeypot: $form->is_spam_filtered ? $minted['honeypot'] : null,
            captcha: Captcha::forForm($form),
            action: route('web.user.forms.submit', ['locale' => $locale, 'slug' => $form->slug]),
            // No locale on these two: the uploader answers with ids and sizes and
            // the beacon answers 204, so neither has anything a language changes.
            uploadAction: route('web.user.forms.upload', ['slug' => $form->slug]),
            telemetryAction: route('web.user.forms.telemetry', ['slug' => $form->slug]),
            labels: $this->labels(),
        );
    }

    /**
     * The renderer's own chrome, translated server-side.
     *
     * FormRenderer used to hardcode "Next", "Back", "Submit", "Sending…" and
     * "Step 2 of 3" as English literals, which meant an Arabic form had English
     * buttons. The lang keys existed and nothing read them; this is what reads
     * them. Field labels are NOT here — those are translatable columns an admin
     * edits in the builder and they ride in the schema.
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'submit' => __('forms.submit'),
            'next' => __('forms.next'),
            'back' => __('forms.back'),
            'sending' => __('forms.sending'),
            'step' => __('forms.step', ['current' => ':current', 'total' => ':total']),
            'uploading' => __('forms.uploading'),
            'removeFile' => __('forms.remove_file'),
            'captchaFailed' => __('forms.captcha_failed'),
            // :label and :number are left UNRESOLVED on purpose — the group's own
            // translated label and the row number are only known in the browser,
            // the same way `step` carries :current and :total.
            'groupAdd' => __('forms.group_add', ['label' => ':label']),
            'groupRemove' => __('forms.group_remove'),
            'groupItem' => __('forms.group_item', ['label' => ':label', 'number' => ':number']),
            'fillManually' => __('forms.fill_manually'),
            'fillManuallyHint' => __('forms.fill_manually_hint'),
            'fillWithAi' => __('forms.fill_with_ai'),
            'fillWithAiHint' => __('forms.fill_with_ai_hint'),
            'cvReading' => __('forms.cv_reading'),
            'cvFailed' => __('forms.cv_failed'),
        ];
    }

    /**
     * The renderer's schema — the same shape the builder preview feeds it.
     *
     * @return array<string, mixed>
     */
    public function schema(Form $form, string $locale): array
    {
        $form->load([
            'pages.topLevelFields.options',
            'pages.topLevelFields.children.options',
        ]);

        return [
            'id' => $form->id,
            'slug' => $form->slug,
            'locale' => $locale,
            'default_locale' => Language::defaultCode(),
            'is_rtl' => (bool) Language::where('code', $locale)->value('is_rtl'),
            'title' => $form->enabledTranslations('title'),
            'description' => $form->enabledTranslations('description'),
            'content' => $form->enabledTranslations('content'),
            'pages' => $form->pages->map(fn ($page) => [
                'id' => $page->id,
                'title' => $page->enabledTranslations('title'),
                'css_id' => $page->css_id,
                'css_class' => $page->css_class,
                'is_interstitial' => (bool) $page->is_interstitial,
                'fields' => $page->topLevelFields->map(fn ($field) => $this->field($field, $locale))->all(),
            ])->all(),
        ];
    }

    /**
     * One element, and — for a repeatable group — the elements inside it.
     *
     * A child is emitted in exactly the same shape as a top-level field, so the
     * renderer's element registry draws it with the same component and no branch:
     * the only thing that differs is where its answer lives, which the group
     * itself owns.
     *
     * @return array<string, mixed>
     */
    protected function field(FormField $field, string $locale): array
    {
        $out = [
            'id' => $field->id,
            'type' => $field->type,
            'key' => $field->key,
            'is_required' => (bool) $field->is_required,
            'settings' => $field->settings ?? [],
            'validation' => $field->validation ?? [],
            'target_form_page_id' => $field->target_form_page_id,
            'css_id' => $field->css_id,
            'css_class' => $field->css_class,
            'label' => $field->enabledTranslations('label'),
            'placeholder' => $field->enabledTranslations('placeholder'),
            'value' => $field->enabledTranslations('value'),
            // The map above is what the builder round-trips; this is the
            // one value the renderer seeds an unanswered field with, and
            // it has to be resolved HERE because the renderer seeds before
            // it knows anything about locales.
            'value_resolved' => $this->resolved($field->enabledTranslations('value'), $locale),
            'content' => $field->enabledTranslations('content'),
            'options' => $field->options->map(fn ($o) => [
                'value' => $o->value,
                'is_default' => (bool) $o->is_default,
                'label' => $o->enabledTranslations('label'),
            ])->all(),
        ];

        if ($field->isGroup()) {
            $out['children'] = $field->children->map(fn ($child) => $this->field($child, $locale))->all();
        }

        return $out;
    }

    /**
     * One translatable map, resolved for the page's locale.
     *
     * The visitor's locale first, then the default — the same order lib/forms/i18n
     * `translate()` uses, so a half-translated form falls back the same way whether
     * the value was resolved here or in the browser.
     *
     * NULL, never '', when there is nothing: the renderer seeds with
     * `?? emptyValue(type, field)`, and '' would be adopted as the answer for a
     * checkbox group or a multi-select that needs an array.
     *
     * @param  array<string, string|null>  $map
     */
    public function resolved(array $map, string $locale): ?string
    {
        foreach ([$locale, Language::defaultCode()] as $code) {
            $value = $map[$code] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
