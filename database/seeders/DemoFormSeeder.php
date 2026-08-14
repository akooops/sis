<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use App\Models\Language;
use App\States\Form\Published;
use Illuminate\Database\Seeder;

/**
 * A form that exercises EVERY field type and both kinds of page, for testing the
 * public renderer end to end.
 *
 * Not is_system: it is a fixture, not part of the app. Nothing resolves its slug,
 * so an admin can rename it, restructure it in the builder, or delete it outright
 * without breaking a page — which is the whole point of having something to poke
 * at. Run it on demand:
 *
 *     php artisan db:seed --class=DemoFormSeeder
 *
 * WHAT IT COVERS. All sixteen registered types (see config('forms.field_types')):
 * the four display/action ones — heading, paragraph, html, button — and the twelve
 * that hold an answer. Three linear pages plus one INTERSTITIAL, which is entered
 * from a `goto` button and returned from with `back`, and therefore never appears
 * in the "Step 2 of 3" progress count.
 *
 * firstOrCreate on the slug, like every other seeder here: re-running it leaves an
 * existing copy — and anything you changed in the builder — alone. Delete the form
 * to get a fresh one.
 *
 * Wording is inline per locale in the same shape config/forms.php uses, narrowed
 * to the locales this install actually has.
 */
class DemoFormSeeder extends Seeder
{
    protected const SLUG = 'test-form';

    /** @var array<int, string> */
    protected array $codes = [];

