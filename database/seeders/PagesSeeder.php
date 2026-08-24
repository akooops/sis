<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Page;
use App\States\Page\Published;
use Illuminate\Database\Seeder;

/**
 * Pages the app itself resolves by slug. `is_system` freezes the slug and blocks
 * the delete; everything else about the row stays editable.
 *
 * EVERY SLUG BELOW IS LOAD-BEARING. Each one is either read by a site controller
 * (`$this->page('articles')` in ArticlesController) or resolved by the exception
 * handler (`error`). Renaming one 404s a page, which is exactly what is_system
 * exists to prevent — and all but `error` also own a fixed route, so
 * Web\Site\PagesController::SLUG_ROUTES redirects /{locale}/{slug} away from the
 * generic page route for them.
 *
 * firstOrCreate, NOT updateOrCreate — a deliberate divergence from
 * LanguagesSeeder. A language row is pure metadata, so overwriting it on reseed
 * is harmless; a page row is authored content, and a reseed must never clobber
 * copy an admin has since written. Only the lock is re-asserted.
 *
 * Titles are seeded in ALL NINE LOCALES. They are the one piece of page content
 * the app itself depends on — a system page with no title in the active language
 * renders a blank heading — so shipping them translated means a fresh install is
 * coherent in every enabled locale rather than English-with-Arabic-chrome. The
 * BODY of each page is still empty and still the admin's to write.
 */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array{slug: string, name: string, title: array<string, string>}> $pages */
        $pages = [
            ['slug' => 'home', 'name' => 'Home', 'title' => [
                'en' => 'Welcome', 'ar' => 'مرحبًا بكم', 'fr' => 'Bienvenue', 'es' => 'Bienvenidos',
                'de' => 'Willkommen', 'it' => 'Benvenuti', 'pt' => 'Bem-vindos', 'ru' => 'Добро пожаловать',
                'hi' => 'स्वागत है',
            ]],
            ['slug' => 'articles', 'name' => 'News', 'title' => [
                'en' => 'News', 'ar' => 'الأخبار', 'fr' => 'Actualités', 'es' => 'Noticias',
                'de' => 'Neuigkeiten', 'it' => 'Notizie', 'pt' => 'Notícias', 'ru' => 'Новости',
                'hi' => 'समाचार',
            ]],
            ['slug' => 'albums', 'name' => 'Albums', 'title' => [
                'en' => 'Albums', 'ar' => 'الألبومات', 'fr' => 'Albums', 'es' => 'Álbumes',
                'de' => 'Alben', 'it' => 'Album', 'pt' => 'Álbuns', 'ru' => 'Альбомы', 'hi' => 'एल्बम',
            ]],
            ['slug' => 'events', 'name' => 'Events', 'title' => [
                'en' => 'Events', 'ar' => 'الفعاليات', 'fr' => 'Événements', 'es' => 'Eventos',
                'de' => 'Veranstaltungen', 'it' => 'Eventi', 'pt' => 'Eventos', 'ru' => 'События',
                'hi' => 'कार्यक्रम',
            ]],
            ['slug' => 'achievements', 'name' => 'Achievements', 'title' => [
                'en' => 'Achievements', 'ar' => 'الإنجازات', 'fr' => 'Réalisations', 'es' => 'Logros',
                'de' => 'Erfolge', 'it' => 'Risultati', 'pt' => 'Conquistas', 'ru' => 'Достижения',
                'hi' => 'उपलब्धियाँ',
            ]],
            ['slug' => 'identity', 'name' => 'Identity', 'title' => [
                'en' => 'Our identity', 'ar' => 'هويتنا', 'fr' => 'Notre identité', 'es' => 'Nuestra identidad',
                'de' => 'Unsere Identität', 'it' => 'La nostra identità', 'pt' => 'A nossa identidade',
                'ru' => 'Наша айдентика', 'hi' => 'हमारी पहचान',
            ]],
            ['slug' => 'jobs', 'name' => 'Careers', 'title' => [
                'en' => 'Careers', 'ar' => 'الوظائف', 'fr' => 'Carrières', 'es' => 'Empleo',
                'de' => 'Karriere', 'it' => 'Lavora con noi', 'pt' => 'Carreiras', 'ru' => 'Вакансии',
                'hi' => 'करियर',
            ]],
            ['slug' => 'calendars', 'name' => 'Calendars', 'title' => [
                'en' => 'Academic calendars', 'ar' => 'التقويمات الدراسية', 'fr' => 'Calendriers scolaires',
                'es' => 'Calendarios académicos', 'de' => 'Schulkalender', 'it' => 'Calendari scolastici',
                'pt' => 'Calendários académicos', 'ru' => 'Учебные календари', 'hi' => 'शैक्षणिक कैलेंडर',
            ]],
            ['slug' => 'newsletters', 'name' => 'Newsletters', 'title' => [
                'en' => 'Newsletters', 'ar' => 'النشرات', 'fr' => 'Bulletins', 'es' => 'Boletines',
                'de' => 'Newsletter', 'it' => 'Newsletter', 'pt' => 'Boletins', 'ru' => 'Рассылки',
                'hi' => 'न्यूज़लेटर',
            ]],
            ['slug' => 'guidelines', 'name' => 'Guidelines', 'title' => [
                'en' => 'Guidelines', 'ar' => 'الأدلة الإرشادية', 'fr' => 'Guides', 'es' => 'Guías',
                'de' => 'Richtlinien', 'it' => 'Linee guida', 'pt' => 'Orientações', 'ru' => 'Руководства',
                'hi' => 'दिशानिर्देश',
            ]],
            ['slug' => 'documents', 'name' => 'Documents', 'title' => [
                'en' => 'Documents and forms', 'ar' => 'المستندات والنماذج', 'fr' => 'Documents et formulaires',
                'es' => 'Documentos y formularios', 'de' => 'Dokumente und Formulare',
                'it' => 'Documenti e moduli', 'pt' => 'Documentos e formulários',
                'ru' => 'Документы и формы', 'hi' => 'दस्तावेज़ और फ़ॉर्म',
            ]],
            ['slug' => 'contact', 'name' => 'Contact', 'title' => [
                'en' => 'Contact us', 'ar' => 'اتصل بنا', 'fr' => 'Nous contacter', 'es' => 'Contacto',
                'de' => 'Kontakt', 'it' => 'Contattaci', 'pt' => 'Contacte-nos', 'ru' => 'Свяжитесь с нами',
                'hi' => 'हमसे संपर्क करें',
            ]],
            ['slug' => 'inquiries', 'name' => 'Admissions', 'title' => [
                'en' => 'Admissions inquiry', 'ar' => 'طلب القبول والتسجيل', 'fr' => 'Demande d\'admission',
                'es' => 'Solicitud de admisión', 'de' => 'Aufnahmeanfrage', 'it' => 'Richiesta di ammissione',
                'pt' => 'Pedido de admissão', 'ru' => 'Заявка на приём', 'hi' => 'प्रवेश पूछताछ',
            ]],
            ['slug' => 'facilities', 'name' => 'Facilities', 'title' => [
                'en' => 'Facilities', 'ar' => 'المرافق', 'fr' => 'Nos espaces',
                'es' => 'Instalaciones', 'de' => 'Räumlichkeiten', 'it' => 'I nostri spazi',
                'pt' => 'Os nossos espaços', 'ru' => 'Площадки', 'hi' => 'सुविधाएँ',
            ]],
            ['slug' => 'visits', 'name' => 'School visits', 'title' => [
                'en' => 'School visits', 'ar' => 'زيارات المدرسة', 'fr' => 'Visites de l’école',
                'es' => 'Visitas a la escuela', 'de' => 'Schulbesuche', 'it' => 'Visite alla scuola',
                'pt' => 'Visitas à escola', 'ru' => 'Посещение школы', 'hi' => 'विद्यालय भ्रमण',
            ]],
            // Not routed: the exception handler's 404 view reads this one, and
            // renders a translated fallback when it is missing.
            ['slug' => 'error', 'name' => 'Error', 'title' => [
                'en' => 'Page not found', 'ar' => 'الصفحة غير موجودة', 'fr' => 'Page introuvable',
                'es' => 'Página no encontrada', 'de' => 'Seite nicht gefunden', 'it' => 'Pagina non trovata',
                'pt' => 'Página não encontrada', 'ru' => 'Страница не найдена', 'hi' => 'पृष्ठ नहीं मिला',
            ]],
        ];

        if ($pages === []) {
            return;
        }

        // Only the locales this install actually has rows for — seeding a title
        // for a language nobody created would sit in the json column unreachable.
        $codes = Language::query()->pluck('code')->all();

        foreach ($pages as $definition) {
            $page = Page::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'title' => array_intersect_key($definition['title'], array_flip($codes)),
                    'status' => Published::class,
                    'published_at' => now(),
                    'is_system' => true,
                ],
            );

            // An existing page adopted into the system set: assert the lock only.
            if (! $page->wasRecentlyCreated && ! $page->is_system) {
                $page->forceFill(['is_system' => true])->save();
            }
        }
    }
}
