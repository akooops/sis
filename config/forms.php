<?php

return [

    /*
     * The element catalogue. This array is the source of truth — there is no
     * table mirroring it, because a mirror drifts the moment someone deploys
     * without reseeding and the builder would then offer settings the
     * submit-time compiler cannot read.
     *
     * A new element is one class implementing App\Contracts\Forms\FieldType plus
     * one line here. App\Services\Forms\FieldTypeRegistry builds a code => class
     * map from it, and the builder's inspector renders each element's declared
     * schema through the same SchemaField component the integrations use.
     */
    'field_types' => [
        App\Services\Forms\FieldTypes\HeadingType::class,
        App\Services\Forms\FieldTypes\ParagraphType::class,
        App\Services\Forms\FieldTypes\HtmlType::class,

        App\Services\Forms\FieldTypes\TextType::class,
        App\Services\Forms\FieldTypes\TextareaType::class,
        App\Services\Forms\FieldTypes\NumberType::class,
        App\Services\Forms\FieldTypes\EmailType::class,
        App\Services\Forms\FieldTypes\PhoneType::class,
        App\Services\Forms\FieldTypes\DateType::class,
        App\Services\Forms\FieldTypes\FileType::class,
        App\Services\Forms\FieldTypes\HiddenType::class,

        App\Services\Forms\FieldTypes\SelectType::class,
        App\Services\Forms\FieldTypes\RadioType::class,
        App\Services\Forms\FieldTypes\CheckboxType::class,
        App\Services\Forms\FieldTypes\ConsentType::class,

        App\Services\Forms\FieldTypes\ButtonType::class,
    ],

    /*
     * Forms that ship with the app. FormsSeeder creates these as is_system, so
     * their settings stay editable but their structure does not.
     *
     * These two exist because the public site RESOLVES THEM BY SLUG:
     * /{locale}/contact and /{locale}/inquiries each look up one of these and
     * render it with the ordinary form renderer, so renaming a slug would
     * 404 a page. is_system is what makes that safe — UpdateFormData freezes the
     * slug with Rule::in([$form->slug]), Form::isLocked() blocks structural
     * edits in the builder, and FormsController::destroy refuses the delete.
     * Copy, wording, notification routing and webhooks all stay editable.
     *
     * WORDING IS INLINE HERE, NOT A CATALOGUE KEY. Every title, confirmation
     * and label below is a locale => string map, so this file alone says what
     * the two seeded forms read like in all nine languages, and FormsSeeder
     * needs nothing but what it can see.
     *
     * It used to hold `title_key` / `confirmation_key` / `label_key` pointing
     * into the `forms` group of config/translations.php, which meant reading a
     * seeded form took two files and a lookup, and the catalogue carried
     * eighteen keys nothing ever resolved at runtime — they existed purely to
     * be copied into the database once. The lang files keep the strings the
     * app itself resolves (forms.submit, forms.closed, …); a seed value is not
     * one of those.
     *
     * THE SEED IS A STARTING POINT, NOT A BINDING. These land in translatable
     * columns on the row, and an admin edits them afterwards in the builder —
     * changing a string here never rewrites a form that already exists.
     *
     * Settings and validation keys below are the ones the FieldType classes
     * actually declare — `email` and `phone` declare NONE, so nothing is passed
     * to them. Check app/Services/Forms/FieldTypes/*.php before adding a key;
     * an unknown one is silently ignored rather than rejected.
     */
    'system' => [
        [
            'slug' => 'contact',
            'name' => 'Contact',
            'title' => [
                'en' => 'Contact us',
                'ar' => 'اتصل بنا',
                'fr' => 'Nous contacter',
                'es' => 'Contacto',
                'de' => 'Kontakt',
                'it' => 'Contattaci',
                'pt' => 'Contacte-nos',
                'ru' => 'Свяжитесь с нами',
                'hi' => 'हमसे संपर्क करें',
            ],
            'confirmation_message' => [
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
            'pages' => [
                [
                    'name' => 'Contact',
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'name',
                            'is_required' => true,
                            'validation' => ['max_length' => 120],
                            'label' => [
                                'en' => 'Full name',
                                'ar' => 'الاسم الكامل',
                                'fr' => 'Nom complet',
                                'es' => 'Nombre completo',
                                'de' => 'Vollständiger Name',
                                'it' => 'Nome completo',
                                'pt' => 'Nome completo',
                                'ru' => 'Полное имя',
                                'hi' => 'पूरा नाम',
                            ],
                        ],
                        [
                            'type' => 'email',
                            'key' => 'email',
                            'is_required' => true,
                            'label' => [
                                'en' => 'Email address',
                                'ar' => 'البريد الإلكتروني',
                                'fr' => 'Adresse e-mail',
                                'es' => 'Correo electrónico',
                                'de' => 'E-Mail-Adresse',
                                'it' => 'Indirizzo e-mail',
                                'pt' => 'Endereço de e-mail',
                                'ru' => 'Электронная почта',
                                'hi' => 'ईमेल पता',
                            ],
                        ],
                        [
                            'type' => 'phone',
                            'key' => 'phone',
                            'label' => [
                                'en' => 'Phone number',
                                'ar' => 'رقم الهاتف',
                                'fr' => 'Numéro de téléphone',
                                'es' => 'Número de teléfono',
                                'de' => 'Telefonnummer',
                                'it' => 'Numero di telefono',
                                'pt' => 'Número de telefone',
                                'ru' => 'Номер телефона',
                                'hi' => 'फ़ोन नंबर',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'key' => 'subject',
                            'is_required' => true,
                            'validation' => ['max_length' => 160],
                            'label' => [
                                'en' => 'Subject',
                                'ar' => 'الموضوع',
                                'fr' => 'Objet',
                                'es' => 'Asunto',
                                'de' => 'Betreff',
                                'it' => 'Oggetto',
                                'pt' => 'Assunto',
                                'ru' => 'Тема',
                                'hi' => 'विषय',
                            ],
                        ],
                        [
                            'type' => 'textarea',
                            'key' => 'message',
                            'is_required' => true,
                            'settings' => ['rows' => 8],
                            'validation' => ['max_length' => 4000],
                            'label' => [
                                'en' => 'Message',
                                'ar' => 'الرسالة',
                                'fr' => 'Message',
                                'es' => 'Mensaje',
                                'de' => 'Nachricht',
                                'it' => 'Messaggio',
                                'pt' => 'Mensagem',
                                'ru' => 'Сообщение',
                                'hi' => 'संदेश',
                            ],
                        ],
                        [
                            'type' => 'button',
                            'key' => 'submit',
                            'settings' => ['action' => 'submit', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Send message',
                                'ar' => 'إرسال الرسالة',
                                'fr' => 'Envoyer le message',
                                'es' => 'Enviar mensaje',
                                'de' => 'Nachricht senden',
                                'it' => 'Invia il messaggio',
                                'pt' => 'Enviar mensagem',
                                'ru' => 'Отправить сообщение',
                                'hi' => 'संदेश भेजें',
                            ],
                        ],
                    ],
                ],
            ],
        ],

        [
            'slug' => 'inquiries',
            'name' => 'Admissions inquiry',
            'title' => [
                'en' => 'Admissions inquiry',
                'ar' => 'طلب القبول والتسجيل',
                'fr' => 'Demande d\'admission',
                'es' => 'Solicitud de admisión',
                'de' => 'Aufnahmeanfrage',
                'it' => 'Richiesta di ammissione',
                'pt' => 'Pedido de admissão',
                'ru' => 'Заявка на приём',
                'hi' => 'प्रवेश पूछताछ',
            ],
            'confirmation_message' => [
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
            'pages' => [
                [
                    'name' => 'Inquiry',
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'guardian_name',
                            'is_required' => true,
                            'validation' => ['max_length' => 120],
                            'label' => [
                                'en' => 'Guardian name',
                                'ar' => 'اسم ولي الأمر',
                                'fr' => 'Nom du responsable légal',
                                'es' => 'Nombre del tutor',
                                'de' => 'Name des Erziehungsberechtigten',
                                'it' => 'Nome del tutore',
                                'pt' => 'Nome do encarregado de educação',
                                'ru' => 'Имя родителя или опекуна',
                                'hi' => 'अभिभावक का नाम',
                            ],
                        ],
                        [
                            'type' => 'email',
                            'key' => 'email',
                            'is_required' => true,
                            'label' => [
                                'en' => 'Email address',
                                'ar' => 'البريد الإلكتروني',
                                'fr' => 'Adresse e-mail',
                                'es' => 'Correo electrónico',
                                'de' => 'E-Mail-Adresse',
                                'it' => 'Indirizzo e-mail',
                                'pt' => 'Endereço de e-mail',
                                'ru' => 'Электронная почта',
                                'hi' => 'ईमेल पता',
                            ],
                        ],
                        [
                            'type' => 'phone',
                            'key' => 'phone',
                            'is_required' => true,
                            'label' => [
                                'en' => 'Phone number',
                                'ar' => 'رقم الهاتف',
                                'fr' => 'Numéro de téléphone',
                                'es' => 'Número de teléfono',
                                'de' => 'Telefonnummer',
                                'it' => 'Numero di telefono',
                                'pt' => 'Número de telefone',
                                'ru' => 'Номер телефона',
                                'hi' => 'फ़ोन नंबर',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'key' => 'student_name',
                            'is_required' => true,
                            'validation' => ['max_length' => 120],
                            'label' => [
                                'en' => 'Student name',
                                'ar' => 'اسم الطالب',
                                'fr' => 'Nom de l\'élève',
                                'es' => 'Nombre del alumno',
                                'de' => 'Name des Schülers',
                                'it' => 'Nome dello studente',
                                'pt' => 'Nome do aluno',
                                'ru' => 'Имя учащегося',
                                'hi' => 'छात्र का नाम',
                            ],
                        ],
                        // 'today' is resolved to a concrete date by DateType::resolve() at
                        // render time — a relative string cannot be compared against a
                        // date_format rule, which is why it resolves there.
                        [
                            'type' => 'date',
                            'key' => 'student_birthdate',
                            'is_required' => true,
                            'validation' => ['max_date' => 'today'],
                            'label' => [
                                'en' => 'Student date of birth',
                                'ar' => 'تاريخ ميلاد الطالب',
                                'fr' => 'Date de naissance de l\'élève',
                                'es' => 'Fecha de nacimiento del alumno',
                                'de' => 'Geburtsdatum des Schülers',
                                'it' => 'Data di nascita dello studente',
                                'pt' => 'Data de nascimento do aluno',
                                'ru' => 'Дата рождения учащегося',
                                'hi' => 'छात्र की जन्म तिथि',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'key' => 'student_school',
                            'validation' => ['max_length' => 160],
                            'label' => [
                                'en' => 'Current school',
                                'ar' => 'المدرسة الحالية',
                                'fr' => 'École actuelle',
                                'es' => 'Centro actual',
                                'de' => 'Derzeitige Schule',
                                'it' => 'Scuola attuale',
                                'pt' => 'Escola atual',
                                'ru' => 'Текущая школа',
                                'hi' => 'वर्तमान विद्यालय',
                            ],
                        ],
                        [
                            'type' => 'select',
                            'key' => 'academic_year',
                            'is_required' => true,
                            // A fixed list rather than a generated one: a seeder runs once, and
                            // a range computed from the seed date would silently go stale.
                            // Extend it here.
                            'options' => [
                                ['value' => '2026/2027', 'label' => '2026/2027'],
                                ['value' => '2027/2028', 'label' => '2027/2028'],
                                ['value' => '2028/2029', 'label' => '2028/2029'],
                                ['value' => '2029/2030', 'label' => '2029/2030'],
                                ['value' => '2030/2031', 'label' => '2030/2031'],
                            ],
                            'label' => [
                                'en' => 'Academic year',
                                'ar' => 'العام الدراسي',
                                'fr' => 'Année scolaire',
                                'es' => 'Curso académico',
                                'de' => 'Schuljahr',
                                'it' => 'Anno scolastico',
                                'pt' => 'Ano letivo',
                                'ru' => 'Учебный год',
                                'hi' => 'शैक्षणिक वर्ष',
                            ],
                        ],
                        [
                            'type' => 'select',
                            'key' => 'grade',
                            'is_required' => true,
                            // Option VALUES are never translated — the same answer has to read
                            // identically whatever language it was given in, which is what makes
                            // an export comparable.
                            'options' => [
                                ['value' => 'prek', 'label' => 'PreK'],
                                ['value' => 'kg1', 'label' => 'KG1'],
                                ['value' => 'kg2', 'label' => 'KG2'],
                                ['value' => 'g1', 'label' => 'Grade 1'],
                                ['value' => 'g2', 'label' => 'Grade 2'],
                                ['value' => 'g3', 'label' => 'Grade 3'],
                                ['value' => 'g4', 'label' => 'Grade 4'],
                                ['value' => 'g5', 'label' => 'Grade 5'],
                                ['value' => 'g6', 'label' => 'Grade 6'],
                                ['value' => 'g7', 'label' => 'Grade 7'],
                                ['value' => 'g8', 'label' => 'Grade 8'],
                                ['value' => 'g9', 'label' => 'Grade 9'],
                                ['value' => 'g10', 'label' => 'Grade 10'],
                                ['value' => 'g11', 'label' => 'Grade 11'],
                                ['value' => 'g12', 'label' => 'Grade 12'],
                            ],
                            'label' => [
                                'en' => 'Grade applied for',
                                'ar' => 'الصف المتقدَّم إليه',
                                'fr' => 'Niveau demandé',
                                'es' => 'Curso solicitado',
                                'de' => 'Gewünschte Klassenstufe',
                                'it' => 'Classe richiesta',
                                'pt' => 'Ano pretendido',
                                'ru' => 'Желаемый класс',
                                'hi' => 'आवेदित कक्षा',
                            ],
                        ],
                        [
                            'type' => 'textarea',
                            'key' => 'questions',
                            'settings' => ['rows' => 5],
                            'validation' => ['max_length' => 4000],
                            'label' => [
                                'en' => 'Questions',
                                'ar' => 'أسئلتك',
                                'fr' => 'Questions',
                                'es' => 'Preguntas',
                                'de' => 'Fragen',
                                'it' => 'Domande',
                                'pt' => 'Questões',
                                'ru' => 'Вопросы',
                                'hi' => 'प्रश्न',
                            ],
                        ],
                        [
                            'type' => 'button',
                            'key' => 'submit',
                            'settings' => ['action' => 'submit', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Send inquiry',
                                'ar' => 'إرسال الطلب',
                                'fr' => 'Envoyer la demande',
                                'es' => 'Enviar solicitud',
                                'de' => 'Anfrage senden',
                                'it' => 'Invia la richiesta',
                                'pt' => 'Enviar pedido',
                                'ru' => 'Отправить заявку',
                                'hi' => 'पूछताछ भेजें',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
     * How long a rendered form's submission token stays valid, in minutes. The
     * token carries the honeypot field name and the render timestamp, so this is
     * also how long a visitor has to fill the form in before being asked to
     * reload.
     */
    'token_ttl' => (int) env('FORMS_TOKEN_TTL', 120),

    'spam' => [
        // Default floor for "no human filled this in that fast". A form may
        // override it; null on the form means this value.
        'min_seconds' => (int) env('FORMS_MIN_SUBMIT_SECONDS', 5),

        // Score at or above which a submission is filed as spam.
        'threshold' => (int) env('FORMS_SPAM_THRESHOLD', 50),
    ],

    'uploads' => [
        // Files one anonymous session may upload, so an abandoned form cannot
        // be used as free storage.
        'max_per_session' => (int) env('FORMS_MAX_UPLOADS_PER_SESSION', 10),

        // How long the browser waits for a virus scan before submitting anyway.
        'scan_wait_seconds' => (int) env('FORMS_SCAN_WAIT_SECONDS', 15),
    ],

    'submissions' => [
        // Unfinished submissions older than this are pruned by model:prune.
        // COMPLETED submissions are never pruned — they are the record.
        'prune_after_days' => (int) env('FORMS_PRUNE_AFTER_DAYS', 90),

        // A draft nobody touched for this long is DELETED by model:prune.
        'prune_started_after_hours' => (int) env('FORMS_PRUNE_STARTED_AFTER_HOURS', 24),

        // A draft nobody touched for this long is swept to `abandoned` by
        // forms:close-abandoned. MUST stay well below prune_started_after_hours
        // above, or the nightly prune deletes drafts before they are ever
        // classified and the abandonment funnel reads empty forever.
        'abandon_after_minutes' => (int) env('FORMS_ABANDON_AFTER_MINUTES', 30),

        // Drafts one address may open per hour PER FORM, so a script cannot fill
        // the table. Counted per form on purpose: one ip_hash is often a whole
        // office, campus or carrier NAT, and site-wide the busiest form would
        // spend the allowance for every other form on the site.
        'max_drafts_per_hour' => (int) env('FORMS_MAX_DRAFTS_PER_HOUR', 20),

        // How long a signed link to an answer file stays valid, in minutes.
        // Answer uploads live on the private disk and have no URL of their own,
        // so this is the whole lifetime of a shared link — short on purpose.
        'file_link_ttl' => (int) env('FORMS_FILE_LINK_TTL', 30),

        // Rows read per keyset chunk while the CSV export streams.
        'export_chunk' => (int) env('FORMS_EXPORT_CHUNK', 500),
    ],

    'webhooks' => [
        // Mandatory while the queue is sync: without it a dead endpoint holds
        // the visitor's browser open for the full connection timeout.
        'timeout' => (int) env('FORMS_WEBHOOK_TIMEOUT', 10),
    ],

    'limits' => [
        'submits_per_minute' => (int) env('FORMS_SUBMITS_PER_MINUTE', 10),
        'uploads_per_minute' => (int) env('FORMS_UPLOADS_PER_MINUTE', 20),

        // Beacons. Higher than the others because one visit sends several: the
        // first interaction, every page change, a heartbeat while typing, and
        // one on the way out.
        'telemetry_per_minute' => (int) env('FORMS_TELEMETRY_PER_MINUTE', 60),
    ],

    'geo' => [
        /*
         * Headers a CDN or load balancer uses to report the visitor's country.
         * Trusted ONLY when the request arrived through a proxy TrustProxies
         * recognises — otherwise any client could set one and walk straight past
         * a country block.
         */
        'country_headers' => ['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country', 'X-Country-Code'],
    ],

    /*
     * What the public page measures. Everything here is COUNTS and TIMINGS —
     * keystroke content and pasted text are never transmitted or stored.
     */
    'capture' => [
        'timing' => (bool) env('FORMS_CAPTURE_TIMING', true),
        'behaviour' => (bool) env('FORMS_CAPTURE_BEHAVIOUR', true),
        'scroll' => (bool) env('FORMS_CAPTURE_SCROLL', true),
        'keystrokes' => (bool) env('FORMS_CAPTURE_KEYSTROKES', true),

        // Off by default: it needs a high-frequency listener to measure
        // something no chart reads, and it is the first thing a privacy review
        // asks about.
        'mouse' => (bool) env('FORMS_CAPTURE_MOUSE', false),
    ],
];