    public function run(): void
    {
        $this->codes = Language::query()->pluck('code')->all();

        $form = Form::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Test form — every component',
                'title' => $this->t([
                    'en' => 'Test form',
                    'ar' => 'نموذج اختبار',
                    'fr' => 'Formulaire de test',
                ]),
                'description' => $this->t([
                    'en' => 'Every field type the builder offers, across three steps and one interstitial.',
                    'ar' => 'كل أنواع الحقول التي يوفرها المنشئ، عبر ثلاث خطوات وصفحة بينية.',
                    'fr' => 'Tous les types de champs, en trois étapes et un interstitiel.',
                ]),
                'content' => $this->t([
                    'en' => '<p>This form exists to be filled in badly on purpose. Submit it empty to see every '
                        .'validation message in place, then fill it properly to see the confirmation.</p>',
                    'ar' => '<p>هذا النموذج موجود ليُملأ بشكل خاطئ عمدًا. أرسله فارغًا لترى رسائل التحقق، ثم املأه بشكل صحيح لترى رسالة التأكيد.</p>',
                ]),
                'confirmation_message' => $this->t([
                    'en' => '<p>Thank you — the test submission was recorded.</p>',
                    'ar' => '<p>شكرًا لك — تم تسجيل الإرسال التجريبي.</p>',
                    'fr' => '<p>Merci — l’envoi de test a été enregistré.</p>',
                ]),
                'status' => Published::class,
                'published_at' => now(),
                'is_system' => false,
                // Left on so the honeypot and the minimum fill time are both live —
                // this form is for testing the real pipeline, not a softer one.
                'is_spam_filtered' => true,
                'is_ip_stored' => true,
                'confirmation_type' => 'message',
            ],
        );

        if (! $form->wasRecentlyCreated) {
            $this->command?->warn("Form [{$form->slug}] already exists — left untouched. Delete it to reseed.");

            return;
        }

        $this->build($form);

        $this->command?->info("Form [{$form->slug}] created: /forms/{$form->slug}");
    }

    /** Narrow a locale => string map to the locales this install has. */
    protected function t(array $lines): array
    {
        $out = [];

        foreach ($this->codes as $code) {
            if (! empty($lines[$code])) {
                $out[$code] = $lines[$code];
            }
        }

        return $out ?: ['en' => reset($lines) ?: ''];
    }

    protected function build(Form $form): void
    {
        $details = $this->page($form, 0, 'Your details', ['en' => 'Your details', 'ar' => 'بياناتك']);
        $choices = $this->page($form, 1, 'Choices', ['en' => 'Choices', 'ar' => 'الخيارات']);
        $finish = $this->page($form, 2, 'Files and consent', ['en' => 'Files and consent', 'ar' => 'الملفات والموافقة']);

        // Entered from the `goto` button on page 2 and left with `back`. Ordered
        // last and flagged, so linearPages() skips it and the progress line still
        // reads "Step 2 of 3".
        $aside = $this->page($form, 3, 'More information', ['en' => 'More information', 'ar' => 'معلومات إضافية'], true);

        $order = 0;

        /* ---------------- Page 1 — the plain inputs ---------------- */

        $this->field($form, $details, $order++, 'heading', 'details_heading', [
            'content' => ['en' => 'Tell us about you', 'ar' => 'أخبرنا عنك'],
            'settings' => ['level' => 'h2'],
        ]);

        $this->field($form, $details, $order++, 'paragraph', 'details_intro', [
            'content' => [
                'en' => 'Nothing here is real. Required fields are marked with an asterisk.',
                'ar' => 'لا شيء هنا حقيقي. الحقول المطلوبة معلَّمة بنجمة.',
            ],
        ]);

        $this->field($form, $details, $order++, 'text', 'full_name', [
            'label' => ['en' => 'Full name', 'ar' => 'الاسم الكامل'],
            'placeholder' => ['en' => 'Ada Lovelace', 'ar' => 'آدا لوفلايس'],
            'is_required' => true,
            'validation' => ['min_length' => 3, 'max_length' => 120],
        ]);

        $this->field($form, $details, $order++, 'email', 'email', [
            'label' => ['en' => 'Email address', 'ar' => 'البريد الإلكتروني'],
            'is_required' => true,
        ]);

        // Enhanced with intl-tel-input on the site; answers in E.164.
        $this->field($form, $details, $order++, 'phone', 'phone', [
            'label' => ['en' => 'Phone number', 'ar' => 'رقم الهاتف'],
            'placeholder' => ['en' => '+966555123456'],
        ]);

        $this->field($form, $details, $order++, 'number', 'guests', [
            'label' => ['en' => 'How many guests', 'ar' => 'عدد الضيوف'],
            'settings' => ['step' => 1],
            'validation' => ['min' => 1, 'max' => 20, 'integer_only' => true],
        ]);

        $this->field($form, $details, $order++, 'date', 'visit_at', [
            'label' => ['en' => 'Preferred date and time', 'ar' => 'التاريخ والوقت المفضّل'],
            'settings' => ['include_time' => true],
            'validation' => ['min_date' => 'today'],
        ]);

        $this->field($form, $details, $order++, 'button', 'to_choices', [
            'label' => ['en' => 'Next', 'ar' => 'التالي'],
            'settings' => ['action' => 'next', 'variant' => 'primary'],
        ]);

        /* ---------------- Page 2 — the choice controls ---------------- */

        $order = 0;

        $this->field($form, $choices, $order++, 'heading', 'choices_heading', [
            'content' => ['en' => 'Pick some things', 'ar' => 'اختر بعض الأشياء'],
            'settings' => ['level' => 'h2'],
        ]);

        $stream = $this->field($form, $choices, $order++, 'select', 'stream', [
            'label' => ['en' => 'Programme', 'ar' => 'البرنامج'],
            'is_required' => true,
            'settings' => ['is_multiple' => false],
        ]);
        $this->options($stream, [
            ['national', ['en' => 'National', 'ar' => 'وطني']],
            ['international', ['en' => 'International', 'ar' => 'دولي']],
            ['bilingual', ['en' => 'Bilingual', 'ar' => 'ثنائي اللغة']],
        ]);

        $langs = $this->field($form, $choices, $order++, 'select', 'languages', [
            'label' => ['en' => 'Languages spoken (multi-select)', 'ar' => 'اللغات المحكية (اختيار متعدد)'],
            'settings' => ['is_multiple' => true],
        ]);
        $this->options($langs, [
            ['ar', ['en' => 'Arabic', 'ar' => 'العربية']],
            ['en', ['en' => 'English', 'ar' => 'الإنجليزية']],
            ['fr', ['en' => 'French', 'ar' => 'الفرنسية']],
        ]);

        $contact = $this->field($form, $choices, $order++, 'radio', 'contact_by', [
            'label' => ['en' => 'Contact me by', 'ar' => 'تواصل معي عبر'],
            'is_required' => true,
            'settings' => ['inline' => true],
        ]);
        $this->options($contact, [
            ['email', ['en' => 'Email', 'ar' => 'البريد الإلكتروني'], true],
            ['phone', ['en' => 'Phone', 'ar' => 'الهاتف']],
        ]);

        $interests = $this->field($form, $choices, $order++, 'checkbox', 'interests', [
            'label' => ['en' => 'Interests (pick one or two)', 'ar' => 'الاهتمامات (اختر واحدًا أو اثنين)'],
            'is_required' => true,
            'settings' => ['inline' => false],
            'validation' => ['min_selected' => 1, 'max_selected' => 2],
        ]);
        $this->options($interests, [
            ['sports', ['en' => 'Sports', 'ar' => 'الرياضة']],
            ['arts', ['en' => 'Arts', 'ar' => 'الفنون']],
            ['science', ['en' => 'Science', 'ar' => 'العلوم']],
        ]);

        // Raw admin-authored markup, rendered unescaped by the renderer.
        $this->field($form, $choices, $order++, 'html', 'choices_note', [
            'content' => [
                'en' => '<p><strong>Note:</strong> this block is raw HTML from the builder — '
                    .'use it for anything the other elements cannot say.</p>',
                'ar' => '<p><strong>ملاحظة:</strong> هذه الكتلة HTML خام من المنشئ.</p>',
            ],
        ]);

        $this->field($form, $choices, $order++, 'button', 'to_aside', [
            'label' => ['en' => 'Read more first', 'ar' => 'اقرأ المزيد أولًا'],
            'settings' => ['action' => 'goto', 'variant' => 'secondary'],
            'target_form_page_id' => $aside->id,
        ]);

        $this->field($form, $choices, $order++, 'button', 'choices_back', [
            'label' => ['en' => 'Back', 'ar' => 'رجوع'],
            'settings' => ['action' => 'back', 'variant' => 'secondary'],
        ]);

        $this->field($form, $choices, $order++, 'button', 'to_finish', [
            'label' => ['en' => 'Next', 'ar' => 'التالي'],
            'settings' => ['action' => 'next', 'variant' => 'primary'],
        ]);

        /* ---------------- Page 3 — upload, consent, submit ---------------- */

        $order = 0;

        $this->field($form, $finish, $order++, 'heading', 'finish_heading', [
            'content' => ['en' => 'Nearly done', 'ar' => 'أوشكنا على الانتهاء'],
            'settings' => ['level' => 'h2'],
        ]);

        $this->field($form, $finish, $order++, 'textarea', 'notes', [
            'label' => ['en' => 'Anything else', 'ar' => 'أي شيء آخر'],
            'settings' => ['rows' => 6],
            'validation' => ['max_length' => 2000],
        ]);

        // Uploaded to its own endpoint before submit; the answer carries media ids.
        $this->field($form, $finish, $order++, 'file', 'attachments', [
            'label' => ['en' => 'Attachments', 'ar' => 'المرفقات'],
            'settings' => [
                'is_multiple' => true,
                'max_files' => 3,
                'extensions' => ['pdf', 'docx', 'png', 'jpg'],
            ],
        ]);

        // Renders no wrapper and shows the visitor nothing; it just posts.
        $this->field($form, $finish, $order++, 'hidden', 'source', [
            'value' => ['en' => 'test-form'],
        ]);

        $this->field($form, $finish, $order++, 'consent', 'agree', [
            'label' => [
                'en' => 'I agree to be contacted about this test submission.',
                'ar' => 'أوافق على أن يتم التواصل معي بخصوص هذا الإرسال التجريبي.',
            ],
            'is_required' => true,
        ]);

        $this->field($form, $finish, $order++, 'button', 'finish_back', [
            'label' => ['en' => 'Back', 'ar' => 'رجوع'],
            'settings' => ['action' => 'back', 'variant' => 'secondary'],
        ]);

        $this->field($form, $finish, $order++, 'button', 'send', [
            'label' => ['en' => 'Send', 'ar' => 'إرسال'],
            'settings' => ['action' => 'submit', 'variant' => 'primary'],
        ]);

        /* ---------------- The interstitial ---------------- */

        $order = 0;

        $this->field($form, $aside, $order++, 'heading', 'aside_heading', [
            'content' => ['en' => 'About the programmes', 'ar' => 'عن البرامج'],
            'settings' => ['level' => 'h2'],
        ]);

        $this->field($form, $aside, $order++, 'html', 'aside_body', [
            'content' => [
                'en' => '<p>You reached this page from a <code>goto</code> button, and it is flagged as an '
                    .'interstitial — so it is not counted as a step and the progress line above is unchanged.</p>',
                'ar' => '<p>وصلت إلى هذه الصفحة من زر <code>goto</code>، وهي مُعلَّمة كصفحة بينية.</p>',
            ],
        ]);

        $this->field($form, $aside, $order++, 'button', 'aside_back', [
            'label' => ['en' => 'Back to the form', 'ar' => 'العودة إلى النموذج'],
            'settings' => ['action' => 'back', 'variant' => 'primary'],
        ]);
    }

    /** @param array<string, string> $title */
    protected function page(Form $form, int $order, string $name, array $title, bool $interstitial = false): FormPage
    {
        return FormPage::create([
            'form_id' => $form->id,
            'name' => $name,
            'order' => $order,
            'is_interstitial' => $interstitial,
            'title' => $this->t($title),
        ]);
    }

    /** @param array<string, mixed> $definition */
    protected function field(Form $form, FormPage $page, int $order, string $type, string $key, array $definition): FormField
    {
        return FormField::create([
            'form_id' => $form->id,
            'form_page_id' => $page->id,
            'type' => $type,
            'key' => $key,
            'order' => $order,
            'is_required' => (bool) ($definition['is_required'] ?? false),
            'settings' => $definition['settings'] ?? null,
            'validation' => $definition['validation'] ?? null,
            'target_form_page_id' => $definition['target_form_page_id'] ?? null,
            'label' => $this->t($definition['label'] ?? []),
            'placeholder' => $this->t($definition['placeholder'] ?? []),
            'value' => $this->t($definition['value'] ?? []),
            'content' => $this->t($definition['content'] ?? []),
        ]);
    }

    /**
     * Option VALUES are never translated — the same answer has to read identically
     * whatever language it was given in, which is what makes an export comparable.
     *
     * @param  array<int, array{0: string, 1: array<string, string>, 2?: bool}>  $options
     */
    protected function options(FormField $field, array $options): void
    {
        foreach ($options as $index => [$value, $label]) {
            FormFieldOption::create([
                'form_field_id' => $field->id,
                'value' => $value,
                'order' => $index,
                'is_default' => (bool) ($options[$index][2] ?? false),
                'label' => $this->t($label),
            ]);
        }
    }
}
