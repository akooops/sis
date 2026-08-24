<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Database\Seeder;

/**
 * Mirrors the catalogue at the bottom of this file into the translation_keys
 * registry, then writes it to disk — one lang/{code}/{group}.php per locale per
 * group. Adding a line is: add it to catalogue(), reseed.
 *
 * THE CATALOGUE LIVES HERE, NOT IN CONFIG. It is seed content, read once by this
 * class at deploy time, and config/ is for settings the running app reads — the
 * app itself reads translations through __()/@lang() off lang/, never off this
 * array. What stays in config/translations.php is exactly the settings half: the
 * `groups` whitelist, the code/group path patterns and `fill_missing_keys`.
 *
 * lang/ is GENERATED OUTPUT and gitignored, so this is not a convenience: it is
 * how a fresh clone gets any translations at all. THIS FILE IS THE TRACKED
 * SOURCE.
 *
 * Only ever fills keys that are ABSENT, so reseeding never overwrites what an
 * admin has translated through the Translations page. Uses putMany() rather than
 * put(): one locked write per (locale, group) instead of one per key, and no
 * activity row — a seed is not an admin edit.
 *
 * Runs after LanguagesSeeder, which creates the locales written to here.
 */
class TranslationKeysSeeder extends Seeder
{
    public function run(): void
    {
        $translations = app(TranslationService::class);

        $codes = Language::query()->pluck('code');

        /*
         * The escape hatch, and it exists because lang/ is generated output.
         *
         * onlyMissing is what keeps a reseed safe for an admin's edits — but it
         * also means correcting a string here never reaches an install that
         * already wrote that file. One env flag is cheaper than a bespoke
         * translations:sync command, and it leaves the safe behaviour as default.
         */
        $overwrite = (bool) env('TRANSLATIONS_OVERWRITE', false);

        $catalogue = $this->catalogue();

        /*
         * Driven by the config whitelist, not by array_keys($catalogue), so a
         * group can be retired from the seed without its lines being deleted
         * from this file — and so the order is deterministic.
         */
        foreach ($translations->groups() as $group) {
            $lines = $catalogue[$group] ?? [];

            foreach (array_keys($lines) as $key) {
                TranslationKey::updateOrCreate(['group' => $group, 'key' => $key]);
            }

            foreach ($codes as $code) {
                $translations->putMany(
                    $code,
                    $group,
                    $this->linesFor($lines, $code),
                    onlyMissing: ! $overwrite,
                );
            }
        }
    }

    /**
     * One group's lines for one locale, ready for putMany(). A locale the
     * catalogue has no entry for yields '' — which, with fill_missing_keys off,
     * is written once and then falls back to English on every read.
     *
     * @param  array<string, array<string, string>|string>  $lines
     * @return array<string, string>
     */
    private function linesFor(array $lines, string $code): array
    {
        return array_map(
            fn ($locales) => (string) (is_array($locales) ? ($locales[$code] ?? '') : $locales),
            $lines,
        );
    }

    /**
     * THE WHOLE CATALOGUE, IN ONE METHOD.
     *
     * It was six files under config/translations/, then one config file, and is
     * now one method: every revision of it was the same shape, and a nested
     * config directory bought nothing but more files to open. Big, but it is a
     * data table — read it with a search, not by scrolling.
     *
     * Keys are FLAT-DOTTED and written NESTED to disk, which is what __() and
     * @lang() read: 'confirm.title' becomes ['confirm' => ['title' => …]].
     *
     * Each value is a map of LOCALE => STRING covering the nine codes
     * LanguagesSeeder ships. An absent or blank locale seeds as a missing line
     * and shows up under the Translations page's `missing` filter.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    private function catalogue(): array
    {
        return [

            /*
             * Translation catalogue — group `common` (lang/{code}/common.php).
             *
             * The words that belong to no single page: verbs the admin kit uses, and the
             * handful of shared nouns the public site repeats everywhere. Anything that
             * names a page belongs in `site`.
             *
             * Keys are FLAT-DOTTED here and written NESTED to disk, which is what __() and
             * @lang() read: 'confirm.title' becomes ['confirm' => ['title' => …]].
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'common' => [
                /* Admin kit verbs — pre-existing keys, do not renumber or rename. */

                'actions' => [
                    'en' => 'Actions', 'ar' => 'الإجراءات', 'fr' => 'Actions', 'es' => 'Acciones', 'de' => 'Aktionen',
                    'it' => 'Azioni', 'pt' => 'Ações', 'ru' => 'Действия', 'hi' => 'कार्रवाइयाँ',
                ],
                'cancel' => [
                    'en' => 'Cancel', 'ar' => 'إلغاء', 'fr' => 'Annuler', 'es' => 'Cancelar', 'de' => 'Abbrechen',
                    'it' => 'Annulla', 'pt' => 'Cancelar', 'ru' => 'Отмена', 'hi' => 'रद्द करें',
                ],
                'confirm.body' => [
                    'en' => 'This action cannot be undone.', 'ar' => 'لا يمكن التراجع عن هذا الإجراء.',
                    'fr' => 'Cette action est irréversible.', 'es' => 'Esta acción no se puede deshacer.',
                    'de' => 'Diese Aktion kann nicht rückgängig gemacht werden.', 'it' => 'Questa azione non può essere annullata.',
                    'pt' => 'Esta ação não pode ser desfeita.', 'ru' => 'Это действие нельзя отменить.',
                    'hi' => 'यह कार्रवाई पूर्ववत नहीं की जा सकती।',
                ],
                'confirm.title' => [
                    'en' => 'Are you sure?', 'ar' => 'هل أنت متأكد؟', 'fr' => 'Êtes-vous sûr ?', 'es' => '¿Está seguro?',
                    'de' => 'Sind Sie sicher?', 'it' => 'Sei sicuro?', 'pt' => 'Tem a certeza?', 'ru' => 'Вы уверены?',
                    'hi' => 'क्या आप निश्चित हैं?',
                ],
                'create' => [
                    'en' => 'Create', 'ar' => 'إنشاء', 'fr' => 'Créer', 'es' => 'Crear', 'de' => 'Erstellen',
                    'it' => 'Crea', 'pt' => 'Criar', 'ru' => 'Создать', 'hi' => 'बनाएँ',
                ],
                'delete' => [
                    'en' => 'Delete', 'ar' => 'حذف', 'fr' => 'Supprimer', 'es' => 'Eliminar', 'de' => 'Löschen',
                    'it' => 'Elimina', 'pt' => 'Eliminar', 'ru' => 'Удалить', 'hi' => 'हटाएँ',
                ],
                'edit' => [
                    'en' => 'Edit', 'ar' => 'تعديل', 'fr' => 'Modifier', 'es' => 'Editar', 'de' => 'Bearbeiten',
                    'it' => 'Modifica', 'pt' => 'Editar', 'ru' => 'Изменить', 'hi' => 'संपादित करें',
                ],
                'no' => [
                    'en' => 'No', 'ar' => 'لا', 'fr' => 'Non', 'es' => 'No', 'de' => 'Nein',
                    'it' => 'No', 'pt' => 'Não', 'ru' => 'Нет', 'hi' => 'नहीं',
                ],
                'save' => [
                    'en' => 'Save', 'ar' => 'حفظ', 'fr' => 'Enregistrer', 'es' => 'Guardar', 'de' => 'Speichern',
                    'it' => 'Salva', 'pt' => 'Guardar', 'ru' => 'Сохранить', 'hi' => 'सहेजें',
                ],
                'search' => [
                    'en' => 'Search', 'ar' => 'بحث', 'fr' => 'Rechercher', 'es' => 'Buscar', 'de' => 'Suchen',
                    'it' => 'Cerca', 'pt' => 'Pesquisar', 'ru' => 'Поиск', 'hi' => 'खोजें',
                ],
                'yes' => [
                    'en' => 'Yes', 'ar' => 'نعم', 'fr' => 'Oui', 'es' => 'Sí', 'de' => 'Ja',
                    'it' => 'Sì', 'pt' => 'Sim', 'ru' => 'Да', 'hi' => 'हाँ',
                ],

                /* Public site — shared words. `website_title` in the old catalogue. */

