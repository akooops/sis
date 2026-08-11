<?php

return [

    /*
     * The translation catalogue's GROUPS. Each is a lang file basename, so
     * `common` is lang/{locale}/common.php and __('nav.services') reads the
     * `nav` group below.
     *
     * Listed explicitly rather than derived from the keys array, so a group can
     * be retired without its lines disappearing from the file, and so the seed
     * order is deterministic.
     */
    'groups' => ['common', 'nav', 'site', 'forms', 'jobs', 'visits'],

    /*
     * THE WHOLE CATALOGUE, IN ONE FILE.
     *
     * It was six files under config/translations/ and is deliberately one now:
     * a nested config DIRECTORY buys nothing here except six files to open, and
     * every one of them was the same shape. Big, but it is a data table — read
     * it with a search, not by scrolling.
     *
     * Keys are FLAT-DOTTED and written NESTED to disk, which is what __() and
     * @lang() read: 'confirm.title' becomes ['confirm' => ['title' => …]].
     *
     * Each value is a map of LOCALE => STRING covering the nine codes
     * LanguagesSeeder ships. An absent or blank locale seeds as a missing line
     * and shows up under the Translations page's `missing` filter.
     *
     * THIS IS THE TRACKED SOURCE. lang/ is generated output and gitignored, so
     * a fresh clone has no translations at all until it seeds. Adding a line is:
     * add it here, reseed.
     */
    'keys' => [

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
                'en' => 'Explore programmes', 'ar' => 'استكشف البرامج', 'fr' => 'Découvrir les programmes',
                'es' => 'Explorar programas', 'de' => 'Programme entdecken', 'it' => 'Scopri i programmi',
                'pt' => 'Explorar programas', 'ru' => 'Смотреть программы', 'hi' => 'कार्यक्रम देखें',
            ],
            'home.pathway.title' => [
                'en' => 'Choose your pathway', 'ar' => 'اختر مسارك', 'fr' => 'Choisissez votre parcours',
                'es' => 'Elija su itinerario', 'de' => 'Wählen Sie Ihren Bildungsweg',
                'it' => 'Scegli il tuo percorso', 'pt' => 'Escolha o seu percurso',
                'ru' => 'Выберите свой путь', 'hi' => 'अपना मार्ग चुनें',
            ],
            'home.pathway.subtitle' => [
                'en' => 'Two curricula, one community.', 'ar' => 'منهجان، ومجتمع واحد.',
                'fr' => 'Deux programmes, une communauté.', 'es' => 'Dos currículos, una comunidad.',
                'de' => 'Zwei Lehrpläne, eine Gemeinschaft.', 'it' => 'Due programmi, una comunità.',
                'pt' => 'Dois currículos, uma comunidade.', 'ru' => 'Две программы, одно сообщество.',
                'hi' => 'दो पाठ्यक्रम, एक समुदाय।',
            ],
            'home.pathway.stream_label' => [
                'en' => 'Pathway', 'ar' => 'المسار', 'fr' => 'Parcours', 'es' => 'Itinerario',
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
            'articles.search' => [
                'en' => 'Search news…', 'ar' => 'ابحث في الأخبار…', 'fr' => 'Rechercher une actualité…',
                'es' => 'Buscar noticias…', 'de' => 'Neuigkeiten durchsuchen…', 'it' => 'Cerca notizie…',
                'pt' => 'Pesquisar notícias…', 'ru' => 'Поиск новостей…', 'hi' => 'समाचार खोजें…',
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
            'achievements.filters.all_categories' => [
                'en' => 'All categories', 'ar' => 'كل التصنيفات', 'fr' => 'Toutes les catégories',
                'es' => 'Todas las categorías', 'de' => 'Alle Kategorien', 'it' => 'Tutte le categorie',
                'pt' => 'Todas as categorias', 'ru' => 'Все категории', 'hi' => 'सभी श्रेणियाँ',
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

            /* -----------------------------------------
             Contact — the seeded system form
            ------------------------------------------*/

            'contact.name' => [
                'en' => 'Full name', 'ar' => 'الاسم الكامل', 'fr' => 'Nom complet', 'es' => 'Nombre completo',
                'de' => 'Vollständiger Name', 'it' => 'Nome completo', 'pt' => 'Nome completo',
                'ru' => 'Полное имя', 'hi' => 'पूरा नाम',
            ],
            'contact.email' => [
                'en' => 'Email address', 'ar' => 'البريد الإلكتروني', 'fr' => 'Adresse e-mail',
                'es' => 'Correo electrónico', 'de' => 'E-Mail-Adresse', 'it' => 'Indirizzo e-mail',
                'pt' => 'Endereço de e-mail', 'ru' => 'Электронная почта', 'hi' => 'ईमेल पता',
            ],
            'contact.phone' => [
                'en' => 'Phone number', 'ar' => 'رقم الهاتف', 'fr' => 'Numéro de téléphone',
                'es' => 'Número de teléfono', 'de' => 'Telefonnummer', 'it' => 'Numero di telefono',
                'pt' => 'Número de telefone', 'ru' => 'Номер телефона', 'hi' => 'फ़ोन नंबर',
            ],
            'contact.subject' => [
                'en' => 'Subject', 'ar' => 'الموضوع', 'fr' => 'Objet', 'es' => 'Asunto', 'de' => 'Betreff',
                'it' => 'Oggetto', 'pt' => 'Assunto', 'ru' => 'Тема', 'hi' => 'विषय',
            ],
            'contact.message' => [
                'en' => 'Message', 'ar' => 'الرسالة', 'fr' => 'Message', 'es' => 'Mensaje',
                'de' => 'Nachricht', 'it' => 'Messaggio', 'pt' => 'Mensagem', 'ru' => 'Сообщение',
                'hi' => 'संदेश',
            ],
            'contact.title' => [
                'en' => 'Contact us', 'ar' => 'اتصل بنا', 'fr' => 'Nous contacter', 'es' => 'Contacto',
                'de' => 'Kontakt', 'it' => 'Contattaci', 'pt' => 'Contacte-nos', 'ru' => 'Свяжитесь с нами',
                'hi' => 'हमसे संपर्क करें',
            ],
            'contact.submit' => [
                'en' => 'Send message', 'ar' => 'إرسال الرسالة', 'fr' => 'Envoyer le message',
                'es' => 'Enviar mensaje', 'de' => 'Nachricht senden', 'it' => 'Invia il messaggio',
                'pt' => 'Enviar mensagem', 'ru' => 'Отправить сообщение', 'hi' => 'संदेश भेजें',
            ],
            'contact.success' => [
                'en' => 'Thank you for contacting us. We will reply shortly.',
                'ar' => 'شكرًا لتواصلك معنا. سنرد عليك قريبًا.',
                'fr' => 'Merci de nous avoir contactés. Nous vous répondrons sous peu.',
                'es' => 'Gracias por ponerse en contacto. Le responderemos en breve.',
                'de' => 'Danke für Ihre Nachricht. Wir melden uns in Kürze.',
                'it' => 'Grazie per averci contattato. Ti risponderemo a breve.',
                'pt' => 'Obrigado por nos contactar. Responderemos em breve.',
                'ru' => 'Спасибо за обращение. Мы скоро ответим.',
                'hi' => 'हमसे संपर्क करने के लिए धन्यवाद। हम शीघ्र ही उत्तर देंगे।',
            ],

            /* -----------------------------------------
             Admissions inquiry — the seeded system form
            ------------------------------------------*/

            'inquiry.guardian_name' => [
                'en' => 'Guardian name', 'ar' => 'اسم ولي الأمر', 'fr' => 'Nom du responsable légal',
                'es' => 'Nombre del tutor', 'de' => 'Name des Erziehungsberechtigten',
                'it' => 'Nome del tutore', 'pt' => 'Nome do encarregado de educação',
                'ru' => 'Имя родителя или опекуна', 'hi' => 'अभिभावक का नाम',
            ],
            'inquiry.student_name' => [
                'en' => 'Student name', 'ar' => 'اسم الطالب', 'fr' => 'Nom de l\'élève',
                'es' => 'Nombre del alumno', 'de' => 'Name des Schülers', 'it' => 'Nome dello studente',
                'pt' => 'Nome do aluno', 'ru' => 'Имя учащегося', 'hi' => 'छात्र का नाम',
            ],
            'inquiry.student_birthdate' => [
                'en' => 'Student date of birth', 'ar' => 'تاريخ ميلاد الطالب',
                'fr' => 'Date de naissance de l\'élève', 'es' => 'Fecha de nacimiento del alumno',
                'de' => 'Geburtsdatum des Schülers', 'it' => 'Data di nascita dello studente',
                'pt' => 'Data de nascimento do aluno', 'ru' => 'Дата рождения учащегося',
                'hi' => 'छात्र की जन्म तिथि',
            ],
            'inquiry.student_school' => [
                'en' => 'Current school', 'ar' => 'المدرسة الحالية', 'fr' => 'École actuelle',
                'es' => 'Centro actual', 'de' => 'Derzeitige Schule', 'it' => 'Scuola attuale',
                'pt' => 'Escola atual', 'ru' => 'Текущая школа', 'hi' => 'वर्तमान विद्यालय',
            ],
            'inquiry.academic_year' => [
                'en' => 'Academic year', 'ar' => 'العام الدراسي', 'fr' => 'Année scolaire',
                'es' => 'Curso académico', 'de' => 'Schuljahr', 'it' => 'Anno scolastico',
                'pt' => 'Ano letivo', 'ru' => 'Учебный год', 'hi' => 'शैक्षणिक वर्ष',
            ],
            'inquiry.grade' => [
                'en' => 'Grade applied for', 'ar' => 'الصف المتقدَّم إليه', 'fr' => 'Niveau demandé',
                'es' => 'Curso solicitado', 'de' => 'Gewünschte Klassenstufe', 'it' => 'Classe richiesta',
                'pt' => 'Ano pretendido', 'ru' => 'Желаемый класс', 'hi' => 'आवेदित कक्षा',
            ],
            'inquiry.questions' => [
                'en' => 'Questions', 'ar' => 'أسئلتك', 'fr' => 'Questions', 'es' => 'Preguntas',
                'de' => 'Fragen', 'it' => 'Domande', 'pt' => 'Questões', 'ru' => 'Вопросы',
                'hi' => 'प्रश्न',
            ],
            'inquiry.title' => [
                'en' => 'Admissions inquiry', 'ar' => 'طلب القبول والتسجيل', 'fr' => 'Demande d\'admission',
                'es' => 'Solicitud de admisión', 'de' => 'Aufnahmeanfrage', 'it' => 'Richiesta di ammissione',
                'pt' => 'Pedido de admissão', 'ru' => 'Заявка на приём', 'hi' => 'प्रवेश पूछताछ',
            ],
            'inquiry.submit' => [
                'en' => 'Send inquiry', 'ar' => 'إرسال الطلب', 'fr' => 'Envoyer la demande',
                'es' => 'Enviar solicitud', 'de' => 'Anfrage senden', 'it' => 'Invia la richiesta',
                'pt' => 'Enviar pedido', 'ru' => 'Отправить заявку', 'hi' => 'पूछताछ भेजें',
            ],
            'inquiry.success' => [
                'en' => 'Thank you. Our admissions team will be in touch.',
                'ar' => 'شكرًا لك. سيتواصل معك فريق القبول والتسجيل.',
                'fr' => 'Merci. Notre équipe des admissions vous contactera.',
                'es' => 'Gracias. Nuestro equipo de admisiones se pondrá en contacto.',
                'de' => 'Vielen Dank. Unser Aufnahmeteam wird sich melden.',
                'it' => 'Grazie. Il nostro ufficio ammissioni ti contatterà.',
                'pt' => 'Obrigado. A nossa equipa de admissões entrará em contacto.',
                'ru' => 'Спасибо. Приёмная комиссия свяжется с вами.',
                'hi' => 'धन्यवाद। हमारी प्रवेश टीम आपसे संपर्क करेगी।',
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
            'slots.full' => [
                'en' => 'Fully booked', 'ar' => 'مكتمل العدد', 'fr' => 'Complet', 'es' => 'Completo',
                'de' => 'Ausgebucht', 'it' => 'Al completo', 'pt' => 'Esgotado', 'ru' => 'Мест нет',
                'hi' => 'पूर्ण रूप से बुक',
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
    ],

    /*
     * A locale code and a group both become path segments, so anything not
     * matching these never reaches the disk — see TranslationService::guard().
     */
    'code_pattern' => '/^[a-z]{2}(_[A-Z]{2})?$/',
    'group_pattern' => '/^[a-z0-9_-]+$/',

    /*
     * FALSE, and the reason matters.
     *
     * With true, a registered key absent from a file is written back as '' so
     * the file always mirrors the registry. But Laravel falls back to
     * fallback_locale only when a key is ABSENT, never when it is '' — so on a
     * public site a half-translated locale would render blank headings and blank
     * buttons to real visitors. English text always beats nothing.
     *
     * The missing-key report is unaffected: TranslationService::isTranslated()
     * already treats null and '' alike, and missingKeyIds() is built on it.
     *
     * The cost: an admin who deliberately CLEARS a translation no longer has
     * that stick across a reseed — but clearing now produces the English
     * fallback, which is what clearing was trying to express anyway.
     */
    'fill_missing_keys' => false,
];