                'site_title' => [
                    'en' => 'Saud International Schools', 'ar' => 'مدارس سعود العالمية',
                    'fr' => 'Écoles internationales Saud', 'es' => 'Escuelas Internacionales Saud',
                    'de' => 'Saud International Schools', 'it' => 'Scuole Internazionali Saud',
                    'pt' => 'Escolas Internacionais Saud', 'ru' => 'Международные школы Сауд',
                    'hi' => 'सऊद इंटरनेशनल स्कूल्स',
                ],
                /*
                 * The site's own strap and search metadata.
                 *
                 * KEYS, NOT SETTINGS. A settings row holds ONE value for every locale, so any
                 * string a visitor reads cannot live there — the Arabic site would show the
                 * English name. That is the whole reason these moved out of the `general` and
                 * `seo` settings groups.
                 */
                'tagline' => [
                    'en' => 'Learning without limits', 'ar' => 'تعلُّم بلا حدود',
                    'fr' => 'Apprendre sans limites', 'es' => 'Aprender sin límites',
                    'de' => 'Lernen ohne Grenzen', 'it' => 'Imparare senza limiti',
                    'pt' => 'Aprender sem limites', 'ru' => 'Обучение без границ',
                    'hi' => 'असीम शिक्षा',
                ],
                'meta_description' => [
                    'en' => 'An international school community built on curiosity, care and high expectations.',
                    'ar' => 'مجتمع مدرسي عالمي قائم على الفضول والرعاية والتطلعات العالية.',
                    'fr' => 'Une communauté scolaire internationale fondée sur la curiosité, l\'attention et l\'exigence.',
                    'es' => 'Una comunidad escolar internacional basada en la curiosidad, el cuidado y la exigencia.',
                    'de' => 'Eine internationale Schulgemeinschaft aus Neugier, Fürsorge und hohem Anspruch.',
                    'it' => 'Una comunità scolastica internazionale fondata su curiosità, attenzione e alte aspettative.',
                    'pt' => 'Uma comunidade escolar internacional assente na curiosidade, no cuidado e na exigência.',
                    'ru' => 'Международное школьное сообщество, построенное на любознательности, заботе и высоких требованиях.',
                    'hi' => 'जिज्ञासा, देखभाल और ऊँची अपेक्षाओं पर आधारित एक अंतरराष्ट्रीय विद्यालय समुदाय।',
                ],
                'keywords' => [
                    'en' => 'saud international schools, international school, admissions',
                    'ar' => 'مدارس سعود العالمية، مدرسة عالمية، القبول والتسجيل',
                    'fr' => 'écoles internationales saud, école internationale, admissions',
                    'es' => 'escuelas internacionales saud, escuela internacional, admisiones',
                    'de' => 'saud international schools, internationale schule, aufnahme',
                    'it' => 'scuole internazionali saud, scuola internazionale, ammissioni',
                    'pt' => 'escolas internacionais saud, escola internacional, admissões',
                    'ru' => 'международные школы сауд, международная школа, приём',
                    'hi' => 'सऊद इंटरनेशनल स्कूल्स, अंतरराष्ट्रीय विद्यालय, प्रवेश',
                ],
                'read_more' => [
                    'en' => 'Read more', 'ar' => 'اقرأ المزيد', 'fr' => 'En savoir plus', 'es' => 'Leer más',
                    'de' => 'Mehr lesen', 'it' => 'Leggi di più', 'pt' => 'Ler mais', 'ru' => 'Подробнее',
                    'hi' => 'और पढ़ें',
                ],
                'view_all' => [
                    'en' => 'View all', 'ar' => 'عرض الكل', 'fr' => 'Tout voir', 'es' => 'Ver todo',
                    'de' => 'Alle ansehen', 'it' => 'Vedi tutto', 'pt' => 'Ver tudo', 'ru' => 'Смотреть все',
                    'hi' => 'सभी देखें',
                ],
                'view' => [
                    'en' => 'View', 'ar' => 'عرض', 'fr' => 'Voir', 'es' => 'Ver', 'de' => 'Ansehen',
                    'it' => 'Vedi', 'pt' => 'Ver', 'ru' => 'Смотреть', 'hi' => 'देखें',
                ],
                'download' => [
                    'en' => 'Download', 'ar' => 'تحميل', 'fr' => 'Télécharger', 'es' => 'Descargar',
                    'de' => 'Herunterladen', 'it' => 'Scarica', 'pt' => 'Descarregar', 'ru' => 'Скачать',
                    'hi' => 'डाउनलोड करें',
                ],
                'close' => [
                    'en' => 'Close', 'ar' => 'إغلاق', 'fr' => 'Fermer', 'es' => 'Cerrar', 'de' => 'Schließen',
                    'it' => 'Chiudi', 'pt' => 'Fechar', 'ru' => 'Закрыть', 'hi' => 'बंद करें',
                ],
                'loading' => [
                    'en' => 'Loading…', 'ar' => 'جارٍ التحميل…', 'fr' => 'Chargement…', 'es' => 'Cargando…',
                    'de' => 'Wird geladen…', 'it' => 'Caricamento…', 'pt' => 'A carregar…', 'ru' => 'Загрузка…',
                    'hi' => 'लोड हो रहा है…',
                ],
                'no_results' => [
                    'en' => 'Nothing to show yet.', 'ar' => 'لا يوجد ما يُعرض بعد.',
                    'fr' => 'Rien à afficher pour le moment.', 'es' => 'Todavía no hay nada que mostrar.',
                    'de' => 'Noch nichts anzuzeigen.', 'it' => 'Non c\'è ancora nulla da mostrare.',
                    'pt' => 'Ainda não há nada para mostrar.', 'ru' => 'Пока нечего показать.',
                    'hi' => 'अभी दिखाने के लिए कुछ नहीं है।',
                ],
                'back_home' => [
                    'en' => 'Back to home', 'ar' => 'العودة إلى الرئيسية', 'fr' => 'Retour à l\'accueil',
                    'es' => 'Volver al inicio', 'de' => 'Zurück zur Startseite', 'it' => 'Torna alla home',
                    'pt' => 'Voltar ao início', 'ru' => 'На главную', 'hi' => 'होम पर वापस जाएँ',
                ],
                'previous' => [
                    'en' => 'Previous', 'ar' => 'السابق', 'fr' => 'Précédent', 'es' => 'Anterior',
                    'de' => 'Zurück', 'it' => 'Precedente', 'pt' => 'Anterior', 'ru' => 'Назад',
                    'hi' => 'पिछला',
                ],
                'next' => [
                    'en' => 'Next', 'ar' => 'التالي', 'fr' => 'Suivant', 'es' => 'Siguiente',
                    'de' => 'Weiter', 'it' => 'Successivo', 'pt' => 'Seguinte', 'ru' => 'Далее',
                    'hi' => 'अगला',
                ],
                'pagination' => [
                    'en' => 'Pagination', 'ar' => 'ترقيم الصفحات', 'fr' => 'Pagination', 'es' => 'Paginación',
                    'de' => 'Seitennummerierung', 'it' => 'Impaginazione', 'pt' => 'Paginação',
                    'ru' => 'Постраничная навигация', 'hi' => 'पृष्ठ क्रमांकन',
                ],
                'scroll_top' => [
                    'en' => 'Back to top', 'ar' => 'العودة إلى الأعلى', 'fr' => 'Retour en haut',
                    'es' => 'Volver arriba', 'de' => 'Nach oben', 'it' => 'Torna su', 'pt' => 'Voltar ao topo',
                    'ru' => 'Наверх', 'hi' => 'ऊपर जाएँ',
                ],
                'file_size' => [
                    'en' => 'Size', 'ar' => 'الحجم', 'fr' => 'Taille', 'es' => 'Tamaño', 'de' => 'Größe',
                    'it' => 'Dimensione', 'pt' => 'Tamanho', 'ru' => 'Размер', 'hi' => 'आकार',
                ],
            ],

            /*
             * Translation catalogue — group `nav` (lang/{code}/nav.php).
             *
             * The site chrome: header, off-canvas drawer, footer. Everything here renders on
             * EVERY page, which makes it the group most worth a human review pass — a
             * visitor judges the whole site by these dozen strings.
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'nav' => [
                /* Header */

                'services' => [
                    'en' => 'Services', 'ar' => 'الخدمات', 'fr' => 'Services', 'es' => 'Servicios',
                    'de' => 'Leistungen', 'it' => 'Servizi', 'pt' => 'Serviços', 'ru' => 'Услуги',
                    'hi' => 'सेवाएँ',
                ],
                'menu.label' => [
                    'en' => 'Menu', 'ar' => 'القائمة', 'fr' => 'Menu', 'es' => 'Menú', 'de' => 'Menü',
                    'it' => 'Menu', 'pt' => 'Menu', 'ru' => 'Меню', 'hi' => 'मेन्यू',
                ],
                'search.label' => [
                    'en' => 'Search', 'ar' => 'بحث', 'fr' => 'Rechercher', 'es' => 'Buscar', 'de' => 'Suchen',
                    'it' => 'Cerca', 'pt' => 'Pesquisar', 'ru' => 'Поиск', 'hi' => 'खोजें',
                ],
                'search.placeholder' => [
                    'en' => 'Search the site…', 'ar' => 'ابحث في الموقع…', 'fr' => 'Rechercher sur le site…',
                    'es' => 'Buscar en el sitio…', 'de' => 'Website durchsuchen…', 'it' => 'Cerca nel sito…',
                    'pt' => 'Pesquisar no site…', 'ru' => 'Поиск по сайту…', 'hi' => 'साइट पर खोजें…',
                ],
                'language.label' => [
                    'en' => 'Change language', 'ar' => 'تغيير اللغة', 'fr' => 'Changer de langue',
                    'es' => 'Cambiar idioma', 'de' => 'Sprache wechseln', 'it' => 'Cambia lingua',
                    'pt' => 'Mudar de idioma', 'ru' => 'Сменить язык', 'hi' => 'भाषा बदलें',
                ],
                'skip_to_content' => [
                    'en' => 'Skip to content', 'ar' => 'تخطَّ إلى المحتوى', 'fr' => 'Aller au contenu',
                    'es' => 'Ir al contenido', 'de' => 'Zum Inhalt springen', 'it' => 'Vai al contenuto',
                    'pt' => 'Saltar para o conteúdo', 'ru' => 'Перейти к содержимому',
                    'hi' => 'सामग्री पर जाएँ',
                ],

                /* Footer */

                'footer.get_in_touch' => [
                    'en' => 'Get in touch', 'ar' => 'تواصل معنا', 'fr' => 'Nous contacter',
                    'es' => 'Póngase en contacto', 'de' => 'Kontakt aufnehmen', 'it' => 'Contattaci',
                    'pt' => 'Entre em contacto', 'ru' => 'Свяжитесь с нами', 'hi' => 'संपर्क करें',
                ],
                'footer.links' => [
                    'en' => 'Quick links', 'ar' => 'روابط سريعة', 'fr' => 'Liens rapides',
                    'es' => 'Enlaces rápidos', 'de' => 'Schnellzugriff', 'it' => 'Link rapidi',
                    'pt' => 'Ligações rápidas', 'ru' => 'Быстрые ссылки', 'hi' => 'त्वरित लिंक',
                ],
                'footer.programs' => [
                    'en' => 'Programmes', 'ar' => 'البرامج', 'fr' => 'Programmes', 'es' => 'Programas',
                    'de' => 'Programme', 'it' => 'Programmi', 'pt' => 'Programas', 'ru' => 'Программы',
                    'hi' => 'कार्यक्रम',
                ],
                'footer.social' => [
                    'en' => 'Follow us', 'ar' => 'تابعنا', 'fr' => 'Suivez-nous', 'es' => 'Síganos',
                    'de' => 'Folgen Sie uns', 'it' => 'Seguici', 'pt' => 'Siga-nos', 'ru' => 'Мы в соцсетях',
                    'hi' => 'हमें फ़ॉलो करें',
                ],
                'footer.address' => [
                    'en' => 'Address', 'ar' => 'العنوان', 'fr' => 'Adresse', 'es' => 'Dirección',
                    'de' => 'Adresse', 'it' => 'Indirizzo', 'pt' => 'Morada', 'ru' => 'Адрес',
                    'hi' => 'पता',
                ],
                'footer.rights' => [
                    'en' => 'All rights reserved.', 'ar' => 'جميع الحقوق محفوظة.', 'fr' => 'Tous droits réservés.',
                    'es' => 'Todos los derechos reservados.', 'de' => 'Alle Rechte vorbehalten.',
                    'it' => 'Tutti i diritti riservati.', 'pt' => 'Todos os direitos reservados.',
                    'ru' => 'Все права защищены.', 'hi' => 'सर्वाधिकार सुरक्षित।',
                ],
            ],

            /*
             * Translation catalogue — group `site` (lang/{code}/site.php).
             *
             * Page-level copy, keyed by the page it appears on. The old catalogue spelled
             * this as flat snake_case with the page baked into a prefix
             * (`index_page_articles_section_title`); here the page is the first dotted
             * segment and the noise words `page`/`section` are gone, so the key reads as a
             * path: site.home.articles.title.
             *
             * Editorial copy — a page's own heading and body — is NOT here. That lives on
             * the Page record as translatable `title`/`description`/`content`, edited in the
             * admin. This group is the furniture around it.
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'site' => [
                /* -----------------------------------------
                 Breadcrumbs
                ------------------------------------------*/

                'breadcrumbs.home' => [
                    'en' => 'Home', 'ar' => 'الرئيسية', 'fr' => 'Accueil', 'es' => 'Inicio', 'de' => 'Startseite',
                    'it' => 'Home', 'pt' => 'Início', 'ru' => 'Главная', 'hi' => 'होम',
                ],
                'breadcrumbs.articles' => [
                    'en' => 'News', 'ar' => 'الأخبار', 'fr' => 'Actualités', 'es' => 'Noticias',
                    'de' => 'Neuigkeiten', 'it' => 'Notizie', 'pt' => 'Notícias', 'ru' => 'Новости',
                    'hi' => 'समाचार',
                ],
                'breadcrumbs.albums' => [
                    'en' => 'Albums', 'ar' => 'الألبومات', 'fr' => 'Albums', 'es' => 'Álbumes',
                    'de' => 'Alben', 'it' => 'Album', 'pt' => 'Álbuns', 'ru' => 'Альбомы', 'hi' => 'एल्बम',
                ],
                'breadcrumbs.events' => [
                    'en' => 'Events', 'ar' => 'الفعاليات', 'fr' => 'Événements', 'es' => 'Eventos',
                    'de' => 'Veranstaltungen', 'it' => 'Eventi', 'pt' => 'Eventos', 'ru' => 'События',
                    'hi' => 'कार्यक्रम',
                ],
                'breadcrumbs.achievements' => [
                    'en' => 'Achievements', 'ar' => 'الإنجازات', 'fr' => 'Réalisations', 'es' => 'Logros',
                    'de' => 'Erfolge', 'it' => 'Risultati', 'pt' => 'Conquistas', 'ru' => 'Достижения',
                    'hi' => 'उपलब्धियाँ',
                ],
                'breadcrumbs.jobs' => [
                    'en' => 'Careers', 'ar' => 'الوظائف', 'fr' => 'Carrières', 'es' => 'Empleo',
                    'de' => 'Karriere', 'it' => 'Lavora con noi', 'pt' => 'Carreiras', 'ru' => 'Вакансии',
                    'hi' => 'करियर',
                ],
                'breadcrumbs.facilities' => [
                    'en' => 'Facilities', 'ar' => 'المرافق', 'fr' => 'Espaces', 'es' => 'Instalaciones',
                    'de' => 'Räumlichkeiten', 'it' => 'Spazi', 'pt' => 'Espaços', 'ru' => 'Площадки',
                    'hi' => 'सुविधाएँ',
                ],
                'breadcrumbs.identity' => [
                    'en' => 'Identity', 'ar' => 'الهوية', 'fr' => 'Identité', 'es' => 'Identidad',
                    'de' => 'Identität', 'it' => 'Identità', 'pt' => 'Identidade', 'ru' => 'Айдентика',
                    'hi' => 'पहचान',
                ],
                'breadcrumbs.calendars' => [
                    'en' => 'Calendars', 'ar' => 'التقويمات', 'fr' => 'Calendriers', 'es' => 'Calendarios',
                    'de' => 'Kalender', 'it' => 'Calendari', 'pt' => 'Calendários', 'ru' => 'Календари',
                    'hi' => 'कैलेंडर',
                ],
                'breadcrumbs.newsletters' => [
                    'en' => 'Newsletters', 'ar' => 'النشرات', 'fr' => 'Bulletins', 'es' => 'Boletines',
                    'de' => 'Newsletter', 'it' => 'Newsletter', 'pt' => 'Boletins', 'ru' => 'Рассылки',
                    'hi' => 'न्यूज़लेटर',
                ],
                'breadcrumbs.guidelines' => [
                    'en' => 'Guidelines', 'ar' => 'الأدلة الإرشادية', 'fr' => 'Guides', 'es' => 'Guías',
                    'de' => 'Richtlinien', 'it' => 'Linee guida', 'pt' => 'Orientações', 'ru' => 'Руководства',
                    'hi' => 'दिशानिर्देश',
                ],
                'breadcrumbs.documents' => [
                    'en' => 'Documents', 'ar' => 'المستندات', 'fr' => 'Documents', 'es' => 'Documentos',
                    'de' => 'Dokumente', 'it' => 'Documenti', 'pt' => 'Documentos', 'ru' => 'Документы',
                    'hi' => 'दस्तावेज़',
                ],
                'breadcrumbs.contact' => [
                    'en' => 'Contact', 'ar' => 'اتصل بنا', 'fr' => 'Contact', 'es' => 'Contacto',
                    'de' => 'Kontakt', 'it' => 'Contatti', 'pt' => 'Contacto', 'ru' => 'Контакты',
                    'hi' => 'संपर्क',
                ],
                'breadcrumbs.inquiries' => [
                    'en' => 'Admissions', 'ar' => 'القبول والتسجيل', 'fr' => 'Admissions', 'es' => 'Admisiones',
                    'de' => 'Aufnahme', 'it' => 'Ammissioni', 'pt' => 'Admissões', 'ru' => 'Приём',
                    'hi' => 'प्रवेश',
                ],
                'breadcrumbs.programs' => [
                    'en' => 'Programmes', 'ar' => 'البرامج', 'fr' => 'Programmes', 'es' => 'Programas',
                    'de' => 'Programme', 'it' => 'Programmi', 'pt' => 'Programas', 'ru' => 'Программы',
                    'hi' => 'कार्यक्रम',
                ],

                /* -----------------------------------------
                 Home
                ------------------------------------------*/

                'home.articles.title' => [
                    'en' => 'Latest news', 'ar' => 'أحدث الأخبار', 'fr' => 'Dernières actualités',
                    'es' => 'Últimas noticias', 'de' => 'Aktuelle Neuigkeiten', 'it' => 'Ultime notizie',
                    'pt' => 'Últimas notícias', 'ru' => 'Последние новости', 'hi' => 'ताज़ा समाचार',
                ],
                'home.articles.cta' => [
                    'en' => 'All news', 'ar' => 'كل الأخبار', 'fr' => 'Toutes les actualités',
                    'es' => 'Todas las noticias', 'de' => 'Alle Neuigkeiten', 'it' => 'Tutte le notizie',
                    'pt' => 'Todas as notícias', 'ru' => 'Все новости', 'hi' => 'सभी समाचार',
                ],
                'home.albums.title' => [
                    'en' => 'From our albums', 'ar' => 'من ألبوماتنا', 'fr' => 'Nos albums',
                    'es' => 'De nuestros álbumes', 'de' => 'Aus unseren Alben', 'it' => 'Dai nostri album',
                    'pt' => 'Dos nossos álbuns', 'ru' => 'Из наших альбомов', 'hi' => 'हमारे एल्बम से',
                ],
                'home.albums.cta' => [
                    'en' => 'All albums', 'ar' => 'كل الألبومات', 'fr' => 'Tous les albums',
                    'es' => 'Todos los álbumes', 'de' => 'Alle Alben', 'it' => 'Tutti gli album',
                    'pt' => 'Todos os álbuns', 'ru' => 'Все альбомы', 'hi' => 'सभी एल्बम',
                ],
                'home.achievements.title' => [
                    'en' => 'Our achievements', 'ar' => 'إنجازاتنا', 'fr' => 'Nos réalisations',
                    'es' => 'Nuestros logros', 'de' => 'Unsere Erfolge', 'it' => 'I nostri risultati',
                    'pt' => 'As nossas conquistas', 'ru' => 'Наши достижения', 'hi' => 'हमारी उपलब्धियाँ',
                ],
                'home.achievements.cta' => [
                    'en' => 'All achievements', 'ar' => 'كل الإنجازات', 'fr' => 'Toutes les réalisations',
                    'es' => 'Todos los logros', 'de' => 'Alle Erfolge', 'it' => 'Tutti i risultati',
                    'pt' => 'Todas as conquistas', 'ru' => 'Все достижения', 'hi' => 'सभी उपलब्धियाँ',
                ],
                'home.programs.title' => [
                    'en' => 'Academic levels', 'ar' => 'المراحل الدراسية', 'fr' => 'Niveaux scolaires',
                    'es' => 'Niveles académicos', 'de' => 'Schulstufen', 'it' => 'Livelli scolastici',
                    'pt' => 'Níveis académicos', 'ru' => 'Ступени обучения', 'hi' => 'शैक्षणिक स्तर',
                ],
                'home.programs.cta' => [
                    'en' => 'Learn more', 'ar' => 'إعرف المزيد', 'fr' => 'Découvrir les programmes',
                    'es' => 'Explorar programas', 'de' => 'Programme entdecken', 'it' => 'Scopri i programmi',
                    'pt' => 'Explorar programas', 'ru' => 'Смотреть программы', 'hi' => 'कार्यक्रम देखें',
                ],
                'home.pathway.title' => [
                    'en' => 'Academic Pathways', 'ar' => 'المسارات الدراسية', 'fr' => 'Choisissez votre parcours',
                    'es' => 'Elija su itinerario', 'de' => 'Wählen Sie Ihren Bildungsweg',
                    'it' => 'Scegli il tuo percorso', 'pt' => 'Escolha o seu percurso',
                    'ru' => 'Выберите свой путь', 'hi' => 'अपना मार्ग चुनें',
                ],
                'home.pathway.subtitle' => [
                    'en' => 'Globally Recognized. Structured for Your Child\'s Future.', 'ar' => 'معترف به عالمياً، ومصمم لمستقبل طفلك.',
                    'fr' => 'Deux programmes, une communauté.', 'es' => 'Dos currículos, una comunidad.',
                    'de' => 'Zwei Lehrpläne, eine Gemeinschaft.', 'it' => 'Due programmi, una comunità.',
                    'pt' => 'Dois currículos, uma comunidade.', 'ru' => 'Две программы, одно сообщество.',
                    'hi' => 'दो पाठ्यक्रम, एक समुदाय।',
                ],
                'home.pathway.stream_label' => [
                    'en' => 'Stream', 'ar' => 'المسار', 'fr' => 'Parcours', 'es' => 'Itinerario',
                    'de' => 'Bildungsweg', 'it' => 'Percorso', 'pt' => 'Percurso', 'ru' => 'Направление',
                    'hi' => 'मार्ग',
                ],
                'home.partners.title' => [
                    'en' => 'Our partners', 'ar' => 'شركاؤنا', 'fr' => 'Nos partenaires',
                    'es' => 'Nuestros socios', 'de' => 'Unsere Partner', 'it' => 'I nostri partner',
                    'pt' => 'Os nossos parceiros', 'ru' => 'Наши партнёры', 'hi' => 'हमारे साझेदार',
                ],
                'home.welcome.cta' => [
                    'en' => 'Discover the school', 'ar' => 'تعرَّف على المدرسة', 'fr' => 'Découvrir l\'école',
                    'es' => 'Descubra la escuela', 'de' => 'Die Schule entdecken', 'it' => 'Scopri la scuola',
                    'pt' => 'Conheça a escola', 'ru' => 'Узнать о школе', 'hi' => 'स्कूल के बारे में जानें',
                ],

                /* -----------------------------------------
                 Articles
                ------------------------------------------*/

                'articles.popular' => [
                    'en' => 'Popular articles', 'ar' => 'مقالات رائجة', 'fr' => 'Articles populaires',
                    'es' => 'Artículos populares', 'de' => 'Beliebte Beiträge', 'it' => 'Articoli popolari',
                    'pt' => 'Artigos populares', 'ru' => 'Популярные статьи', 'hi' => 'लोकप्रिय लेख',
                ],
                'articles.filters.search' => [
                    'en' => 'Search news…', 'ar' => 'ابحث في الأخبار…', 'fr' => 'Rechercher une actualité…',
                    'es' => 'Buscar noticias…', 'de' => 'Neuigkeiten durchsuchen…', 'it' => 'Cerca notizie…',
                    'pt' => 'Pesquisar notícias…', 'ru' => 'Поиск новостей…', 'hi' => 'समाचार खोजें…',
                ],
                'articles.filters.categories' => [
                    'en' => 'Categories', 'ar' => 'التصنيفات', 'fr' => 'Catégories', 'es' => 'Categorías',
                    'de' => 'Kategorien', 'it' => 'Categorie', 'pt' => 'Categorias', 'ru' => 'Категории',
                    'hi' => 'श्रेणियाँ',
                ],
                'articles.empty' => [
                    'en' => 'No articles published yet.', 'ar' => 'لم تُنشر أي مقالات بعد.',
                    'fr' => 'Aucun article publié pour le moment.', 'es' => 'Todavía no hay artículos publicados.',
                    'de' => 'Noch keine Beiträge veröffentlicht.', 'it' => 'Nessun articolo pubblicato finora.',
                    'pt' => 'Ainda não há artigos publicados.', 'ru' => 'Статей пока нет.',
                    'hi' => 'अभी तक कोई लेख प्रकाशित नहीं हुआ है।',
                ],

                /* -----------------------------------------
                 Albums / events
                ------------------------------------------*/

                'albums.videos' => [
                    'en' => 'Videos', 'ar' => 'الفيديوهات', 'fr' => 'Vidéos', 'es' => 'Vídeos',
                    'de' => 'Videos', 'it' => 'Video', 'pt' => 'Vídeos', 'ru' => 'Видео', 'hi' => 'वीडियो',
                ],
                'albums.empty' => [
                    'en' => 'No albums yet.', 'ar' => 'لا توجد ألبومات بعد.', 'fr' => 'Aucun album pour le moment.',
                    'es' => 'Todavía no hay álbumes.', 'de' => 'Noch keine Alben.', 'it' => 'Nessun album finora.',
                    'pt' => 'Ainda não há álbuns.', 'ru' => 'Альбомов пока нет.', 'hi' => 'अभी कोई एल्बम नहीं है।',
                ],
                'events.empty' => [
                    'en' => 'No events scheduled.', 'ar' => 'لا توجد فعاليات مجدولة.',
                    'fr' => 'Aucun événement programmé.', 'es' => 'No hay eventos programados.',
                    'de' => 'Keine Veranstaltungen geplant.', 'it' => 'Nessun evento in programma.',
                    'pt' => 'Não há eventos agendados.', 'ru' => 'Запланированных событий нет.',
                    'hi' => 'कोई कार्यक्रम निर्धारित नहीं है।',
                ],
                'events.starts' => [
                    'en' => 'Starts', 'ar' => 'يبدأ', 'fr' => 'Début', 'es' => 'Comienza', 'de' => 'Beginn',
                    'it' => 'Inizio', 'pt' => 'Início', 'ru' => 'Начало', 'hi' => 'प्रारंभ',
                ],
                'events.ends' => [
                    'en' => 'Ends', 'ar' => 'ينتهي', 'fr' => 'Fin', 'es' => 'Termina', 'de' => 'Ende',
                    'it' => 'Fine', 'pt' => 'Fim', 'ru' => 'Окончание', 'hi' => 'समाप्ति',
                ],

                /* -----------------------------------------
                 Achievements
                ------------------------------------------*/

                'achievements.browse' => [
                    'en' => 'Browse achievements', 'ar' => 'تصفَّح الإنجازات', 'fr' => 'Parcourir les réalisations',
                    'es' => 'Explorar logros', 'de' => 'Erfolge durchsuchen', 'it' => 'Sfoglia i risultati',
                    'pt' => 'Explorar conquistas', 'ru' => 'Просмотр достижений', 'hi' => 'उपलब्धियाँ देखें',
                ],
                'achievements.empty.title' => [
                    'en' => 'No achievements found', 'ar' => 'لم يتم العثور على إنجازات',
                    'fr' => 'Aucune réalisation trouvée', 'es' => 'No se encontraron logros',
                    'de' => 'Keine Erfolge gefunden', 'it' => 'Nessun risultato trovato',
                    'pt' => 'Nenhuma conquista encontrada', 'ru' => 'Достижения не найдены',
                    'hi' => 'कोई उपलब्धि नहीं मिली',
                ],
                'achievements.empty.body' => [
                    'en' => 'Try adjusting your filters, or check back later.',
                    'ar' => 'جرِّب تعديل عوامل التصفية، أو عد لاحقًا.',
                    'fr' => 'Essayez d\'ajuster vos filtres ou revenez plus tard.',
                    'es' => 'Pruebe a ajustar los filtros o vuelva más tarde.',
                    'de' => 'Passen Sie die Filter an oder schauen Sie später wieder vorbei.',
                    'it' => 'Prova a modificare i filtri o torna più tardi.',
                    'pt' => 'Experimente ajustar os filtros ou volte mais tarde.',
                    'ru' => 'Измените фильтры или зайдите позже.',
                    'hi' => 'फ़िल्टर बदलकर देखें, या बाद में फिर आएँ।',
                ],
                'achievements.filters.search' => [
                    'en' => 'Search achievements', 'ar' => 'ابحث في الإنجازات', 'fr' => 'Rechercher une réalisation',
                    'es' => 'Buscar logros', 'de' => 'Erfolge suchen', 'it' => 'Cerca risultati',
                    'pt' => 'Pesquisar conquistas', 'ru' => 'Поиск достижений', 'hi' => 'उपलब्धियाँ खोजें',
                ],
                'achievements.filters.categories' => [
                    'en' => 'Categories', 'ar' => 'التصنيفات', 'fr' => 'Catégories', 'es' => 'Categorías',
                    'de' => 'Kategorien', 'it' => 'Categorie', 'pt' => 'Categorias', 'ru' => 'Категории',
                    'hi' => 'श्रेणियाँ',
                ],
                'achievements.filters.years' => [
                    'en' => 'Years', 'ar' => 'السنوات', 'fr' => 'Années', 'es' => 'Años', 'de' => 'Jahre',
                    'it' => 'Anni', 'pt' => 'Anos', 'ru' => 'Годы', 'hi' => 'वर्ष',
                ],
                'achievements.filters.all_years' => [
                    'en' => 'All years', 'ar' => 'كل السنوات', 'fr' => 'Toutes les années',
                    'es' => 'Todos los años', 'de' => 'Alle Jahre', 'it' => 'Tutti gli anni',
                    'pt' => 'Todos os anos', 'ru' => 'Все годы', 'hi' => 'सभी वर्ष',
                ],
                'achievements.filters.clear' => [
                    'en' => 'Clear filters', 'ar' => 'مسح عوامل التصفية', 'fr' => 'Effacer les filtres',
                    'es' => 'Borrar filtros', 'de' => 'Filter zurücksetzen', 'it' => 'Azzera i filtri',
                    'pt' => 'Limpar filtros', 'ru' => 'Сбросить фильтры', 'hi' => 'फ़िल्टर हटाएँ',
                ],
                'achievements.same_year' => [
                    'en' => 'From the same year', 'ar' => 'من العام نفسه', 'fr' => 'De la même année',
                    'es' => 'Del mismo año', 'de' => 'Aus demselben Jahr', 'it' => 'Dello stesso anno',
                    'pt' => 'Do mesmo ano', 'ru' => 'За тот же год', 'hi' => 'उसी वर्ष से',
                ],

                /* -----------------------------------------
                 Identity / brands
                ------------------------------------------*/

                'identity.view' => [
                    'en' => 'View identity', 'ar' => 'عرض الهوية', 'fr' => 'Voir l\'identité',
                    'es' => 'Ver identidad', 'de' => 'Identität ansehen', 'it' => 'Vedi identità',
                    'pt' => 'Ver identidade', 'ru' => 'Смотреть айдентику', 'hi' => 'पहचान देखें',
                ],
                'identity.download' => [
                    'en' => 'Download asset', 'ar' => 'تحميل الملف', 'fr' => 'Télécharger le fichier',
                    'es' => 'Descargar recurso', 'de' => 'Datei herunterladen', 'it' => 'Scarica il file',
                    'pt' => 'Descarregar ficheiro', 'ru' => 'Скачать файл', 'hi' => 'फ़ाइल डाउनलोड करें',
                ],
                'identity.empty' => [
                    'en' => 'No assets in this group yet.', 'ar' => 'لا توجد ملفات في هذه المجموعة بعد.',
                    'fr' => 'Aucun fichier dans ce groupe pour le moment.',
                    'es' => 'Todavía no hay recursos en este grupo.',
                    'de' => 'Noch keine Dateien in dieser Gruppe.', 'it' => 'Nessun file in questo gruppo finora.',
                    'pt' => 'Ainda não há ficheiros neste grupo.', 'ru' => 'В этой группе пока нет файлов.',
                    'hi' => 'इस समूह में अभी कोई फ़ाइल नहीं है।',
                ],
                'identity.table.name' => [
                    'en' => 'Name', 'ar' => 'الاسم', 'fr' => 'Nom', 'es' => 'Nombre', 'de' => 'Name',
                    'it' => 'Nome', 'pt' => 'Nome', 'ru' => 'Название', 'hi' => 'नाम',
                ],

                /* -----------------------------------------
                 Downloadable listings — guidelines, calendars, newsletters, documents
                ------------------------------------------*/

                'guidelines.panel' => [
                    'en' => 'Grade guidelines', 'ar' => 'أدلة الصفوف', 'fr' => 'Guides par niveau',
                    'es' => 'Guías por curso', 'de' => 'Richtlinien nach Klassenstufe',
                    'it' => 'Linee guida per classe', 'pt' => 'Orientações por ano', 'ru' => 'Руководства по классам',
                    'hi' => 'कक्षावार दिशानिर्देश',
                ],
                'guidelines.select_grade' => [
                    'en' => 'Select a grade', 'ar' => 'اختر صفًا', 'fr' => 'Sélectionnez un niveau',
                    'es' => 'Seleccione un curso', 'de' => 'Klassenstufe wählen', 'it' => 'Seleziona una classe',
                    'pt' => 'Selecione um ano', 'ru' => 'Выберите класс', 'hi' => 'कक्षा चुनें',
                ],
                'guidelines.table.grade' => [
                    'en' => 'Grade', 'ar' => 'الصف', 'fr' => 'Niveau', 'es' => 'Curso', 'de' => 'Klassenstufe',
                    'it' => 'Classe', 'pt' => 'Ano', 'ru' => 'Класс', 'hi' => 'कक्षा',
                ],
                'guidelines.all_grades' => [
                    'en' => 'All grades', 'ar' => 'كل الصفوف', 'fr' => 'Tous les niveaux',
                    'es' => 'Todos los cursos', 'de' => 'Alle Klassenstufen', 'it' => 'Tutte le classi',
                    'pt' => 'Todos os anos', 'ru' => 'Все классы', 'hi' => 'सभी कक्षाएँ',
                ],
                'guidelines.empty' => [
                    'en' => 'No files available.', 'ar' => 'لا توجد ملفات متاحة.', 'fr' => 'Aucun fichier disponible.',
                    'es' => 'No hay archivos disponibles.', 'de' => 'Keine Dateien verfügbar.',
                    'it' => 'Nessun file disponibile.', 'pt' => 'Não há ficheiros disponíveis.',
                    'ru' => 'Файлов нет.', 'hi' => 'कोई फ़ाइल उपलब्ध नहीं है।',
                ],
                'calendars.panel' => [
                    'en' => 'Academic calendars', 'ar' => 'التقويمات الدراسية', 'fr' => 'Calendriers scolaires',
                    'es' => 'Calendarios académicos', 'de' => 'Schulkalender', 'it' => 'Calendari scolastici',
                    'pt' => 'Calendários académicos', 'ru' => 'Учебные календари', 'hi' => 'शैक्षणिक कैलेंडर',
                ],
                'calendars.table.calendar' => [
                    'en' => 'Calendar', 'ar' => 'التقويم', 'fr' => 'Calendrier', 'es' => 'Calendario',
                    'de' => 'Kalender', 'it' => 'Calendario', 'pt' => 'Calendário', 'ru' => 'Календарь',
                    'hi' => 'कैलेंडर',
                ],
                'calendars.table.start' => [
                    'en' => 'Start date', 'ar' => 'تاريخ البداية', 'fr' => 'Date de début',
                    'es' => 'Fecha de inicio', 'de' => 'Startdatum', 'it' => 'Data di inizio',
                    'pt' => 'Data de início', 'ru' => 'Дата начала', 'hi' => 'प्रारंभ तिथि',
                ],
                'calendars.table.end' => [
                    'en' => 'End date', 'ar' => 'تاريخ النهاية', 'fr' => 'Date de fin',
                    'es' => 'Fecha de fin', 'de' => 'Enddatum', 'it' => 'Data di fine',
                    'pt' => 'Data de fim', 'ru' => 'Дата окончания', 'hi' => 'समाप्ति तिथि',
                ],
                'newsletters.panel' => [
                    'en' => 'Newsletters', 'ar' => 'النشرات', 'fr' => 'Bulletins', 'es' => 'Boletines',
                    'de' => 'Newsletter', 'it' => 'Newsletter', 'pt' => 'Boletins', 'ru' => 'Рассылки',
                    'hi' => 'न्यूज़लेटर',
                ],
                'documents.panel' => [
                    'en' => 'Documents and forms', 'ar' => 'المستندات والنماذج',
                    'fr' => 'Documents et formulaires', 'es' => 'Documentos y formularios',
                    'de' => 'Dokumente und Formulare', 'it' => 'Documenti e moduli',
                    'pt' => 'Documentos e formulários', 'ru' => 'Документы и формы',
                    'hi' => 'दस्तावेज़ और फ़ॉर्म',
                ],
                'table.file' => [
                    'en' => 'File', 'ar' => 'الملف', 'fr' => 'Fichier', 'es' => 'Archivo', 'de' => 'Datei',
                    'it' => 'File', 'pt' => 'Ficheiro', 'ru' => 'Файл', 'hi' => 'फ़ाइल',
                ],
                'table.action' => [
                    'en' => 'Action', 'ar' => 'الإجراء', 'fr' => 'Action', 'es' => 'Acción', 'de' => 'Aktion',
                    'it' => 'Azione', 'pt' => 'Ação', 'ru' => 'Действие', 'hi' => 'कार्रवाई',
                ],

                /* -----------------------------------------
                 Programmes
                ------------------------------------------*/

                'programs.streams' => [
                    'en' => 'Pathways', 'ar' => 'المسارات', 'fr' => 'Parcours', 'es' => 'Itinerarios',
                    'de' => 'Bildungswege', 'it' => 'Percorsi', 'pt' => 'Percursos', 'ru' => 'Направления',
                    'hi' => 'मार्ग',
                ],
                'programs.grades' => [
                    'en' => 'Grades', 'ar' => 'الصفوف', 'fr' => 'Niveaux', 'es' => 'Cursos',
                    'de' => 'Klassenstufen', 'it' => 'Classi', 'pt' => 'Anos', 'ru' => 'Классы',
                    'hi' => 'कक्षाएँ',
                ],

                /* -----------------------------------------
                 404
                ------------------------------------------*/

                'error.title' => [
                    'en' => 'Page not found', 'ar' => 'الصفحة غير موجودة', 'fr' => 'Page introuvable',
                    'es' => 'Página no encontrada', 'de' => 'Seite nicht gefunden', 'it' => 'Pagina non trovata',
                    'pt' => 'Página não encontrada', 'ru' => 'Страница не найдена', 'hi' => 'पृष्ठ नहीं मिला',
                ],
                'error.body' => [
                    'en' => 'The page you were looking for has moved or no longer exists.',
                    'ar' => 'الصفحة التي تبحث عنها انتقلت أو لم تعد موجودة.',
                    'fr' => 'La page que vous cherchez a été déplacée ou n\'existe plus.',
                    'es' => 'La página que busca se ha movido o ya no existe.',
                    'de' => 'Die gesuchte Seite wurde verschoben oder existiert nicht mehr.',
                    'it' => 'La pagina che cercavi è stata spostata o non esiste più.',
                    'pt' => 'A página que procura foi movida ou já não existe.',
                    'ru' => 'Страница, которую вы искали, перемещена или больше не существует.',
                    'hi' => 'आप जिस पृष्ठ को खोज रहे थे वह हटा दिया गया है या अब मौजूद नहीं है।',
                ],
            ],

            /*
             * Translation catalogue — group `forms` (lang/{code}/forms.php).
             *
             * Two things live here. The public form renderer's own chrome and refusal
             * messages, which existed as a hand-maintained lang/en/forms.php whose docblock
             * WRONGLY claimed it was registered as a group — it never was, so it had no
             * other locale and would have vanished from a fresh clone the moment lang/ was
             * gitignored. And the labels for the two system forms (contact, inquiries),
             * which are seeded from config/forms.php and shown by the renderer.
             *
             * Field LABELS on a form are translatable columns an admin edits in the builder;
             * these are the seed values and the surrounding copy.
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'forms' => [
                /* -----------------------------------------
                 Renderer chrome and refusals
                ------------------------------------------*/

                'invalid_session' => [
                    'en' => 'We could not verify this form submission. Please reload the page and try again.',
                    'ar' => 'تعذَّر التحقق من هذا الإرسال. يرجى تحديث الصفحة والمحاولة مرة أخرى.',
                    'fr' => 'Nous n\'avons pas pu vérifier cet envoi. Veuillez recharger la page et réessayer.',
                    'es' => 'No hemos podido verificar este envío. Recargue la página e inténtelo de nuevo.',
                    'de' => 'Diese Übermittlung konnte nicht überprüft werden. Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
                    'it' => 'Non è stato possibile verificare questo invio. Ricarica la pagina e riprova.',
                    'pt' => 'Não foi possível verificar este envio. Recarregue a página e tente novamente.',
                    'ru' => 'Не удалось проверить эту отправку. Обновите страницу и попробуйте снова.',
                    'hi' => 'हम इस सबमिशन की पुष्टि नहीं कर सके। कृपया पृष्ठ पुनः लोड करें और फिर प्रयास करें।',
                ],
                'expired' => [
                    'en' => 'This form has been open for a while. Please reload the page and submit again.',
                    'ar' => 'ظل هذا النموذج مفتوحًا لفترة طويلة. يرجى تحديث الصفحة وإعادة الإرسال.',
                    'fr' => 'Ce formulaire est ouvert depuis un moment. Veuillez recharger la page et renvoyer.',
                    'es' => 'Este formulario lleva un rato abierto. Recargue la página y envíelo de nuevo.',
                    'de' => 'Dieses Formular ist seit einiger Zeit geöffnet. Bitte laden Sie die Seite neu und senden Sie es erneut.',
                    'it' => 'Questo modulo è aperto da un po\'. Ricarica la pagina e invia di nuovo.',
                    'pt' => 'Este formulário está aberto há algum tempo. Recarregue a página e envie novamente.',
                    'ru' => 'Форма была открыта слишком долго. Обновите страницу и отправьте снова.',
                    'hi' => 'यह फ़ॉर्म काफ़ी देर से खुला है। कृपया पृष्ठ पुनः लोड करें और दोबारा भेजें।',
                ],
                'closed' => [
                    'en' => 'This form is no longer accepting responses.',
                    'ar' => 'لم يعد هذا النموذج يستقبل الردود.',
                    'fr' => 'Ce formulaire n\'accepte plus de réponses.',
                    'es' => 'Este formulario ya no acepta respuestas.',
                    'de' => 'Dieses Formular nimmt keine Antworten mehr entgegen.',
                    'it' => 'Questo modulo non accetta più risposte.',
                    'pt' => 'Este formulário já não aceita respostas.',
                    'ru' => 'Эта форма больше не принимает ответы.',
                    'hi' => 'यह फ़ॉर्म अब उत्तर स्वीकार नहीं कर रहा है।',
                ],
                'already_submitted' => [
                    'en' => 'You have already responded to this form.',
                    'ar' => 'لقد أرسلت ردًا على هذا النموذج بالفعل.',
                    'fr' => 'Vous avez déjà répondu à ce formulaire.',
                    'es' => 'Ya ha respondido a este formulario.',
                    'de' => 'Sie haben dieses Formular bereits beantwortet.',
                    'it' => 'Hai già risposto a questo modulo.',
                    'pt' => 'Já respondeu a este formulário.',
                    'ru' => 'Вы уже отправляли ответ на эту форму.',
                    'hi' => 'आप इस फ़ॉर्म का उत्तर पहले ही दे चुके हैं।',
                ],
                'blocked' => [
                    'en' => 'This form is not available from your location.',
                    'ar' => 'هذا النموذج غير متاح من موقعك.',
                    'fr' => 'Ce formulaire n\'est pas disponible depuis votre localisation.',
                    'es' => 'Este formulario no está disponible desde su ubicación.',
                    'de' => 'Dieses Formular ist von Ihrem Standort aus nicht verfügbar.',
                    'it' => 'Questo modulo non è disponibile dalla tua posizione.',
                    'pt' => 'Este formulário não está disponível a partir da sua localização.',
                    'ru' => 'Эта форма недоступна из вашего региона.',
                    'hi' => 'यह फ़ॉर्म आपके स्थान से उपलब्ध नहीं है।',
                ],
                'javascript_required' => [
                    'en' => 'This form needs JavaScript. Please enable it and reload the page.',
                    'ar' => 'يحتاج هذا النموذج إلى JavaScript. يرجى تفعيله وتحديث الصفحة.',
                    'fr' => 'Ce formulaire nécessite JavaScript. Veuillez l\'activer et recharger la page.',
                    'es' => 'Este formulario necesita JavaScript. Actívelo y recargue la página.',
                    'de' => 'Dieses Formular benötigt JavaScript. Bitte aktivieren Sie es und laden Sie die Seite neu.',
                    'it' => 'Questo modulo richiede JavaScript. Attivalo e ricarica la pagina.',
                    'pt' => 'Este formulário precisa de JavaScript. Ative-o e recarregue a página.',
                    'ru' => 'Для этой формы нужен JavaScript. Включите его и обновите страницу.',
                    'hi' => 'इस फ़ॉर्म को JavaScript चाहिए। कृपया इसे सक्षम करें और पृष्ठ पुनः लोड करें।',
                ],
                'captcha_failed' => [
                    'en' => 'The verification check did not pass. Please try again.',
                    'ar' => 'لم يجتز التحقق. يرجى المحاولة مرة أخرى.',
                    'fr' => 'La vérification a échoué. Veuillez réessayer.',
                    'es' => 'La verificación no se ha superado. Inténtelo de nuevo.',
                    'de' => 'Die Überprüfung war nicht erfolgreich. Bitte versuchen Sie es erneut.',
                    'it' => 'La verifica non è andata a buon fine. Riprova.',
                    'pt' => 'A verificação não foi bem-sucedida. Tente novamente.',
                    'ru' => 'Проверка не пройдена. Попробуйте ещё раз.',
                    'hi' => 'सत्यापन पूरा नहीं हुआ। कृपया पुनः प्रयास करें।',
                ],

                /* Navigation. The renderer hardcoded these in English; they are keys now. */

                'submit' => [
                    'en' => 'Submit', 'ar' => 'إرسال', 'fr' => 'Envoyer', 'es' => 'Enviar', 'de' => 'Absenden',
                    'it' => 'Invia', 'pt' => 'Enviar', 'ru' => 'Отправить', 'hi' => 'सबमिट करें',
                ],
                'next' => [
                    'en' => 'Next', 'ar' => 'التالي', 'fr' => 'Suivant', 'es' => 'Siguiente', 'de' => 'Weiter',
                    'it' => 'Avanti', 'pt' => 'Seguinte', 'ru' => 'Далее', 'hi' => 'अगला',
                ],
                'back' => [
                    'en' => 'Back', 'ar' => 'السابق', 'fr' => 'Retour', 'es' => 'Atrás', 'de' => 'Zurück',
                    'it' => 'Indietro', 'pt' => 'Voltar', 'ru' => 'Назад', 'hi' => 'पीछे',
                ],
                'sending' => [
                    'en' => 'Sending…', 'ar' => 'جارٍ الإرسال…', 'fr' => 'Envoi…', 'es' => 'Enviando…',
                    'de' => 'Wird gesendet…', 'it' => 'Invio in corso…', 'pt' => 'A enviar…',
                    'ru' => 'Отправка…', 'hi' => 'भेजा जा रहा है…',
                ],
                'step' => [
                    'en' => 'Step :current of :total', 'ar' => 'الخطوة :current من :total',
                    'fr' => 'Étape :current sur :total', 'es' => 'Paso :current de :total',
                    'de' => 'Schritt :current von :total', 'it' => 'Passaggio :current di :total',
                    'pt' => 'Passo :current de :total', 'ru' => 'Шаг :current из :total',
                    'hi' => 'चरण :current / :total',
                ],
                'uploading' => [
                    'en' => 'Uploading…', 'ar' => 'جارٍ الرفع…', 'fr' => 'Téléversement…', 'es' => 'Subiendo…',
                    'de' => 'Wird hochgeladen…', 'it' => 'Caricamento…', 'pt' => 'A carregar…',
                    'ru' => 'Загрузка…', 'hi' => 'अपलोड हो रहा है…',
                ],
                'remove_file' => [
                    'en' => 'Remove file', 'ar' => 'إزالة الملف', 'fr' => 'Retirer le fichier',
                    'es' => 'Quitar archivo', 'de' => 'Datei entfernen', 'it' => 'Rimuovi il file',
                    'pt' => 'Remover ficheiro', 'ru' => 'Удалить файл', 'hi' => 'फ़ाइल हटाएँ',
                ],

                /* -----------------------------------------
                 The two ways into a long form
                ------------------------------------------*/

                'fill_manually' => [
                    'en' => 'Fill in manually', 'ar' => 'التعبئة يدويًا', 'fr' => 'Remplir manuellement',
                    'es' => 'Rellenar manualmente', 'de' => 'Manuell ausfüllen', 'it' => 'Compila manualmente',
                    'pt' => 'Preencher manualmente', 'ru' => 'Заполнить вручную', 'hi' => 'स्वयं भरें',
                ],

                'fill_manually_hint' => [
                    'en' => 'Answer the questions yourself.', 'ar' => 'أجب عن الأسئلة بنفسك.',
                    'fr' => 'Répondez vous-même aux questions.', 'es' => 'Responda usted mismo.',
                    'de' => 'Beantworten Sie die Fragen selbst.', 'it' => 'Rispondi tu alle domande.',
                    'pt' => 'Responda você mesmo às perguntas.', 'ru' => 'Ответьте на вопросы сами.',
                    'hi' => 'प्रश्नों के उत्तर स्वयं दें।',
                ],

                'fill_with_ai' => [
                    'en' => 'Fill in from my CV', 'ar' => 'التعبئة من سيرتي الذاتية',
                    'fr' => 'Remplir depuis mon CV', 'es' => 'Rellenar desde mi CV',
                    'de' => 'Aus meinem Lebenslauf ausfüllen', 'it' => 'Compila dal mio CV',
                    'pt' => 'Preencher a partir do meu CV', 'ru' => 'Заполнить из резюме',
                    'hi' => 'मेरे बायोडाटा से भरें',
                ],

                'fill_with_ai_hint' => [
                    'en' => 'Upload your CV and we will fill in what we can. You can correct anything.',
                    'ar' => 'ارفع سيرتك الذاتية وسنملأ ما نستطيع، ويمكنك تصحيح أي شيء.',
                    'fr' => 'Téléversez votre CV : nous remplirons ce que nous pouvons, vous pourrez tout corriger.',
                    'es' => 'Suba su CV y rellenaremos lo que podamos. Podrá corregir lo que quiera.',
                    'de' => 'Laden Sie Ihren Lebenslauf hoch; wir füllen aus, was wir können — Sie können alles korrigieren.',
                    'it' => 'Carica il tuo CV: compileremo quello che possiamo e potrai correggere tutto.',
                    'pt' => 'Carregue o seu CV e preencheremos o que for possível. Pode corrigir tudo.',
                    'ru' => 'Загрузите резюме — мы заполним, что сможем. Вы сможете всё исправить.',
                    'hi' => 'अपना बायोडाटा अपलोड करें; हम जो भर सकते हैं भर देंगे और आप कुछ भी सुधार सकते हैं।',
                ],

                'cv_reading' => [
                    'en' => 'Reading your CV…', 'ar' => 'جارٍ قراءة سيرتك الذاتية…',
                    'fr' => 'Lecture de votre CV…', 'es' => 'Leyendo su CV…',
                    'de' => 'Lebenslauf wird gelesen…', 'it' => 'Lettura del CV…',
                    'pt' => 'A ler o seu CV…', 'ru' => 'Читаем резюме…', 'hi' => 'बायोडाटा पढ़ा जा रहा है…',
                ],

                'cv_failed' => [
                    'en' => 'That file could not be read. You can still fill the form in yourself.',
                    'ar' => 'تعذّرت قراءة الملف. لا يزال بإمكانك تعبئة النموذج بنفسك.',
                    'fr' => 'Ce fichier n’a pas pu être lu. Vous pouvez toujours remplir le formulaire vous-même.',
                    'es' => 'No se pudo leer el archivo. Aún puede rellenar el formulario usted mismo.',
                    'de' => 'Die Datei konnte nicht gelesen werden. Sie können das Formular weiterhin selbst ausfüllen.',
                    'it' => 'Non è stato possibile leggere il file. Puoi comunque compilare il modulo a mano.',
                    'pt' => 'Não foi possível ler o ficheiro. Pode preencher o formulário manualmente.',
                    'ru' => 'Не удалось прочитать файл. Вы можете заполнить форму вручную.',
                    'hi' => 'यह फ़ाइल पढ़ी नहीं जा सकी। आप फ़ॉर्म स्वयं भर सकते हैं।',
                ],

                /* -----------------------------------------
                 Job application guards

                 Refusals no single field can decide — the posting closed, this person
                 already applied, this CV was already sent. Worded for the APPLICANT,
                 who has done nothing wrong in any of the three cases.
                ------------------------------------------*/

                'job_offer_missing' => [
                    'en' => 'That position is no longer listed.',
                    'ar' => 'هذه الوظيفة لم تعد مدرجة.',
                    'fr' => 'Ce poste n’est plus proposé.',
                    'es' => 'Ese puesto ya no está disponible.',
                    'de' => 'Diese Stelle wird nicht mehr angeboten.',
                    'it' => 'Questa posizione non è più disponibile.',
                    'pt' => 'Essa vaga já não está disponível.',
                    'ru' => 'Эта вакансия больше не размещена.',
                    'hi' => 'यह पद अब सूचीबद्ध नहीं है।',
                ],

                'job_offer_closed' => [
                    'en' => 'This position has closed for applications.',
                    'ar' => 'أُغلق باب التقديم على هذه الوظيفة.',
                    'fr' => 'Les candidatures pour ce poste sont closes.',
                    'es' => 'Este puesto ya no admite candidaturas.',
                    'de' => 'Für diese Stelle werden keine Bewerbungen mehr angenommen.',
                    'it' => 'Le candidature per questa posizione sono chiuse.',
                    'pt' => 'As candidaturas para esta vaga estão encerradas.',
                    'ru' => 'Приём заявок на эту вакансию закрыт.',
                    'hi' => 'इस पद के लिए आवेदन बंद हो चुके हैं।',
                ],

                'job_already_applied' => [
                    'en' => 'You have already applied for this position.',
                    'ar' => 'لقد سبق أن تقدمت لهذه الوظيفة.',
                    'fr' => 'Vous avez déjà postulé à ce poste.',
                    'es' => 'Ya se ha presentado a este puesto.',
                    'de' => 'Sie haben sich bereits auf diese Stelle beworben.',
                    'it' => 'Ti sei già candidato per questa posizione.',
                    'pt' => 'Já se candidatou a esta vaga.',
                    'ru' => 'Вы уже подавали заявку на эту вакансию.',
                    'hi' => 'आप इस पद के लिए पहले ही आवेदन कर चुके हैं।',
                ],

                /* -----------------------------------------
                 Visit reservation guards

                 The same shape as the job guards above, and worded the same way: the
                 visitor has done nothing wrong in any of these cases. A slot fills up
                 or is called off while the page sits open in a tab, which on a booking
                 page is the normal case rather than an edge one.

                 visit_slot_mismatched is separate from visit_slot_full on purpose — a
                 slot attached to another visit is a mismatched booking, not a busy one,
                 and telling somebody it is "fully booked" would send them hunting for a
                 different time when the time was never the problem.
                ------------------------------------------*/

                'visit_slot_missing' => [
                    'en' => 'That time is no longer available.',
                    'ar' => 'هذا الموعد لم يعد متاحًا.',
                    'fr' => 'Ce créneau n’est plus disponible.',
                    'es' => 'Ese horario ya no está disponible.',
                    'de' => 'Dieser Termin ist nicht mehr verfügbar.',
                    'it' => 'Questo orario non è più disponibile.',
                    'pt' => 'Esse horário já não está disponível.',
                    'ru' => 'Это время больше недоступно.',
                    'hi' => 'यह समय अब उपलब्ध नहीं है।',
                ],

                'visit_slot_mismatched' => [
                    'en' => 'That time belongs to a different visit. Please pick one from the calendar.',
                    'ar' => 'هذا الموعد يخص زيارة أخرى. يرجى اختيار موعد من التقويم.',
                    'fr' => 'Ce créneau correspond à une autre visite. Veuillez en choisir un dans le calendrier.',
                    'es' => 'Ese horario pertenece a otra visita. Elija uno en el calendario.',
                    'de' => 'Dieser Termin gehört zu einem anderen Besuch. Bitte wählen Sie einen im Kalender.',
                    'it' => 'Questo orario appartiene a un’altra visita. Scegline uno dal calendario.',
                    'pt' => 'Esse horário pertence a outra visita. Escolha um no calendário.',
                    'ru' => 'Это время относится к другому визиту. Выберите время в календаре.',
                    'hi' => 'यह समय किसी अन्य भ्रमण का है। कृपया कैलेंडर से समय चुनें।',
                ],

                'visit_slot_closed' => [
                    'en' => 'That time is no longer open for booking.',
                    'ar' => 'أُغلق الحجز على هذا الموعد.',
                    'fr' => 'Ce créneau n’est plus ouvert à la réservation.',
                    'es' => 'Ese horario ya no admite reservas.',
                    'de' => 'Für diesen Termin sind keine Buchungen mehr möglich.',
                    'it' => 'Questo orario non è più prenotabile.',
                    'pt' => 'Esse horário já não aceita reservas.',
                    'ru' => 'Запись на это время закрыта.',
                    'hi' => 'इस समय के लिए बुकिंग बंद हो चुकी है।',
                ],

                'visit_slot_full' => [
                    'en' => 'That time is fully booked. Please choose another.',
                    'ar' => 'اكتمل العدد في هذا الموعد. يرجى اختيار موعد آخر.',
                    'fr' => 'Ce créneau est complet. Veuillez en choisir un autre.',
                    'es' => 'Ese horario está completo. Elija otro.',
                    'de' => 'Dieser Termin ist ausgebucht. Bitte wählen Sie einen anderen.',
                    'it' => 'Questo orario è al completo. Scegline un altro.',
                    'pt' => 'Esse horário está esgotado. Escolha outro.',
                    'ru' => 'На это время мест нет. Выберите другое.',
                    'hi' => 'यह समय पूरी तरह बुक हो चुका है। कृपया दूसरा चुनें।',
                ],

                'visit_already_booked' => [
                    'en' => 'You have already booked this time.',
                    'ar' => 'لقد سبق أن حجزت هذا الموعد.',
                    'fr' => 'Vous avez déjà réservé ce créneau.',
                    'es' => 'Ya ha reservado este horario.',
                    'de' => 'Sie haben diesen Termin bereits gebucht.',
                    'it' => 'Hai già prenotato questo orario.',
                    'pt' => 'Já reservou este horário.',
                    'ru' => 'Вы уже забронировали это время.',
                    'hi' => 'आप यह समय पहले ही बुक कर चुके हैं।',
                ],

                /* -----------------------------------------
                 Venue booking guards

                 The same five cases as the visit guards above, worded for a venue.
                 NOT shared with them: those sentences say "visit", and a person
                 booking the hall being told a *visit* is fully booked would read as
                 the site answering a different question.
                ------------------------------------------*/

                'facility_slot_missing' => [
                    'en' => 'That time is no longer available.',
                    'ar' => 'هذا الموعد لم يعد متاحًا.',
                    'fr' => 'Ce créneau n’est plus disponible.',
                    'es' => 'Ese horario ya no está disponible.',
                    'de' => 'Dieser Termin ist nicht mehr verfügbar.',
                    'it' => 'Questo orario non è più disponibile.',
                    'pt' => 'Esse horário já não está disponível.',
                    'ru' => 'Это время больше недоступно.',
                    'hi' => 'यह समय अब उपलब्ध नहीं है।',
                ],

                'facility_slot_mismatched' => [
                    'en' => 'That time belongs to a different venue. Please pick one from the calendar.',
                    'ar' => 'هذا الموعد يخص مرفقًا آخر. يرجى اختيار موعد من التقويم.',
                    'fr' => 'Ce créneau correspond à un autre espace. Veuillez en choisir un dans le calendrier.',
                    'es' => 'Ese horario pertenece a otro espacio. Elija uno en el calendario.',
                    'de' => 'Dieser Termin gehört zu einem anderen Bereich. Bitte wählen Sie einen im Kalender.',
                    'it' => 'Questo orario appartiene a un’altra struttura. Scegline uno dal calendario.',
                    'pt' => 'Esse horário pertence a outro espaço. Escolha um no calendário.',
                    'ru' => 'Это время относится к другой площадке. Выберите время в календаре.',
                    'hi' => 'यह समय किसी अन्य स्थल का है। कृपया कैलेंडर से समय चुनें।',
                ],

                'facility_slot_closed' => [
                    'en' => 'That time is no longer open for booking.',
                    'ar' => 'أُغلق الحجز على هذا الموعد.',
                    'fr' => 'Ce créneau n’est plus ouvert à la réservation.',
                    'es' => 'Ese horario ya no admite reservas.',
                    'de' => 'Für diesen Termin sind keine Buchungen mehr möglich.',
                    'it' => 'Questo orario non è più prenotabile.',
                    'pt' => 'Esse horário já não aceita reservas.',
                    'ru' => 'Запись на это время закрыта.',
                    'hi' => 'इस समय के लिए बुकिंग बंद हो चुकी है।',
                ],

                'facility_slot_full' => [
                    'en' => 'That time is fully booked. Please choose another.',
                    'ar' => 'اكتمل الحجز في هذا الموعد. يرجى اختيار موعد آخر.',
                    'fr' => 'Ce créneau est complet. Veuillez en choisir un autre.',
                    'es' => 'Ese horario está completo. Elija otro.',
                    'de' => 'Dieser Termin ist ausgebucht. Bitte wählen Sie einen anderen.',
                    'it' => 'Questo orario è al completo. Scegline un altro.',
                    'pt' => 'Esse horário está esgotado. Escolha outro.',
                    'ru' => 'На это время мест нет. Выберите другое.',
                    'hi' => 'यह समय पूरी तरह बुक हो चुका है। कृपया दूसरा चुनें।',
                ],

                'facility_already_booked' => [
                    'en' => 'You have already booked this time.',
                    'ar' => 'لقد سبق أن حجزت هذا الموعد.',
                    'fr' => 'Vous avez déjà réservé ce créneau.',
                    'es' => 'Ya ha reservado este horario.',
                    'de' => 'Sie haben diesen Termin bereits gebucht.',
                    'it' => 'Hai già prenotato questo orario.',
                    'pt' => 'Já reservou este horário.',
                    'ru' => 'Вы уже забронировали это время.',
                    'hi' => 'आप यह समय पहले ही बुक कर चुके हैं।',
                ],

                /* -----------------------------------------
                 Repeatable groups

                 ONE SET OF STRINGS FOR EVERY GROUP, interpolated with the group's own
                 translated label — :label is "Education", ":number" the row. A group
                 carries no per-group button copy of its own, so adding one to a form
                 needs no translation work at all.
                ------------------------------------------*/

                'group_add' => [
                    'en' => 'Add :label', 'ar' => 'إضافة :label', 'fr' => 'Ajouter :label',
                    'es' => 'Añadir :label', 'de' => ':label hinzufügen', 'it' => 'Aggiungi :label',
                    'pt' => 'Adicionar :label', 'ru' => 'Добавить :label', 'hi' => ':label जोड़ें',
                ],

                'group_remove' => [
                    'en' => 'Remove', 'ar' => 'إزالة', 'fr' => 'Retirer', 'es' => 'Quitar',
                    'de' => 'Entfernen', 'it' => 'Rimuovi', 'pt' => 'Remover', 'ru' => 'Удалить',
                    'hi' => 'हटाएँ',
                ],

                'group_item' => [
                    'en' => ':label :number', 'ar' => ':label :number', 'fr' => ':label :number',
                    'es' => ':label :number', 'de' => ':label :number', 'it' => ':label :number',
                    'pt' => ':label :number', 'ru' => ':label :number', 'hi' => ':label :number',
                ],

                /* -----------------------------------------
                 Confirmation page
                ------------------------------------------*/

                'thanks_title' => [
                    'en' => 'Thank you', 'ar' => 'شكرًا لك', 'fr' => 'Merci', 'es' => 'Gracias',
                    'de' => 'Vielen Dank', 'it' => 'Grazie', 'pt' => 'Obrigado', 'ru' => 'Спасибо',
                    'hi' => 'धन्यवाद',
                ],
                'thanks_reference' => [
                    'en' => 'Your reference is :reference.', 'ar' => 'رقمك المرجعي هو :reference.',
                    'fr' => 'Votre référence est :reference.', 'es' => 'Su referencia es :reference.',
                    'de' => 'Ihre Referenz lautet :reference.', 'it' => 'Il tuo riferimento è :reference.',
                    'pt' => 'A sua referência é :reference.', 'ru' => 'Ваш номер обращения: :reference.',
                    'hi' => 'आपका संदर्भ क्रमांक :reference है।',
                ],
            ],

            /*
             * Translation catalogue — group `jobs` (lang/{code}/jobs.php).
             *
             * The careers listing and the job detail page, plus the copy the application
             * wizard needs. The wizard itself is an INERT island in this build (no route, no
             * backend — see the plan's decision 4); its strings live here so that turning it
             * on later is a routing change and not a translation project.
             *
             * NOTE THE ENUM-KEYED FAMILIES. `employment_type.*`, `work_mode.*` and
             * `education_level.*` are keyed by the exact values in JobOffer's
             * EMPLOYMENT_TYPES / WORK_MODES / EDUCATION_LEVELS constants, so a view reads
             * @lang('jobs.employment_type.'.$job->employment_type) with no match block —
             * add a value to the constant and the missing key shows up in the Translations
             * page rather than as a silent fallthrough.
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'jobs' => [
                /* -----------------------------------------
                 Listing
                ------------------------------------------*/

                'search.placeholder' => [
                    'en' => 'Search vacancies…', 'ar' => 'ابحث في الوظائف…', 'fr' => 'Rechercher une offre…',
                    'es' => 'Buscar vacantes…', 'de' => 'Stellen durchsuchen…', 'it' => 'Cerca posizioni…',
                    'pt' => 'Pesquisar vagas…', 'ru' => 'Поиск вакансий…', 'hi' => 'रिक्तियाँ खोजें…',
                ],
                'search.button' => [
                    'en' => 'Search', 'ar' => 'بحث', 'fr' => 'Rechercher', 'es' => 'Buscar', 'de' => 'Suchen',
                    'it' => 'Cerca', 'pt' => 'Pesquisar', 'ru' => 'Найти', 'hi' => 'खोजें',
                ],
                'empty' => [
                    'en' => 'There are no open positions right now.',
                    'ar' => 'لا توجد وظائف شاغرة في الوقت الحالي.',
                    'fr' => 'Aucun poste n\'est ouvert pour le moment.',
                    'es' => 'En este momento no hay puestos vacantes.',
                    'de' => 'Derzeit sind keine Stellen ausgeschrieben.',
                    'it' => 'Al momento non ci sono posizioni aperte.',
                    'pt' => 'De momento não há vagas abertas.',
                    'ru' => 'Сейчас открытых вакансий нет.',
                    'hi' => 'इस समय कोई रिक्ति उपलब्ध नहीं है।',
                ],
                'view' => [
                    'en' => 'View details', 'ar' => 'عرض التفاصيل', 'fr' => 'Voir les détails',
                    'es' => 'Ver detalles', 'de' => 'Details ansehen', 'it' => 'Vedi i dettagli',
                    'pt' => 'Ver detalhes', 'ru' => 'Подробнее', 'hi' => 'विवरण देखें',
                ],
                'deadline' => [
                    'en' => 'Apply before', 'ar' => 'آخر موعد للتقديم', 'fr' => 'Postuler avant',
                    'es' => 'Solicitar antes del', 'de' => 'Bewerbungsschluss', 'it' => 'Candidati entro il',
                    'pt' => 'Candidatar-se até', 'ru' => 'Приём заявок до', 'hi' => 'आवेदन की अंतिम तिथि',
                ],
                'closing_soon' => [
                    'en' => 'Closing soon', 'ar' => 'يُغلق قريبًا', 'fr' => 'Bientôt clôturé',
                    'es' => 'Cierra pronto', 'de' => 'Endet bald', 'it' => 'In chiusura',
                    'pt' => 'A encerrar em breve', 'ru' => 'Скоро закроется', 'hi' => 'जल्द बंद हो रहा है',
                ],
                'posted' => [
                    'en' => 'Posted', 'ar' => 'تاريخ النشر', 'fr' => 'Publié le', 'es' => 'Publicado',
                    'de' => 'Veröffentlicht', 'it' => 'Pubblicato', 'pt' => 'Publicado',
                    'ru' => 'Опубликовано', 'hi' => 'प्रकाशित',
                ],
                'positions' => [
                    'en' => 'Positions available', 'ar' => 'عدد الشواغر', 'fr' => 'Postes à pourvoir',
                    'es' => 'Puestos disponibles', 'de' => 'Verfügbare Stellen', 'it' => 'Posizioni disponibili',
                    'pt' => 'Vagas disponíveis', 'ru' => 'Количество мест', 'hi' => 'उपलब्ध पद',
                ],

                /* -----------------------------------------
                 Listing filters — the sidebar block on /jobs
                ------------------------------------------*/

                'filters.title' => [
                    'en' => 'Filters', 'ar' => 'عوامل التصفية', 'fr' => 'Filtres', 'es' => 'Filtros',
                    'de' => 'Filter', 'it' => 'Filtri', 'pt' => 'Filtros', 'ru' => 'Фильтры',
                    'hi' => 'फ़िल्टर',
                ],
                'filters.categories' => [
                    'en' => 'Categories', 'ar' => 'التصنيفات', 'fr' => 'Catégories', 'es' => 'Categorías',
                    'de' => 'Kategorien', 'it' => 'Categorie', 'pt' => 'Categorias', 'ru' => 'Категории',
                    'hi' => 'श्रेणियाँ',
                ],
                'filters.employment_type' => [
                    'en' => 'Employment type', 'ar' => 'نوع التوظيف', 'fr' => 'Type de contrat',
                    'es' => 'Tipo de empleo', 'de' => 'Beschäftigungsart', 'it' => 'Tipo di contratto',
                    'pt' => 'Tipo de contrato', 'ru' => 'Тип занятости', 'hi' => 'रोज़गार का प्रकार',
                ],
                'filters.work_mode' => [
                    'en' => 'Work type', 'ar' => 'نمط العمل', 'fr' => 'Mode de travail',
                    'es' => 'Modalidad', 'de' => 'Arbeitsform', 'it' => 'Modalità di lavoro',
                    'pt' => 'Regime de trabalho', 'ru' => 'Формат работы', 'hi' => 'कार्य का प्रकार',
                ],
                /*
                 * ONE `all` OPTION SERVES THREE LISTS — categories, employment types and
                 * work modes — so the Romance locales take the INVARIABLE form (fr `Tout`,
                 * es `Todo`, it `Tutto`, pt `Tudo`) rather than a gendered plural: `Tous`
                 * would be wrong above the feminine `Catégories` and `Toutes` wrong above
                 * the masculine `Type de contrat`.
                 */
                'filters.all' => [
                    'en' => 'All', 'ar' => 'الكل', 'fr' => 'Tout', 'es' => 'Todo', 'de' => 'Alle',
                    'it' => 'Tutto', 'pt' => 'Tudo', 'ru' => 'Все', 'hi' => 'सभी',
                ],
                /*
                 * The empty option of the two <select> filters in the aside. Keyed
                 * `filters.all_<key>` to match `filters.<key>` above, so the one loop that
                 * renders both selects derives the heading and the empty option from the
                 * same key. They are SEPARATE from `filters.all` because a select's empty
                 * option names the thing it clears — a bare "All" floating in a dropdown
                 * reads as an option rather than as the absence of one — and because that
                 * lets each locale agree with its own noun, which the deliberately
                 * invariable `filters.all` above cannot do.
                 */
                'filters.all_employment_type' => [
                    'en' => 'All employment types', 'ar' => 'جميع أنواع التوظيف',
                    'fr' => 'Tous les types de contrat', 'es' => 'Todos los tipos de empleo',
                    'de' => 'Alle Beschäftigungsarten', 'it' => 'Tutti i tipi di contratto',
                    'pt' => 'Todos os tipos de contrato', 'ru' => 'Все типы занятости',
                    'hi' => 'सभी रोज़गार प्रकार',
                ],
                'filters.all_work_mode' => [
                    'en' => 'All work types', 'ar' => 'جميع أنماط العمل',
                    'fr' => 'Tous les modes de travail', 'es' => 'Todas las modalidades',
                    'de' => 'Alle Arbeitsformen', 'it' => 'Tutte le modalità di lavoro',
                    'pt' => 'Todos os regimes de trabalho', 'ru' => 'Все форматы работы',
                    'hi' => 'सभी कार्य प्रकार',
                ],
                'filters.clear' => [
                    'en' => 'Clear all filters', 'ar' => 'مسح جميع عوامل التصفية',
                    'fr' => 'Effacer tous les filtres', 'es' => 'Borrar todos los filtros',
                    'de' => 'Alle Filter zurücksetzen', 'it' => 'Azzera tutti i filtri',
                    'pt' => 'Limpar todos os filtros', 'ru' => 'Сбросить все фильтры',
                    'hi' => 'सभी फ़िल्टर हटाएँ',
                ],
                /*
                 * `:count` IS A PLAIN `__()` REPLACEMENT, NOT `trans_choice` — one string has
                 * to be right for 1 and for 300. So every locale is written as a LABEL
                 * (`Vacancies: 1`) instead of a counted phrase (`1 vacancies`): the noun then
                 * never has to agree with the number, which is what would otherwise break
                 * English/French/Hindi (singular after 1), Arabic (dual, plus a different
                 * case for 3–10 and 11+) and Russian (three plural forms). Add a pluralised
                 * variant only via `trans_choice` and a `|` string, never by editing these.
                 */
                'results' => [
                    'en' => 'Vacancies: :count', 'ar' => 'الوظائف الشاغرة: :count',
                    'fr' => 'Postes à pourvoir : :count', 'es' => 'Vacantes: :count',
                    'de' => 'Stellen: :count', 'it' => 'Posizioni: :count', 'pt' => 'Vagas: :count',
                    'ru' => 'Вакансии: :count', 'hi' => 'रिक्तियाँ: :count',
                ],

                /* -----------------------------------------
                 Enum families — keyed by JobOffer's constants
                ------------------------------------------*/

                'employment_type.full_time' => [
                    'en' => 'Full time', 'ar' => 'دوام كامل', 'fr' => 'Temps plein', 'es' => 'Jornada completa',
                    'de' => 'Vollzeit', 'it' => 'Tempo pieno', 'pt' => 'Tempo inteiro',
                    'ru' => 'Полная занятость', 'hi' => 'पूर्णकालिक',
                ],
                'employment_type.part_time' => [
                    'en' => 'Part time', 'ar' => 'دوام جزئي', 'fr' => 'Temps partiel', 'es' => 'Media jornada',
                    'de' => 'Teilzeit', 'it' => 'Tempo parziale', 'pt' => 'Tempo parcial',
                    'ru' => 'Частичная занятость', 'hi' => 'अंशकालिक',
                ],
                'employment_type.contractor' => [
                    'en' => 'Contract', 'ar' => 'بعقد', 'fr' => 'Contrat', 'es' => 'Por contrato',
                    'de' => 'Auftrag', 'it' => 'A contratto', 'pt' => 'Por contrato',
                    'ru' => 'По договору', 'hi' => 'अनुबंध',
                ],
                'employment_type.temporary' => [
                    'en' => 'Temporary', 'ar' => 'مؤقت', 'fr' => 'Temporaire', 'es' => 'Temporal',
                    'de' => 'Befristet', 'it' => 'Temporaneo', 'pt' => 'Temporário',
                    'ru' => 'Временная', 'hi' => 'अस्थायी',
                ],
                'employment_type.intern' => [
                    'en' => 'Internship', 'ar' => 'تدريب', 'fr' => 'Stage', 'es' => 'Prácticas',
                    'de' => 'Praktikum', 'it' => 'Tirocinio', 'pt' => 'Estágio',
                    'ru' => 'Стажировка', 'hi' => 'इंटर्नशिप',
                ],
                'employment_type.volunteer' => [
                    'en' => 'Volunteer', 'ar' => 'تطوُّعي', 'fr' => 'Bénévolat', 'es' => 'Voluntariado',
                    'de' => 'Ehrenamtlich', 'it' => 'Volontariato', 'pt' => 'Voluntariado',
                    'ru' => 'Волонтёрство', 'hi' => 'स्वयंसेवी',
                ],
                'employment_type.per_diem' => [
                    'en' => 'Per diem', 'ar' => 'بالأجر اليومي', 'fr' => 'À la journée', 'es' => 'Por días',
                    'de' => 'Auf Tagesbasis', 'it' => 'A giornata', 'pt' => 'Ao dia',
                    'ru' => 'Подённая', 'hi' => 'प्रतिदिन',
                ],
                'employment_type.other' => [
                    'en' => 'Other', 'ar' => 'أخرى', 'fr' => 'Autre', 'es' => 'Otro', 'de' => 'Sonstiges',
                    'it' => 'Altro', 'pt' => 'Outro', 'ru' => 'Другое', 'hi' => 'अन्य',
                ],

                'work_mode.onsite' => [
                    'en' => 'On site', 'ar' => 'من مقر العمل', 'fr' => 'Sur site', 'es' => 'Presencial',
                    'de' => 'Vor Ort', 'it' => 'In sede', 'pt' => 'Presencial', 'ru' => 'В офисе',
                    'hi' => 'कार्यस्थल पर',
                ],
                'work_mode.hybrid' => [
                    'en' => 'Hybrid', 'ar' => 'هجين', 'fr' => 'Hybride', 'es' => 'Híbrido', 'de' => 'Hybrid',
                    'it' => 'Ibrido', 'pt' => 'Híbrido', 'ru' => 'Гибридный', 'hi' => 'हाइब्रिड',
                ],
                'work_mode.remote' => [
                    'en' => 'Remote', 'ar' => 'عن بُعد', 'fr' => 'À distance', 'es' => 'En remoto',
                    'de' => 'Remote', 'it' => 'Da remoto', 'pt' => 'Remoto', 'ru' => 'Удалённо',
                    'hi' => 'दूरस्थ',
                ],

                'education_level.high_school' => [
                    'en' => 'High school', 'ar' => 'الثانوية العامة', 'fr' => 'Baccalauréat',
                    'es' => 'Bachillerato', 'de' => 'Schulabschluss', 'it' => 'Diploma di scuola superiore',
                    'pt' => 'Ensino secundário', 'ru' => 'Среднее образование', 'hi' => 'हाई स्कूल',
                ],
                'education_level.associate' => [
                    'en' => 'Associate degree', 'ar' => 'دبلوم متوسط', 'fr' => 'Diplôme de premier cycle',
                    'es' => 'Grado medio', 'de' => 'Associate Degree', 'it' => 'Diploma universitario',
                    'pt' => 'Grau associado', 'ru' => 'Неполное высшее', 'hi' => 'एसोसिएट डिग्री',
                ],
                'education_level.bachelor' => [
                    'en' => 'Bachelor\'s degree', 'ar' => 'درجة البكالوريوس', 'fr' => 'Licence',
                    'es' => 'Grado universitario', 'de' => 'Bachelor', 'it' => 'Laurea triennale',
                    'pt' => 'Licenciatura', 'ru' => 'Бакалавриат', 'hi' => 'स्नातक डिग्री',
                ],
                'education_level.professional_certificate' => [
                    'en' => 'Professional certificate', 'ar' => 'شهادة مهنية',
                    'fr' => 'Certificat professionnel', 'es' => 'Certificado profesional',
                    'de' => 'Berufszertifikat', 'it' => 'Certificazione professionale',
                    'pt' => 'Certificado profissional', 'ru' => 'Профессиональный сертификат',
                    'hi' => 'व्यावसायिक प्रमाणपत्र',
                ],
                'education_level.postgraduate' => [
                    'en' => 'Postgraduate degree', 'ar' => 'دراسات عليا', 'fr' => 'Master ou doctorat',
                    'es' => 'Posgrado', 'de' => 'Postgraduiertenabschluss', 'it' => 'Laurea magistrale',
                    'pt' => 'Pós-graduação', 'ru' => 'Магистратура или аспирантура',
                    'hi' => 'स्नातकोत्तर डिग्री',
                ],

                /* -----------------------------------------
                 Detail page
                ------------------------------------------*/

                'detail.description' => [
                    'en' => 'About the role', 'ar' => 'عن الوظيفة', 'fr' => 'À propos du poste',
                    'es' => 'Sobre el puesto', 'de' => 'Über die Stelle', 'it' => 'Informazioni sul ruolo',
                    'pt' => 'Sobre a função', 'ru' => 'О вакансии', 'hi' => 'भूमिका के बारे में',
                ],
                'detail.experience' => [
                    'en' => 'Years of experience', 'ar' => 'سنوات الخبرة', 'fr' => 'Années d’expérience',
                    'es' => 'Años de experiencia', 'de' => 'Berufsjahre', 'it' => 'Anni di esperienza',
                    'pt' => 'Anos de experiência', 'ru' => 'Опыт работы (лет)', 'hi' => 'अनुभव (वर्ष)',
                ],

                'detail.employment_type' => [
                    'en' => 'Employment type', 'ar' => 'نوع التوظيف', 'fr' => 'Type de contrat',
                    'es' => 'Tipo de empleo', 'de' => 'Beschäftigungsart', 'it' => 'Tipo di contratto',
                    'pt' => 'Tipo de contrato', 'ru' => 'Тип занятости', 'hi' => 'रोज़गार का प्रकार',
                ],

                'detail.work_mode' => [
                    'en' => 'Work type', 'ar' => 'نمط العمل', 'fr' => 'Mode de travail',
                    'es' => 'Modalidad', 'de' => 'Arbeitsform', 'it' => 'Modalità di lavoro',
                    'pt' => 'Regime de trabalho', 'ru' => 'Формат работы', 'hi' => 'कार्य का प्रकार',
                ],

                'detail.education_level' => [
                    'en' => 'Education', 'ar' => 'المؤهل العلمي', 'fr' => 'Niveau d’études',
                    'es' => 'Formación', 'de' => 'Ausbildung', 'it' => 'Titolo di studio',
                    'pt' => 'Habilitações', 'ru' => 'Образование', 'hi' => 'शिक्षा',
                ],

                'detail.skills' => [
                    'en' => 'Required skills', 'ar' => 'المهارات المطلوبة', 'fr' => 'Compétences requises',
                    'es' => 'Competencias requeridas', 'de' => 'Erforderliche Fähigkeiten',
                    'it' => 'Competenze richieste', 'pt' => 'Competências exigidas',
                    'ru' => 'Требуемые навыки', 'hi' => 'आवश्यक कौशल',
                ],
                'detail.summary' => [
                    'en' => 'At a glance', 'ar' => 'نظرة سريعة', 'fr' => 'En bref', 'es' => 'Resumen',
                    'de' => 'Auf einen Blick', 'it' => 'In sintesi', 'pt' => 'Em resumo',
                    'ru' => 'Кратко', 'hi' => 'एक नज़र में',
                ],
                'detail.about_school' => [
                    'en' => 'About the school', 'ar' => 'عن المدرسة', 'fr' => 'À propos de l\'école',
                    'es' => 'Sobre la escuela', 'de' => 'Über die Schule', 'it' => 'Informazioni sulla scuola',
                    'pt' => 'Sobre a escola', 'ru' => 'О школе', 'hi' => 'स्कूल के बारे में',
                ],
                'detail.address' => [
                    'en' => 'Location', 'ar' => 'الموقع', 'fr' => 'Lieu', 'es' => 'Ubicación', 'de' => 'Standort',
                    'it' => 'Sede', 'pt' => 'Localização', 'ru' => 'Место работы', 'hi' => 'स्थान',
                ],
                'detail.share' => [
                    'en' => 'Share this vacancy', 'ar' => 'شارك هذه الوظيفة', 'fr' => 'Partager cette offre',
                    'es' => 'Compartir esta vacante', 'de' => 'Stelle teilen', 'it' => 'Condividi questa posizione',
                    'pt' => 'Partilhar esta vaga', 'ru' => 'Поделиться вакансией', 'hi' => 'यह रिक्ति साझा करें',
                ],
                'detail.link_copied' => [
                    'en' => 'Link copied', 'ar' => 'تم نسخ الرابط', 'fr' => 'Lien copié', 'es' => 'Enlace copiado',
                    'de' => 'Link kopiert', 'it' => 'Link copiato', 'pt' => 'Ligação copiada',
                    'ru' => 'Ссылка скопирована', 'hi' => 'लिंक कॉपी हो गया',
                ],
                'detail.expired' => [
                    'en' => 'This vacancy is closed.', 'ar' => 'هذه الوظيفة مغلقة.',
                    'fr' => 'Cette offre est clôturée.', 'es' => 'Esta vacante está cerrada.',
                    'de' => 'Diese Stelle ist geschlossen.', 'it' => 'Questa posizione è chiusa.',
                    'pt' => 'Esta vaga está encerrada.', 'ru' => 'Приём заявок закрыт.',
                    'hi' => 'यह रिक्ति बंद हो चुकी है।',
                ],

                /* -----------------------------------------
                 Application wizard (inert in this build)
                ------------------------------------------*/

                'apply.cta' => [
                    'en' => 'Apply now', 'ar' => 'قدِّم الآن', 'fr' => 'Postuler', 'es' => 'Solicitar ahora',
                    'de' => 'Jetzt bewerben', 'it' => 'Candidati ora', 'pt' => 'Candidatar-se',
                    'ru' => 'Откликнуться', 'hi' => 'अभी आवेदन करें',
                ],
                'apply.abort' => [
                    'en' => 'Cancel', 'ar' => 'إلغاء', 'fr' => 'Annuler', 'es' => 'Cancelar',
                    'de' => 'Abbrechen', 'it' => 'Annulla', 'pt' => 'Cancelar',
                    'ru' => 'Отмена', 'hi' => 'रद्द करें',
                ],

                'apply.title' => [
                    'en' => 'Application', 'ar' => 'طلب التوظيف', 'fr' => 'Candidature', 'es' => 'Solicitud',
                    'de' => 'Bewerbung', 'it' => 'Candidatura', 'pt' => 'Candidatura', 'ru' => 'Заявка',
                    'hi' => 'आवेदन',
                ],
                'apply.general' => [
                    'en' => 'General application', 'ar' => 'طلب توظيف عام', 'fr' => 'Candidature spontanée',
                    'es' => 'Candidatura espontánea', 'de' => 'Initiativbewerbung',
                    'it' => 'Candidatura spontanea', 'pt' => 'Candidatura espontânea',
                    'ru' => 'Общая заявка', 'hi' => 'सामान्य आवेदन',
                ],

                'apply.steps.personal' => [
                    'en' => 'Personal details', 'ar' => 'البيانات الشخصية', 'fr' => 'Informations personnelles',
                    'es' => 'Datos personales', 'de' => 'Persönliche Angaben', 'it' => 'Dati personali',
                    'pt' => 'Dados pessoais', 'ru' => 'Личные данные', 'hi' => 'व्यक्तिगत विवरण',
                ],
                'apply.steps.education' => [
                    'en' => 'Education', 'ar' => 'المؤهلات العلمية', 'fr' => 'Formation', 'es' => 'Formación',
                    'de' => 'Ausbildung', 'it' => 'Istruzione', 'pt' => 'Formação', 'ru' => 'Образование',
                    'hi' => 'शिक्षा',
                ],
                'apply.steps.experience' => [
                    'en' => 'Experience', 'ar' => 'الخبرات', 'fr' => 'Expérience', 'es' => 'Experiencia',
                    'de' => 'Berufserfahrung', 'it' => 'Esperienza', 'pt' => 'Experiência', 'ru' => 'Опыт',
                    'hi' => 'अनुभव',
                ],
                'apply.steps.languages' => [
                    'en' => 'Languages', 'ar' => 'اللغات', 'fr' => 'Langues', 'es' => 'Idiomas',
                    'de' => 'Sprachen', 'it' => 'Lingue', 'pt' => 'Idiomas', 'ru' => 'Языки', 'hi' => 'भाषाएँ',
                ],
                'apply.steps.skills' => [
                    'en' => 'Skills', 'ar' => 'المهارات', 'fr' => 'Compétences', 'es' => 'Competencias',
                    'de' => 'Fähigkeiten', 'it' => 'Competenze', 'pt' => 'Competências', 'ru' => 'Навыки',
                    'hi' => 'कौशल',
                ],
                'apply.steps.documents' => [
                    'en' => 'Documents', 'ar' => 'المستندات', 'fr' => 'Documents', 'es' => 'Documentos',
                    'de' => 'Dokumente', 'it' => 'Documenti', 'pt' => 'Documentos', 'ru' => 'Документы',
                    'hi' => 'दस्तावेज़',
                ],
                'apply.steps.review' => [
                    'en' => 'Review and submit', 'ar' => 'المراجعة والإرسال', 'fr' => 'Vérifier et envoyer',
                    'es' => 'Revisar y enviar', 'de' => 'Prüfen und absenden', 'it' => 'Rivedi e invia',
                    'pt' => 'Rever e enviar', 'ru' => 'Проверить и отправить', 'hi' => 'समीक्षा करें और भेजें',
                ],

                'apply.fields.first_name' => [
                    'en' => 'First name', 'ar' => 'الاسم الأول', 'fr' => 'Prénom', 'es' => 'Nombre',
                    'de' => 'Vorname', 'it' => 'Nome', 'pt' => 'Nome próprio', 'ru' => 'Имя', 'hi' => 'पहला नाम',
                ],
                'apply.fields.last_name' => [
                    'en' => 'Last name', 'ar' => 'اسم العائلة', 'fr' => 'Nom', 'es' => 'Apellidos',
                    'de' => 'Nachname', 'it' => 'Cognome', 'pt' => 'Apelido', 'ru' => 'Фамилия',
                    'hi' => 'अंतिम नाम',
                ],
                'apply.fields.email' => [
                    'en' => 'Email address', 'ar' => 'البريد الإلكتروني', 'fr' => 'Adresse e-mail',
                    'es' => 'Correo electrónico', 'de' => 'E-Mail-Adresse', 'it' => 'Indirizzo e-mail',
                    'pt' => 'Endereço de e-mail', 'ru' => 'Электронная почта', 'hi' => 'ईमेल पता',
                ],
                'apply.fields.phone' => [
                    'en' => 'Phone number', 'ar' => 'رقم الهاتف', 'fr' => 'Numéro de téléphone',
                    'es' => 'Número de teléfono', 'de' => 'Telefonnummer', 'it' => 'Numero di telefono',
                    'pt' => 'Número de telefone', 'ru' => 'Номер телефона', 'hi' => 'फ़ोन नंबर',
                ],
                'apply.fields.nationality' => [
                    'en' => 'Nationality', 'ar' => 'الجنسية', 'fr' => 'Nationalité', 'es' => 'Nacionalidad',
                    'de' => 'Staatsangehörigkeit', 'it' => 'Nazionalità', 'pt' => 'Nacionalidade',
                    'ru' => 'Гражданство', 'hi' => 'राष्ट्रीयता',
                ],
                'apply.fields.address' => [
                    'en' => 'Address', 'ar' => 'العنوان', 'fr' => 'Adresse', 'es' => 'Dirección',
                    'de' => 'Adresse', 'it' => 'Indirizzo', 'pt' => 'Morada', 'ru' => 'Адрес', 'hi' => 'पता',
                ],
                'apply.fields.date_of_birth' => [
                    'en' => 'Date of birth', 'ar' => 'تاريخ الميلاد', 'fr' => 'Date de naissance',
                    'es' => 'Fecha de nacimiento', 'de' => 'Geburtsdatum', 'it' => 'Data di nascita',
                    'pt' => 'Data de nascimento', 'ru' => 'Дата рождения', 'hi' => 'जन्म तिथि',
                ],
                'apply.fields.institution' => [
                    'en' => 'Institution', 'ar' => 'المؤسسة التعليمية', 'fr' => 'Établissement',
                    'es' => 'Institución', 'de' => 'Bildungseinrichtung', 'it' => 'Istituto',
                    'pt' => 'Instituição', 'ru' => 'Учебное заведение', 'hi' => 'संस्थान',
                ],
                'apply.fields.degree' => [
                    'en' => 'Degree', 'ar' => 'الدرجة العلمية', 'fr' => 'Diplôme', 'es' => 'Titulación',
                    'de' => 'Abschluss', 'it' => 'Titolo di studio', 'pt' => 'Grau', 'ru' => 'Степень',
                    'hi' => 'डिग्री',
                ],
                'apply.fields.field_of_study' => [
                    'en' => 'Field of study', 'ar' => 'مجال الدراسة', 'fr' => 'Domaine d\'études',
                    'es' => 'Campo de estudio', 'de' => 'Studienfach', 'it' => 'Campo di studio',
                    'pt' => 'Área de estudo', 'ru' => 'Специальность', 'hi' => 'अध्ययन क्षेत्र',
                ],
                'apply.fields.company' => [
                    'en' => 'Company', 'ar' => 'جهة العمل', 'fr' => 'Entreprise', 'es' => 'Empresa',
                    'de' => 'Unternehmen', 'it' => 'Azienda', 'pt' => 'Empresa', 'ru' => 'Организация',
                    'hi' => 'कंपनी',
                ],
                'apply.fields.job_title' => [
                    'en' => 'Job title', 'ar' => 'المسمى الوظيفي', 'fr' => 'Intitulé du poste',
                    'es' => 'Puesto', 'de' => 'Position', 'it' => 'Posizione', 'pt' => 'Cargo',
                    'ru' => 'Должность', 'hi' => 'पद',
                ],
                'apply.fields.start_year' => [
                    'en' => 'Start year', 'ar' => 'سنة البداية', 'fr' => 'Année de début',
                    'es' => 'Año de inicio', 'de' => 'Startjahr', 'it' => 'Anno di inizio',
                    'pt' => 'Ano de início', 'ru' => 'Год начала', 'hi' => 'प्रारंभ वर्ष',
                ],
                'apply.fields.end_year' => [
                    'en' => 'End year', 'ar' => 'سنة النهاية', 'fr' => 'Année de fin', 'es' => 'Año de fin',
                    'de' => 'Endjahr', 'it' => 'Anno di fine', 'pt' => 'Ano de fim', 'ru' => 'Год окончания',
                    'hi' => 'समाप्ति वर्ष',
                ],
                'apply.fields.current_job' => [
                    'en' => 'I currently work here', 'ar' => 'أعمل هنا حاليًا',
                    'fr' => 'J\'occupe actuellement ce poste', 'es' => 'Trabajo aquí actualmente',
                    'de' => 'Ich arbeite derzeit hier', 'it' => 'Lavoro qui attualmente',
                    'pt' => 'Trabalho aqui atualmente', 'ru' => 'Работаю здесь сейчас',
                    'hi' => 'मैं वर्तमान में यहाँ कार्यरत हूँ',
                ],
                'apply.fields.description' => [
                    'en' => 'Description', 'ar' => 'الوصف', 'fr' => 'Description', 'es' => 'Descripción',
                    'de' => 'Beschreibung', 'it' => 'Descrizione', 'pt' => 'Descrição', 'ru' => 'Описание',
                    'hi' => 'विवरण',
                ],
                'apply.fields.language' => [
                    'en' => 'Language', 'ar' => 'اللغة', 'fr' => 'Langue', 'es' => 'Idioma', 'de' => 'Sprache',
                    'it' => 'Lingua', 'pt' => 'Idioma', 'ru' => 'Язык', 'hi' => 'भाषा',
                ],
                'apply.fields.proficiency' => [
                    'en' => 'Proficiency', 'ar' => 'مستوى الإتقان', 'fr' => 'Niveau', 'es' => 'Nivel',
                    'de' => 'Niveau', 'it' => 'Livello', 'pt' => 'Nível', 'ru' => 'Уровень владения',
                    'hi' => 'दक्षता',
                ],
                'apply.fields.skill' => [
                    'en' => 'Add a skill', 'ar' => 'أضف مهارة', 'fr' => 'Ajouter une compétence',
                    'es' => 'Añadir competencia', 'de' => 'Fähigkeit hinzufügen', 'it' => 'Aggiungi competenza',
                    'pt' => 'Adicionar competência', 'ru' => 'Добавить навык', 'hi' => 'कौशल जोड़ें',
                ],

                'apply.proficiency.basic' => [
                    'en' => 'Basic', 'ar' => 'مبتدئ', 'fr' => 'Notions', 'es' => 'Básico', 'de' => 'Grundkenntnisse',
                    'it' => 'Base', 'pt' => 'Básico', 'ru' => 'Базовый', 'hi' => 'बुनियादी',
                ],
                'apply.proficiency.intermediate' => [
                    'en' => 'Intermediate', 'ar' => 'متوسط', 'fr' => 'Intermédiaire', 'es' => 'Intermedio',
                    'de' => 'Mittel', 'it' => 'Intermedio', 'pt' => 'Intermédio', 'ru' => 'Средний',
                    'hi' => 'मध्यम',
                ],
                'apply.proficiency.advanced' => [
                    'en' => 'Advanced', 'ar' => 'متقدم', 'fr' => 'Avancé', 'es' => 'Avanzado',
                    'de' => 'Fortgeschritten', 'it' => 'Avanzato', 'pt' => 'Avançado', 'ru' => 'Продвинутый',
                    'hi' => 'उन्नत',
                ],
                'apply.proficiency.native' => [
                    'en' => 'Native', 'ar' => 'اللغة الأم', 'fr' => 'Langue maternelle', 'es' => 'Nativo',
                    'de' => 'Muttersprache', 'it' => 'Madrelingua', 'pt' => 'Nativo', 'ru' => 'Родной',
                    'hi' => 'मातृभाषा',
                ],

                'apply.actions.add_education' => [
                    'en' => 'Add education', 'ar' => 'أضف مؤهلًا', 'fr' => 'Ajouter une formation',
                    'es' => 'Añadir formación', 'de' => 'Ausbildung hinzufügen', 'it' => 'Aggiungi istruzione',
                    'pt' => 'Adicionar formação', 'ru' => 'Добавить образование', 'hi' => 'शिक्षा जोड़ें',
                ],
                'apply.actions.add_experience' => [
                    'en' => 'Add experience', 'ar' => 'أضف خبرة', 'fr' => 'Ajouter une expérience',
                    'es' => 'Añadir experiencia', 'de' => 'Erfahrung hinzufügen', 'it' => 'Aggiungi esperienza',
                    'pt' => 'Adicionar experiência', 'ru' => 'Добавить опыт', 'hi' => 'अनुभव जोड़ें',
                ],
                'apply.actions.add_language' => [
                    'en' => 'Add language', 'ar' => 'أضف لغة', 'fr' => 'Ajouter une langue',
                    'es' => 'Añadir idioma', 'de' => 'Sprache hinzufügen', 'it' => 'Aggiungi lingua',
                    'pt' => 'Adicionar idioma', 'ru' => 'Добавить язык', 'hi' => 'भाषा जोड़ें',
                ],
                'apply.actions.remove' => [
                    'en' => 'Remove', 'ar' => 'إزالة', 'fr' => 'Retirer', 'es' => 'Quitar', 'de' => 'Entfernen',
                    'it' => 'Rimuovi', 'pt' => 'Remover', 'ru' => 'Удалить', 'hi' => 'हटाएँ',
                ],
                'apply.actions.submit' => [
                    'en' => 'Submit application', 'ar' => 'إرسال الطلب', 'fr' => 'Envoyer la candidature',
                    'es' => 'Enviar solicitud', 'de' => 'Bewerbung absenden', 'it' => 'Invia la candidatura',
                    'pt' => 'Enviar candidatura', 'ru' => 'Отправить заявку', 'hi' => 'आवेदन भेजें',
                ],

                'apply.documents.cv' => [
                    'en' => 'Curriculum vitae', 'ar' => 'السيرة الذاتية', 'fr' => 'Curriculum vitae',
                    'es' => 'Currículum', 'de' => 'Lebenslauf', 'it' => 'Curriculum vitae',
                    'pt' => 'Currículo', 'ru' => 'Резюме', 'hi' => 'बायोडाटा',
                ],
                'apply.documents.cv_formats' => [
                    'en' => 'PDF, DOC or DOCX, up to 5 MB.', 'ar' => 'PDF أو DOC أو DOCX، بحد أقصى 5 ميغابايت.',
                    'fr' => 'PDF, DOC ou DOCX, 5 Mo maximum.', 'es' => 'PDF, DOC o DOCX, hasta 5 MB.',
                    'de' => 'PDF, DOC oder DOCX, bis 5 MB.', 'it' => 'PDF, DOC o DOCX, fino a 5 MB.',
                    'pt' => 'PDF, DOC ou DOCX, até 5 MB.', 'ru' => 'PDF, DOC или DOCX, до 5 МБ.',
                    'hi' => 'PDF, DOC या DOCX, अधिकतम 5 MB।',
                ],
                'apply.documents.additional' => [
                    'en' => 'Additional documents', 'ar' => 'مستندات إضافية',
                    'fr' => 'Documents complémentaires', 'es' => 'Documentos adicionales',
                    'de' => 'Weitere Dokumente', 'it' => 'Documenti aggiuntivi',
                    'pt' => 'Documentos adicionais', 'ru' => 'Дополнительные документы',
                    'hi' => 'अतिरिक्त दस्तावेज़',
                ],
                'apply.documents.browse' => [
                    'en' => 'Choose a file', 'ar' => 'اختر ملفًا', 'fr' => 'Choisir un fichier',
                    'es' => 'Elegir archivo', 'de' => 'Datei auswählen', 'it' => 'Scegli un file',
                    'pt' => 'Escolher ficheiro', 'ru' => 'Выбрать файл', 'hi' => 'फ़ाइल चुनें',
                ],
                'apply.documents.drag' => [
                    'en' => 'or drag it here', 'ar' => 'أو اسحبه إلى هنا', 'fr' => 'ou glissez-le ici',
                    'es' => 'o arrástrelo aquí', 'de' => 'oder hierher ziehen', 'it' => 'oppure trascinalo qui',
                    'pt' => 'ou arraste-o para aqui', 'ru' => 'или перетащите сюда',
                    'hi' => 'या इसे यहाँ खींचें',
                ],

                'apply.errors.fix' => [
                    'en' => 'Please correct the highlighted fields.', 'ar' => 'يرجى تصحيح الحقول المميزة.',
                    'fr' => 'Veuillez corriger les champs signalés.', 'es' => 'Corrija los campos marcados.',
                    'de' => 'Bitte korrigieren Sie die markierten Felder.',
                    'it' => 'Correggi i campi evidenziati.', 'pt' => 'Corrija os campos assinalados.',
                    'ru' => 'Исправьте отмеченные поля.', 'hi' => 'कृपया चिह्नित फ़ील्ड ठीक करें।',
                ],
                'apply.errors.cv_required' => [
                    'en' => 'A CV is required.', 'ar' => 'السيرة الذاتية مطلوبة.', 'fr' => 'Un CV est requis.',
                    'es' => 'Se requiere un currículum.', 'de' => 'Ein Lebenslauf ist erforderlich.',
                    'it' => 'Il curriculum è obbligatorio.', 'pt' => 'É necessário um currículo.',
                    'ru' => 'Резюме обязательно.', 'hi' => 'बायोडाटा आवश्यक है।',
                ],
                'apply.errors.cv_too_large' => [
                    'en' => 'That file is too large.', 'ar' => 'حجم الملف كبير جدًا.',
                    'fr' => 'Ce fichier est trop volumineux.', 'es' => 'Ese archivo es demasiado grande.',
                    'de' => 'Diese Datei ist zu groß.', 'it' => 'Il file è troppo grande.',
                    'pt' => 'Esse ficheiro é demasiado grande.', 'ru' => 'Файл слишком большой.',
                    'hi' => 'यह फ़ाइल बहुत बड़ी है।',
                ],
                'apply.errors.cv_wrong_type' => [
                    'en' => 'That file type is not accepted.', 'ar' => 'نوع الملف غير مقبول.',
                    'fr' => 'Ce type de fichier n\'est pas accepté.', 'es' => 'Ese tipo de archivo no se admite.',
                    'de' => 'Dieser Dateityp wird nicht akzeptiert.', 'it' => 'Questo tipo di file non è accettato.',
                    'pt' => 'Esse tipo de ficheiro não é aceite.', 'ru' => 'Такой тип файла не принимается.',
                    'hi' => 'यह फ़ाइल प्रकार स्वीकार नहीं है।',
                ],
                'apply.errors.network' => [
                    'en' => 'Something went wrong. Please try again.', 'ar' => 'حدث خطأ ما. يرجى المحاولة مرة أخرى.',
                    'fr' => 'Une erreur est survenue. Veuillez réessayer.',
                    'es' => 'Algo ha salido mal. Inténtelo de nuevo.',
                    'de' => 'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.',
                    'it' => 'Qualcosa è andato storto. Riprova.',
                    'pt' => 'Algo correu mal. Tente novamente.',
                    'ru' => 'Что-то пошло не так. Попробуйте ещё раз.',
                    'hi' => 'कुछ गड़बड़ हो गई। कृपया पुनः प्रयास करें।',
                ],
                'apply.success' => [
                    'en' => 'Your application has been received.', 'ar' => 'تم استلام طلبك.',
                    'fr' => 'Votre candidature a bien été reçue.', 'es' => 'Hemos recibido su solicitud.',
                    'de' => 'Ihre Bewerbung ist eingegangen.', 'it' => 'La tua candidatura è stata ricevuta.',
                    'pt' => 'A sua candidatura foi recebida.', 'ru' => 'Ваша заявка получена.',
                    'hi' => 'आपका आवेदन प्राप्त हो गया है।',
                ],
            ],

            /*
             * Translation catalogue — group `visits` (lang/{code}/visits.php).
             *
             * The school-visit booking wizard. INERT in this build — there is no
             * VisitService model, no route and no controller (see the plan's decision 4);
             * the island ships as a file so that standing the backend up later is a routing
             * change rather than a translation project.
             *
             * Kept as its own group rather than folded into `jobs` or `site` so that
             * deleting the feature, if it never lands, is deleting one file and one line of
             * config('translations.groups').
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             *
             */
            'visits' => [
                'title' => [
                    'en' => 'Book a visit', 'ar' => 'احجز زيارة', 'fr' => 'Réserver une visite',
                    'es' => 'Reservar una visita', 'de' => 'Besuch buchen', 'it' => 'Prenota una visita',
                    'pt' => 'Marcar uma visita', 'ru' => 'Записаться на визит', 'hi' => 'भ्रमण बुक करें',
                ],
                'read_more' => [
                    'en' => 'Read more', 'ar' => 'اقرأ المزيد', 'fr' => 'En savoir plus', 'es' => 'Leer más',
                    'de' => 'Mehr erfahren', 'it' => 'Scopri di più', 'pt' => 'Saber mais',
                    'ru' => 'Подробнее', 'hi' => 'और पढ़ें',
                ],
                'select' => [
                    'en' => 'Select', 'ar' => 'اختيار', 'fr' => 'Choisir', 'es' => 'Seleccionar',
                    'de' => 'Auswählen', 'it' => 'Seleziona', 'pt' => 'Selecionar', 'ru' => 'Выбрать',
                    'hi' => 'चुनें',
                ],

                /* The card, and the panel it opens */

                /*
                 * The duration pill. A COUNT, not a formatted string built in PHP:
                 * "60 min" is not how every one of these languages says it, and the
                 * abbreviation differs even where the number does not.
                 */
                'service.duration' => [
                    'en' => ':minutes min', 'ar' => ':minutes دقيقة', 'fr' => ':minutes min',
                    'es' => ':minutes min', 'de' => ':minutes Min.', 'it' => ':minutes min',
                    'pt' => ':minutes min', 'ru' => ':minutes мин', 'hi' => ':minutes मिनट',
                ],

                /*
                 * The label on the back button in each wizard card's header.
                 *
                 * Paired with an arrow, and with the PREVIOUS step's own name as the
                 * aria-label — so a screen reader hears "Back to Choose a visit"
                 * rather than a bare "Back" three times on one page.
                 */
                'back' => [
                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar', 'ru' => 'Назад',
                    'hi' => 'वापस',
                ],

                /* Steps */

                'steps.service' => [
                    'en' => 'Choose a visit', 'ar' => 'اختر نوع الزيارة', 'fr' => 'Choisir une visite',
                    'es' => 'Elegir una visita', 'de' => 'Besuch auswählen', 'it' => 'Scegli una visita',
                    'pt' => 'Escolher uma visita', 'ru' => 'Выберите визит', 'hi' => 'भ्रमण चुनें',
                ],
                'steps.slot' => [
                    'en' => 'Pick a time', 'ar' => 'اختر الموعد', 'fr' => 'Choisir un horaire',
                    'es' => 'Elegir una hora', 'de' => 'Zeit wählen', 'it' => 'Scegli un orario',
                    'pt' => 'Escolher um horário', 'ru' => 'Выберите время', 'hi' => 'समय चुनें',
                ],
                'steps.details' => [
                    'en' => 'Your details', 'ar' => 'بياناتك', 'fr' => 'Vos coordonnées', 'es' => 'Sus datos',
                    'de' => 'Ihre Angaben', 'it' => 'I tuoi dati', 'pt' => 'Os seus dados',
                    'ru' => 'Ваши данные', 'hi' => 'आपका विवरण',
                ],

                /* Slots */

                'slots.available' => [
                    'en' => 'Available times', 'ar' => 'المواعيد المتاحة', 'fr' => 'Créneaux disponibles',
                    'es' => 'Horarios disponibles', 'de' => 'Verfügbare Zeiten', 'it' => 'Orari disponibili',
                    'pt' => 'Horários disponíveis', 'ru' => 'Свободное время', 'hi' => 'उपलब्ध समय',
                ],
                'slots.remaining' => [
                    'en' => ':count places left', 'ar' => 'المتبقي :count مقعدًا',
                    'fr' => ':count places restantes', 'es' => 'Quedan :count plazas',
                    'de' => 'Noch :count Plätze', 'it' => ':count posti rimasti',
                    'pt' => 'Restam :count lugares', 'ru' => 'Осталось мест: :count',
                    'hi' => ':count स्थान शेष',
                ],
                /*
                 * STILL BOOKABLE, and the wording has to say so — this is the legend
                 * beside an amber slot a visitor may absolutely take. "Almost full"
                 * invites them to hurry; "limited" on its own reads like a refusal.
                 */
                'slots.limited' => [
                    'en' => 'Almost full', 'ar' => 'أوشك على الاكتمال', 'fr' => 'Presque complet',
                    'es' => 'Casi completo', 'de' => 'Fast ausgebucht', 'it' => 'Quasi al completo',
                    'pt' => 'Quase esgotado', 'ru' => 'Почти заполнено', 'hi' => 'लगभग भर चुका',
                ],
                'slots.full' => [
                    'en' => 'Fully booked', 'ar' => 'مكتمل العدد', 'fr' => 'Complet', 'es' => 'Completo',
                    'de' => 'Ausgebucht', 'it' => 'Al completo', 'pt' => 'Esgotado', 'ru' => 'Мест нет',
                    'hi' => 'पूर्ण रूप से बुक',
                ],
                'slots.closed' => [
                    'en' => 'Not available', 'ar' => 'غير متاح', 'fr' => 'Indisponible',
                    'es' => 'No disponible', 'de' => 'Nicht verfügbar', 'it' => 'Non disponibile',
                    'pt' => 'Indisponível', 'ru' => 'Недоступно', 'hi' => 'उपलब्ध नहीं',
                ],
                'slots.none' => [
                    'en' => 'No times available for this visit yet.',
                    'ar' => 'لا توجد مواعيد متاحة لهذه الزيارة بعد.',
                    'fr' => 'Aucun créneau disponible pour cette visite.',
                    'es' => 'Todavía no hay horarios para esta visita.',
                    'de' => 'Für diesen Besuch sind noch keine Zeiten verfügbar.',
                    'it' => 'Nessun orario disponibile per questa visita.',
                    'pt' => 'Ainda não há horários para esta visita.',
                    'ru' => 'Для этого визита пока нет свободного времени.',
                    'hi' => 'इस भ्रमण के लिए अभी कोई समय उपलब्ध नहीं है।',
                ],

                /* Details */

                'fields.name' => [
                    'en' => 'Your name', 'ar' => 'اسمك', 'fr' => 'Votre nom', 'es' => 'Su nombre',
                    'de' => 'Ihr Name', 'it' => 'Il tuo nome', 'pt' => 'O seu nome', 'ru' => 'Ваше имя',
                    'hi' => 'आपका नाम',
                ],
                'fields.email' => [
                    'en' => 'Email address', 'ar' => 'البريد الإلكتروني', 'fr' => 'Adresse e-mail',
                    'es' => 'Correo electrónico', 'de' => 'E-Mail-Adresse', 'it' => 'Indirizzo e-mail',
                    'pt' => 'Endereço de e-mail', 'ru' => 'Электронная почта', 'hi' => 'ईमेल पता',
                ],
                'fields.phone' => [
                    'en' => 'Phone number', 'ar' => 'رقم الهاتف', 'fr' => 'Numéro de téléphone',
                    'es' => 'Número de teléfono', 'de' => 'Telefonnummer', 'it' => 'Numero di telefono',
                    'pt' => 'Número de telefone', 'ru' => 'Номер телефона', 'hi' => 'फ़ोन नंबर',
                ],
                'fields.student_name' => [
                    'en' => 'Student name', 'ar' => 'اسم الطالب', 'fr' => 'Nom de l\'élève',
                    'es' => 'Nombre del alumno', 'de' => 'Name des Schülers', 'it' => 'Nome dello studente',
                    'pt' => 'Nome do aluno', 'ru' => 'Имя учащегося', 'hi' => 'छात्र का नाम',
                ],
                'fields.student_grade' => [
                    'en' => 'Grade', 'ar' => 'الصف', 'fr' => 'Niveau', 'es' => 'Curso', 'de' => 'Klassenstufe',
                    'it' => 'Classe', 'pt' => 'Ano', 'ru' => 'Класс', 'hi' => 'कक्षा',
                ],
                'fields.student_school' => [
                    'en' => 'Current school', 'ar' => 'المدرسة الحالية', 'fr' => 'École actuelle',
                    'es' => 'Centro actual', 'de' => 'Derzeitige Schule', 'it' => 'Scuola attuale',
                    'pt' => 'Escola atual', 'ru' => 'Текущая школа', 'hi' => 'वर्तमान विद्यालय',
                ],
                'fields.visitors' => [
                    'en' => 'Number of visitors', 'ar' => 'عدد الزوار', 'fr' => 'Nombre de visiteurs',
                    'es' => 'Número de visitantes', 'de' => 'Anzahl der Besucher',
                    'it' => 'Numero di visitatori', 'pt' => 'Número de visitantes',
                    'ru' => 'Количество посетителей', 'hi' => 'आगंतुकों की संख्या',
                ],
                'fields.message' => [
                    'en' => 'Anything we should know?', 'ar' => 'هل من شيء ينبغي أن نعرفه؟',
                    'fr' => 'Quelque chose à nous signaler ?', 'es' => '¿Algo que debamos saber?',
                    'de' => 'Gibt es etwas, das wir wissen sollten?', 'it' => 'C\'è qualcosa che dovremmo sapere?',
                    'pt' => 'Há algo que devamos saber?', 'ru' => 'Что нам стоит знать?',
                    'hi' => 'क्या हमें कुछ और जानना चाहिए?',
                ],

                /* Confirmation */

                'confirm.title' => [
                    'en' => 'Confirm your visit', 'ar' => 'أكِّد زيارتك', 'fr' => 'Confirmer votre visite',
                    'es' => 'Confirme su visita', 'de' => 'Besuch bestätigen', 'it' => 'Conferma la visita',
                    'pt' => 'Confirme a sua visita', 'ru' => 'Подтвердите визит',
                    'hi' => 'अपना भ्रमण पुष्ट करें',
                ],
                'confirm.button' => [
                    'en' => 'Confirm booking', 'ar' => 'تأكيد الحجز', 'fr' => 'Confirmer la réservation',
                    'es' => 'Confirmar reserva', 'de' => 'Buchung bestätigen', 'it' => 'Conferma prenotazione',
                    'pt' => 'Confirmar marcação', 'ru' => 'Подтвердить запись', 'hi' => 'बुकिंग पुष्ट करें',
                ],
                'confirm.selected' => [
                    'en' => 'Selected visit', 'ar' => 'الزيارة المختارة', 'fr' => 'Visite sélectionnée',
                    'es' => 'Visita seleccionada', 'de' => 'Ausgewählter Besuch', 'it' => 'Visita selezionata',
                    'pt' => 'Visita selecionada', 'ru' => 'Выбранный визит', 'hi' => 'चयनित भ्रमण',
                ],
                'confirm.date_time' => [
                    'en' => 'Date and time', 'ar' => 'التاريخ والوقت', 'fr' => 'Date et heure',
                    'es' => 'Fecha y hora', 'de' => 'Datum und Uhrzeit', 'it' => 'Data e ora',
                    'pt' => 'Data e hora', 'ru' => 'Дата и время', 'hi' => 'दिनांक और समय',
                ],

                /* Outcomes */

                'success' => [
                    'en' => 'Your visit is booked. We have emailed you the details.',
                    'ar' => 'تم حجز زيارتك. أرسلنا إليك التفاصيل عبر البريد الإلكتروني.',
                    'fr' => 'Votre visite est réservée. Nous vous avons envoyé les détails par e-mail.',
                    'es' => 'Su visita está reservada. Le hemos enviado los detalles por correo.',
                    'de' => 'Ihr Besuch ist gebucht. Die Details haben wir Ihnen per E-Mail geschickt.',
                    'it' => 'La tua visita è prenotata. Ti abbiamo inviato i dettagli via e-mail.',
                    'pt' => 'A sua visita está marcada. Enviámos-lhe os detalhes por e-mail.',
                    'ru' => 'Визит записан. Детали отправлены вам на почту.',
                    'hi' => 'आपका भ्रमण बुक हो गया है। विवरण हमने आपको ईमेल कर दिया है।',
                ],
                'error' => [
                    'en' => 'We could not complete the booking. Please try again.',
                    'ar' => 'تعذَّر إتمام الحجز. يرجى المحاولة مرة أخرى.',
                    'fr' => 'Nous n\'avons pas pu finaliser la réservation. Veuillez réessayer.',
                    'es' => 'No hemos podido completar la reserva. Inténtelo de nuevo.',
                    'de' => 'Die Buchung konnte nicht abgeschlossen werden. Bitte versuchen Sie es erneut.',
                    'it' => 'Non è stato possibile completare la prenotazione. Riprova.',
                    'pt' => 'Não foi possível concluir a marcação. Tente novamente.',
                    'ru' => 'Не удалось завершить запись. Попробуйте ещё раз.',
                    'hi' => 'हम बुकिंग पूरी नहीं कर सके। कृपया पुनः प्रयास करें।',
                ],
                'capacity_error' => [
                    'en' => 'That time no longer has room for this many visitors.',
                    'ar' => 'لم يعد هذا الموعد يتسع لهذا العدد من الزوار.',
                    'fr' => 'Ce créneau ne peut plus accueillir autant de visiteurs.',
                    'es' => 'Ese horario ya no tiene sitio para tantos visitantes.',
                    'de' => 'Für so viele Besucher ist zu dieser Zeit kein Platz mehr.',
                    'it' => 'Questo orario non ha più posto per così tanti visitatori.',
                    'pt' => 'Esse horário já não tem lugar para tantos visitantes.',
                    'ru' => 'На это время уже не хватает мест для стольких посетителей.',
                    'hi' => 'उस समय इतने आगंतुकों के लिए अब स्थान नहीं है।',
                ],
            ],

            /*
             * Translation catalogue — group `facilities` (lang/{code}/facilities.php).
             *
             * The venues the school rents out, and the two things a visitor can do
             * from one: send a message, or book a time.
             *
             * `:venue` is interpolated with the venue's OWN translated title, so a
             * heading reads "Book The Hive" in English and the equivalent in Arabic
             * without a per-venue string existing anywhere. That is also why the
             * page titles are here rather than seeded onto each facility row: the
             * wording is the same for every venue and only the name changes.
             *
             * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored.
             */
            'facilities' => [

                /* The listing and the venue card */

                'empty' => [
                    'en' => 'There are no facilities to show yet.',
                    'ar' => 'لا توجد مرافق لعرضها بعد.',
                    'fr' => 'Aucun espace à afficher pour le moment.',
                    'es' => 'Todavía no hay instalaciones que mostrar.',
                    'de' => 'Es gibt noch keine Räumlichkeiten zu zeigen.',
                    'it' => 'Non ci sono ancora spazi da mostrare.',
                    'pt' => 'Ainda não há espaços para mostrar.',
                    'ru' => 'Площадок пока нет.',
                    'hi' => 'दिखाने के लिए अभी कोई सुविधा नहीं है।',
                ],
                'view' => [
                    'en' => 'View details', 'ar' => 'عرض التفاصيل', 'fr' => 'Voir les détails',
                    'es' => 'Ver detalles', 'de' => 'Details ansehen', 'it' => 'Vedi i dettagli',
                    'pt' => 'Ver detalhes', 'ru' => 'Подробнее', 'hi' => 'विवरण देखें',
                ],

                /* The two calls to action on a venue's page.
                   NAMESPACED under cta.* because `contact` is also the parent of
                   contact.title/contact.crumb below, and a key cannot be both a
                   string and a branch — TranslationService refuses the write. */

                'cta.book' => [
                    'en' => 'Book this venue', 'ar' => 'احجز هذا المرفق',
                    'fr' => 'Réserver cet espace', 'es' => 'Reservar este espacio',
                    'de' => 'Diesen Bereich buchen', 'it' => 'Prenota questo spazio',
                    'pt' => 'Reservar este espaço', 'ru' => 'Забронировать площадку',
                    'hi' => 'यह स्थल बुक करें',
                ],
                'cta.contact' => [
                    'en' => 'Contact us about it', 'ar' => 'تواصل معنا بشأنه',
                    'fr' => 'Nous contacter à ce sujet', 'es' => 'Contáctenos al respecto',
                    'de' => 'Fragen Sie uns dazu', 'it' => 'Contattaci a riguardo',
                    'pt' => 'Fale connosco sobre isto', 'ru' => 'Написать нам о ней',
                    'hi' => 'इसके बारे में संपर्क करें',
                ],

                /* Sections on a venue's page. The HEADINGS render unconditionally
                   only where there is something under them — unlike the home page,
                   an empty "News" heading on a venue says the venue has no news
                   rather than that the section is coming. */

                'sections.news' => [
                    'en' => 'News', 'ar' => 'الأخبار', 'fr' => 'Actualités', 'es' => 'Noticias',
                    'de' => 'Neuigkeiten', 'it' => 'Notizie', 'pt' => 'Notícias', 'ru' => 'Новости',
                    'hi' => 'समाचार',
                ],
                'sections.albums' => [
                    'en' => 'Photos', 'ar' => 'الصور', 'fr' => 'Photos', 'es' => 'Fotos',
                    'de' => 'Fotos', 'it' => 'Foto', 'pt' => 'Fotos', 'ru' => 'Фотографии',
                    'hi' => 'तस्वीरें',
                ],

                /* The contact page */

                'contact.title' => [
                    'en' => 'Contact us about :venue', 'ar' => 'تواصل معنا بشأن :venue',
                    'fr' => 'Nous contacter au sujet de :venue', 'es' => 'Contáctenos sobre :venue',
                    'de' => 'Kontakt zu :venue', 'it' => 'Contattaci per :venue',
                    'pt' => 'Fale connosco sobre :venue', 'ru' => 'Написать нам о :venue',
                    'hi' => ':venue के बारे में संपर्क करें',
                ],
                'contact.crumb' => [
                    'en' => 'Contact', 'ar' => 'تواصل', 'fr' => 'Contact', 'es' => 'Contacto',
                    'de' => 'Kontakt', 'it' => 'Contatti', 'pt' => 'Contacto', 'ru' => 'Контакты',
                    'hi' => 'संपर्क',
                ],

                /* The booking page */

                'reserve.title' => [
                    'en' => 'Book :venue', 'ar' => 'احجز :venue', 'fr' => 'Réserver :venue',
                    'es' => 'Reservar :venue', 'de' => ':venue buchen', 'it' => 'Prenota :venue',
                    'pt' => 'Reservar :venue', 'ru' => 'Забронировать :venue',
                    'hi' => ':venue बुक करें',
                ],
                'reserve.crumb' => [
                    'en' => 'Book', 'ar' => 'الحجز', 'fr' => 'Réserver', 'es' => 'Reservar',
                    'de' => 'Buchen', 'it' => 'Prenota', 'pt' => 'Reservar', 'ru' => 'Бронирование',
                    'hi' => 'बुकिंग',
                ],

                /* The two steps of the booking wizard */

                'steps.slot' => [
                    'en' => 'Pick a time', 'ar' => 'اختر الموعد', 'fr' => 'Choisir un horaire',
                    'es' => 'Elegir una hora', 'de' => 'Zeit wählen', 'it' => 'Scegli un orario',
                    'pt' => 'Escolher um horário', 'ru' => 'Выберите время', 'hi' => 'समय चुनें',
                ],
                'steps.details' => [
                    'en' => 'Your details', 'ar' => 'بياناتك', 'fr' => 'Vos coordonnées',
                    'es' => 'Sus datos', 'de' => 'Ihre Angaben', 'it' => 'I tuoi dati',
                    'pt' => 'Os seus dados', 'ru' => 'Ваши данные', 'hi' => 'आपका विवरण',
                ],
                'back' => [
                    'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                    'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar', 'ru' => 'Назад',
                    'hi' => 'वापस',
                ],

                /* The calendar's legend and its events. The same four states
                   VisitSlot::state() decides, worded the same way — a slot is a
                   slot whichever module owns it. */

                'slots.available' => [
                    'en' => 'Available times', 'ar' => 'المواعيد المتاحة', 'fr' => 'Créneaux disponibles',
                    'es' => 'Horarios disponibles', 'de' => 'Verfügbare Zeiten', 'it' => 'Orari disponibili',
                    'pt' => 'Horários disponíveis', 'ru' => 'Свободное время', 'hi' => 'उपलब्ध समय',
                ],
                'slots.limited' => [
                    'en' => 'Almost full', 'ar' => 'أوشك على الاكتمال', 'fr' => 'Presque complet',
                    'es' => 'Casi completo', 'de' => 'Fast ausgebucht', 'it' => 'Quasi al completo',
                    'pt' => 'Quase esgotado', 'ru' => 'Почти заполнено', 'hi' => 'लगभग भर चुका',
                ],
                'slots.full' => [
                    'en' => 'Fully booked', 'ar' => 'مكتمل الحجز', 'fr' => 'Complet', 'es' => 'Completo',
                    'de' => 'Ausgebucht', 'it' => 'Al completo', 'pt' => 'Esgotado', 'ru' => 'Мест нет',
                    'hi' => 'पूर्ण रूप से बुक',
                ],
                'slots.closed' => [
                    'en' => 'Not available', 'ar' => 'غير متاح', 'fr' => 'Indisponible',
                    'es' => 'No disponible', 'de' => 'Nicht verfügbar', 'it' => 'Non disponibile',
                    'pt' => 'Indisponível', 'ru' => 'Недоступно', 'hi' => 'उपलब्ध नहीं',
                ],
                'slots.remaining' => [
                    'en' => ':count places left', 'ar' => 'المتبقي :count مقعدًا',
                    'fr' => ':count places restantes', 'es' => 'Quedan :count plazas',
                    'de' => 'Noch :count Plätze', 'it' => ':count posti rimasti',
                    'pt' => 'Restam :count lugares', 'ru' => 'Осталось мест: :count',
                    'hi' => ':count स्थान शेष',
                ],
                'slots.none' => [
                    'en' => 'No times are available for this venue yet.',
                    'ar' => 'لا توجد مواعيد متاحة لهذا المرفق بعد.',
                    'fr' => 'Aucun créneau n’est encore disponible pour cet espace.',
                    'es' => 'Todavía no hay horarios para este espacio.',
                    'de' => 'Für diesen Bereich sind noch keine Zeiten verfügbar.',
                    'it' => 'Non ci sono ancora orari disponibili per questo spazio.',
                    'pt' => 'Ainda não há horários para este espaço.',
                    'ru' => 'Для этой площадки пока нет свободного времени.',
                    'hi' => 'इस स्थल के लिए अभी कोई समय उपलब्ध नहीं है।',
                ],
                'error' => [
                    'en' => 'We could not load the available times. Please try again.',
                    'ar' => 'تعذّر تحميل المواعيد المتاحة. يرجى المحاولة مرة أخرى.',
                    'fr' => 'Impossible de charger les créneaux disponibles. Veuillez réessayer.',
                    'es' => 'No hemos podido cargar los horarios disponibles. Inténtelo de nuevo.',
                    'de' => 'Die verfügbaren Zeiten konnten nicht geladen werden. Bitte erneut versuchen.',
                    'it' => 'Non è stato possibile caricare gli orari disponibili. Riprova.',
                    'pt' => 'Não foi possível carregar os horários disponíveis. Tente novamente.',
                    'ru' => 'Не удалось загрузить свободное время. Попробуйте ещё раз.',
                    'hi' => 'उपलब्ध समय लोड नहीं हो सका। कृपया पुनः प्रयास करें।',
                ],
                'confirm.selected' => [
                    'en' => 'Selected time', 'ar' => 'الموعد المختار', 'fr' => 'Créneau choisi',
                    'es' => 'Horario elegido', 'de' => 'Gewählte Zeit', 'it' => 'Orario scelto',
                    'pt' => 'Horário escolhido', 'ru' => 'Выбранное время', 'hi' => 'चयनित समय',
                ],
            ],
        ];
    }
}
