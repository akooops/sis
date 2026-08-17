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
        App\Services\Forms\FieldTypes\SeparatorType::class,

        App\Services\Forms\FieldTypes\TextType::class,
        App\Services\Forms\FieldTypes\TextareaType::class,
        App\Services\Forms\FieldTypes\NumberType::class,
        App\Services\Forms\FieldTypes\EmailType::class,
        App\Services\Forms\FieldTypes\PhoneType::class,
        App\Services\Forms\FieldTypes\DateType::class,
        App\Services\Forms\FieldTypes\FileType::class,
        App\Services\Forms\FieldTypes\HiddenType::class,
        App\Services\Forms\FieldTypes\TagsType::class,

        App\Services\Forms\FieldTypes\SelectType::class,
        App\Services\Forms\FieldTypes\RadioType::class,
        App\Services\Forms\FieldTypes\CheckboxType::class,
        App\Services\Forms\FieldTypes\ConsentType::class,

        App\Services\Forms\FieldTypes\GroupType::class,

        App\Services\Forms\FieldTypes\ButtonType::class,
    ],

    /*
     * EXTRA SUBMIT-TIME RULES, BY FORM SLUG.
     *
     * Each class implements App\Contracts\Forms\SubmissionRule and returns rules
     * keyed by the same `fields.<key>` paths the field registry produces;
     * SubmissionValidator MERGES them onto what the fields already declared, so a
     * provider adds a rule to an input rather than replacing its own.
     *
     * For the checks a field cannot make on its own — "has this person already
     * applied to this posting?", "is that posting still open?" — because they
     * read two answers together, or another table. Returning a rule rather than
     * aborting is what puts the message under the input the visitor can fix.
     *
     * KEYED BY SLUG, HERE, NOT IN A COLUMN. Which rules a form needs is
     * application code; a database row naming a class is a deploy that can break
     * a live form. And it sits at the top level rather than inside the `system`
     * array below because that array is several hundred lines of wording and a
     * one-line behavioural hook would be invisible in it.
     */
    'submission_rules' => [
        'job-application' => [App\Rules\Forms\ApplicationIsAllowed::class],
    ],

    /*
     * WHAT A COMPLETED SUBMISSION BECOMES, BY FORM SLUG.
     *
     * The seam between this module and a domain. Forms own how something was
     * collected — the builder-rendered fields, the steps, the uploads, the
     * honeypot, the captcha, the country and IP blocks, the per-visitor caps,
     * nine locales. A projector owns what it MEANS once it is complete.
     *
     * Each class implements App\Contracts\Forms\SubmissionProjector and is called
     * from ProcessFormSubmission, after the response has already gone to the
     * visitor. Only `completed` submissions reach one, a throw is logged rather
     * than fatal, and an implementation must be idempotent — see the contract.
     *
     * A SECOND DOMAIN IS A CLASS AND A LINE HERE. Nothing in the forms module
     * changes to add one, which is the whole point of registering them rather
     * than branching on a slug inside the job.
     */
    'projectors' => [
        'job-application' => App\Services\Jobs\ApplicationProjector::class,
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
                            //
                            // No `label`: a year span is a code that reads the same in every
                            // language, so the VALUE is the label and the seeder writes it to
                            // all nine locales — an admin who wants a Hijri annotation in
                            // Arabic then has a row to edit rather than a blank to discover.
                            'options' => [
                                ['value' => '2026/2027'],
                                ['value' => '2027/2028'],
                                ['value' => '2028/2029'],
                                ['value' => '2029/2030'],
                                ['value' => '2030/2031'],
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
                                ['value' => 'prek', 'label' => [
                                    'en' => 'Pre-K',
                                    'ar' => 'ما قبل الروضة',
                                    'fr' => 'Petite section',
                                    'es' => 'Preescolar',
                                    'de' => 'Vorschule',
                                    'it' => 'Prescolare',
                                    'pt' => 'Pré-escolar',
                                    'ru' => 'Дошкольная группа',
                                    'hi' => 'प्री-के',
                                ]],
                                ['value' => 'kg1', 'label' => [
                                    'en' => 'KG1',
                                    'ar' => 'الروضة الأولى',
                                    'fr' => 'Maternelle 1',
                                    'es' => 'Infantil 1',
                                    'de' => 'Kindergarten 1',
                                    'it' => 'Materna 1',
                                    'pt' => 'Jardim 1',
                                    'ru' => 'Подготовка 1',
                                    'hi' => 'केजी1',
                                ]],
                                ['value' => 'kg2', 'label' => [
                                    'en' => 'KG2',
                                    'ar' => 'الروضة الثانية',
                                    'fr' => 'Maternelle 2',
                                    'es' => 'Infantil 2',
                                    'de' => 'Kindergarten 2',
                                    'it' => 'Materna 2',
                                    'pt' => 'Jardim 2',
                                    'ru' => 'Подготовка 2',
                                    'hi' => 'केजी2',
                                ]],
                                ['value' => 'g1', 'label' => [
                                    'en' => 'Grade 1',
                                    'ar' => 'الصف 1',
                                    'fr' => 'Année 1',
                                    'es' => 'Curso 1',
                                    'de' => 'Klasse 1',
                                    'it' => 'Classe 1',
                                    'pt' => 'Ano 1',
                                    'ru' => '1-й класс',
                                    'hi' => 'कक्षा 1',
                                ]],
                                ['value' => 'g2', 'label' => [
                                    'en' => 'Grade 2',
                                    'ar' => 'الصف 2',
                                    'fr' => 'Année 2',
                                    'es' => 'Curso 2',
                                    'de' => 'Klasse 2',
                                    'it' => 'Classe 2',
                                    'pt' => 'Ano 2',
                                    'ru' => '2-й класс',
                                    'hi' => 'कक्षा 2',
                                ]],
                                ['value' => 'g3', 'label' => [
                                    'en' => 'Grade 3',
                                    'ar' => 'الصف 3',
                                    'fr' => 'Année 3',
                                    'es' => 'Curso 3',
                                    'de' => 'Klasse 3',
                                    'it' => 'Classe 3',
                                    'pt' => 'Ano 3',
                                    'ru' => '3-й класс',
                                    'hi' => 'कक्षा 3',
                                ]],
                                ['value' => 'g4', 'label' => [
                                    'en' => 'Grade 4',
                                    'ar' => 'الصف 4',
                                    'fr' => 'Année 4',
                                    'es' => 'Curso 4',
                                    'de' => 'Klasse 4',
                                    'it' => 'Classe 4',
                                    'pt' => 'Ano 4',
                                    'ru' => '4-й класс',
                                    'hi' => 'कक्षा 4',
                                ]],
                                ['value' => 'g5', 'label' => [
                                    'en' => 'Grade 5',
                                    'ar' => 'الصف 5',
                                    'fr' => 'Année 5',
                                    'es' => 'Curso 5',
                                    'de' => 'Klasse 5',
                                    'it' => 'Classe 5',
                                    'pt' => 'Ano 5',
                                    'ru' => '5-й класс',
                                    'hi' => 'कक्षा 5',
                                ]],
                                ['value' => 'g6', 'label' => [
                                    'en' => 'Grade 6',
                                    'ar' => 'الصف 6',
                                    'fr' => 'Année 6',
                                    'es' => 'Curso 6',
                                    'de' => 'Klasse 6',
                                    'it' => 'Classe 6',
                                    'pt' => 'Ano 6',
                                    'ru' => '6-й класс',
                                    'hi' => 'कक्षा 6',
                                ]],
                                ['value' => 'g7', 'label' => [
                                    'en' => 'Grade 7',
                                    'ar' => 'الصف 7',
                                    'fr' => 'Année 7',
                                    'es' => 'Curso 7',
                                    'de' => 'Klasse 7',
                                    'it' => 'Classe 7',
                                    'pt' => 'Ano 7',
                                    'ru' => '7-й класс',
                                    'hi' => 'कक्षा 7',
                                ]],
                                ['value' => 'g8', 'label' => [
                                    'en' => 'Grade 8',
                                    'ar' => 'الصف 8',
                                    'fr' => 'Année 8',
                                    'es' => 'Curso 8',
                                    'de' => 'Klasse 8',
                                    'it' => 'Classe 8',
                                    'pt' => 'Ano 8',
                                    'ru' => '8-й класс',
                                    'hi' => 'कक्षा 8',
                                ]],
                                ['value' => 'g9', 'label' => [
                                    'en' => 'Grade 9',
                                    'ar' => 'الصف 9',
                                    'fr' => 'Année 9',
                                    'es' => 'Curso 9',
                                    'de' => 'Klasse 9',
                                    'it' => 'Classe 9',
                                    'pt' => 'Ano 9',
                                    'ru' => '9-й класс',
                                    'hi' => 'कक्षा 9',
                                ]],
                                ['value' => 'g10', 'label' => [
                                    'en' => 'Grade 10',
                                    'ar' => 'الصف 10',
                                    'fr' => 'Année 10',
                                    'es' => 'Curso 10',
                                    'de' => 'Klasse 10',
                                    'it' => 'Classe 10',
                                    'pt' => 'Ano 10',
                                    'ru' => '10-й класс',
                                    'hi' => 'कक्षा 10',
                                ]],
                                ['value' => 'g11', 'label' => [
                                    'en' => 'Grade 11',
                                    'ar' => 'الصف 11',
                                    'fr' => 'Année 11',
                                    'es' => 'Curso 11',
                                    'de' => 'Klasse 11',
                                    'it' => 'Classe 11',
                                    'pt' => 'Ano 11',
                                    'ru' => '11-й класс',
                                    'hi' => 'कक्षा 11',
                                ]],
                                ['value' => 'g12', 'label' => [
                                    'en' => 'Grade 12',
                                    'ar' => 'الصف 12',
                                    'fr' => 'Année 12',
                                    'es' => 'Curso 12',
                                    'de' => 'Klasse 12',
                                    'it' => 'Classe 12',
                                    'pt' => 'Ano 12',
                                    'ru' => '12-й класс',
                                    'hi' => 'कक्षा 12',
                                ]],
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

        /*
         * The job application.
         *
         * FIVE STEPS: who you are, then ONE STEP PER REPEATABLE SECTION. Packing
         * the sections together fails on height — education, experience and
         * languages each grow to ten rows or more, so a shared page's length is
         * set by the applicant rather than by the form, and the section below is
         * pushed off-screen by the one above it. The cost is that the server
         * validates on submit, so a rejected application can reopen several
         * screens from the mistake; that is the trade, and the groups are what
         * make it worth paying.
         *
         * The CV sits LAST on the first page: someone says who they are, then
         * attaches the file. Its position is free — the parse-and-prefill flow
         * uploads through the chooser BEFORE the renderer mounts and hands it
         * initial `values`, so it never reads this field's place in the page.
         *
         * `job_offer_id` is hidden and carries which posting this is against. It is
         * visitor-tamperable, so ApplicationProjector re-reads the posting rather
         * than trusting it, and an absent value falls back to the seeded
         * general-application posting.
         */
        [
            'slug' => 'job-application',
            'name' => 'Job application',
            'title' => [
                'en' => 'Apply', 'ar' => 'تقديم طلب', 'fr' => 'Postuler', 'es' => 'Solicitar',
                'de' => 'Bewerben', 'it' => 'Candidati', 'pt' => 'Candidatar-se',
                'ru' => 'Подать заявку', 'hi' => 'आवेदन करें',
            ],
            'confirmation_message' => [
                'en' => 'Thank you. We have received your application and will be in touch if there is a suitable opening.',
                'ar' => 'شكرًا لك. لقد تلقينا طلبك وسنتواصل معك في حال توفر وظيفة مناسبة.',
                'fr' => 'Merci. Nous avons bien reçu votre candidature et vous contacterons si un poste correspond.',
                'es' => 'Gracias. Hemos recibido su candidatura y le contactaremos si hay una vacante adecuada.',
                'de' => 'Vielen Dank. Wir haben Ihre Bewerbung erhalten und melden uns bei einer passenden Stelle.',
                'it' => 'Grazie. Abbiamo ricevuto la tua candidatura e ti contatteremo per una posizione adatta.',
                'pt' => 'Obrigado. Recebemos a sua candidatura e entraremos em contacto se houver uma vaga adequada.',
                'ru' => 'Спасибо. Мы получили вашу заявку и свяжемся с вами при наличии подходящей вакансии.',
                'hi' => 'धन्यवाद। हमें आपका आवेदन मिल गया है और उपयुक्त रिक्ति होने पर हम संपर्क करेंगे।',
            ],
            'pages' => [
                [
                    'name' => 'Personal',
                    'title' => [
                        'en' => 'Your details',
                        'ar' => 'بياناتك',
                        'fr' => 'Vos informations',
                        'es' => 'Sus datos',
                        'de' => 'Ihre Angaben',
                        'it' => 'I tuoi dati',
                        'pt' => 'Os seus dados',
                        'ru' => 'Ваши данные',
                        'hi' => 'आपका विवरण',
                    ],
                    'fields' => [
                        [
                            'type' => 'text', 'key' => 'first_name', 'is_required' => true,
                            'settings' => ['width' => '50'],
                            'validation' => ['max_length' => 100],
                            'label' => [
                                'en' => 'First name', 'ar' => 'الاسم الأول', 'fr' => 'Prénom',
                                'es' => 'Nombre', 'de' => 'Vorname', 'it' => 'Nome',
                                'pt' => 'Nome próprio', 'ru' => 'Имя', 'hi' => 'पहला नाम',
                            ],
                        ],
                        [
                            'type' => 'text', 'key' => 'last_name', 'is_required' => true,
                            'settings' => ['width' => '50'],
                            'validation' => ['max_length' => 100],
                            'label' => [
                                'en' => 'Last name', 'ar' => 'اسم العائلة', 'fr' => 'Nom',
                                'es' => 'Apellidos', 'de' => 'Nachname', 'it' => 'Cognome',
                                'pt' => 'Apelido', 'ru' => 'Фамилия', 'hi' => 'उपनाम',
                            ],
                        ],
                        [
                            'type' => 'email', 'key' => 'email', 'is_required' => true,
                            'settings' => ['width' => '50'],
                            'label' => [
                                'en' => 'Email', 'ar' => 'البريد الإلكتروني', 'fr' => 'E-mail',
                                'es' => 'Correo electrónico', 'de' => 'E-Mail', 'it' => 'E-mail',
                                'pt' => 'E-mail', 'ru' => 'Эл. почта', 'hi' => 'ईमेल',
                            ],
                        ],
                        [
                            'type' => 'phone', 'key' => 'phone', 'is_required' => true,
                            'settings' => ['width' => '50'],
                            'label' => [
                                'en' => 'Phone', 'ar' => 'رقم الهاتف', 'fr' => 'Téléphone',
                                'es' => 'Teléfono', 'de' => 'Telefon', 'it' => 'Telefono',
                                'pt' => 'Telefone', 'ru' => 'Телефон', 'hi' => 'फ़ोन',
                            ],
                        ],
                        [
                            // The ISO code is the answer, so a nationality means the
                            // same thing whichever language it was chosen in.
                            'type' => 'select', 'key' => 'nationality', 'is_required' => true,
                            'options_from' => 'countries',
                            'label' => [
                                'en' => 'Nationality', 'ar' => 'الجنسية', 'fr' => 'Nationalité',
                                'es' => 'Nacionalidad', 'de' => 'Staatsangehörigkeit', 'it' => 'Nazionalità',
                                'pt' => 'Nacionalidade', 'ru' => 'Гражданство', 'hi' => 'राष्ट्रीयता',
                            ],
                        ],
                        [
                            'type' => 'textarea', 'key' => 'address',
                            'settings' => ['rows' => 3],
                            'validation' => ['max_length' => 500],
                            'label' => [
                                'en' => 'Address', 'ar' => 'العنوان', 'fr' => 'Adresse',
                                'es' => 'Dirección', 'de' => 'Adresse', 'it' => 'Indirizzo',
                                'pt' => 'Morada', 'ru' => 'Адрес', 'hi' => 'पता',
                            ],
                        ],
                        [
                            // The one split left that a page boundary does not
                            // make: attaching a file is a different act from
                            // typing, and it closes the page rather than opening
                            // one of its own.
                            //
                            // DELIBERATELY UNCAPTIONED. The field immediately
                            // below is labelled "Curriculum vitae", so a caption
                            // saying it too would print the same words twice in a
                            // row — the failure that made FormRenderer stop
                            // printing the form's own title above its first input.
                            // A bare rule is the break; the field names itself.
                            'type' => 'separator', 'key' => 'sep_cv',
                            'settings' => ['spacing' => 'normal'],
                        ],
                        [
                            'type' => 'file', 'key' => 'cv', 'is_required' => true,
                            'settings' => ['is_multiple' => false, 'max_files' => 1, 'extensions' => ['pdf', 'doc', 'docx']],
                            'label' => [
                                'en' => 'Curriculum vitae', 'ar' => 'السيرة الذاتية', 'fr' => 'CV',
                                'es' => 'Currículum', 'de' => 'Lebenslauf', 'it' => 'Curriculum',
                                'pt' => 'Currículo', 'ru' => 'Резюме', 'hi' => 'बायोडाटा',
                            ],
                        ],
                        ['type' => 'hidden', 'key' => 'job_offer_id'],
                        [
                            'type' => 'button', 'key' => 'to_education',
                            'settings' => ['action' => 'next', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Education',
                    'title' => [
                        'en' => 'Education',
                        'ar' => 'المؤهلات العلمية',
                        'fr' => 'Formation',
                        'es' => 'Formación',
                        'de' => 'Ausbildung',
                        'it' => 'Formazione',
                        'pt' => 'Formação',
                        'ru' => 'Образование',
                        'hi' => 'शिक्षा',
                    ],
                    'fields' => [
                        [
                            /*
                             * NOT REQUIRED, and no minimum row. The school hires
                             * across every role — teaching, admin, cleaning,
                             * maintenance, drivers — and demanding a qualification
                             * before someone can apply for a manual post turns the
                             * form into a filter nobody intended.
                             */
                            'type' => 'group', 'key' => 'education', 'is_required' => false,
                            'settings' => ['min_instances' => 0, 'max_instances' => 10],
                            'label' => [
                                'en' => 'Education', 'ar' => 'المؤهلات العلمية', 'fr' => 'Formation',
                                'es' => 'Formación', 'de' => 'Ausbildung', 'it' => 'Formazione',
                                'pt' => 'Formação', 'ru' => 'Образование', 'hi' => 'शिक्षा',
                            ],
                            'children' => [
                                [
                                    'type' => 'text', 'key' => 'institution', 'is_required' => true,
                                    'label' => [
                                        'en' => 'Institution', 'ar' => 'المؤسسة التعليمية', 'fr' => 'Établissement',
                                        'es' => 'Institución', 'de' => 'Einrichtung', 'it' => 'Istituto',
                                        'pt' => 'Instituição', 'ru' => 'Учебное заведение', 'hi' => 'संस्थान',
                                    ],
                                ],
                                [
                                    'type' => 'text', 'key' => 'degree',
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Degree', 'ar' => 'الدرجة العلمية', 'fr' => 'Diplôme',
                                        'es' => 'Titulación', 'de' => 'Abschluss', 'it' => 'Titolo',
                                        'pt' => 'Grau', 'ru' => 'Степень', 'hi' => 'डिग्री',
                                    ],
                                ],
                                [
                                    'type' => 'text', 'key' => 'field_of_study',
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Field of study', 'ar' => 'مجال الدراسة', 'fr' => 'Domaine d’études',
                                        'es' => 'Campo de estudio', 'de' => 'Fachrichtung', 'it' => 'Ambito di studio',
                                        'pt' => 'Área de estudo', 'ru' => 'Специальность', 'hi' => 'अध्ययन क्षेत्र',
                                    ],
                                ],
                                [
                                    'type' => 'number', 'key' => 'start_year',
                                    'settings' => ['width' => '50'],
                                    'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                    'label' => [
                                        'en' => 'Start year', 'ar' => 'سنة البدء', 'fr' => 'Année de début',
                                        'es' => 'Año de inicio', 'de' => 'Startjahr', 'it' => 'Anno di inizio',
                                        'pt' => 'Ano de início', 'ru' => 'Год начала', 'hi' => 'प्रारंभ वर्ष',
                                    ],
                                ],
                                [
                                    'type' => 'number', 'key' => 'end_year',
                                    'settings' => ['width' => '50'],
                                    'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                    'label' => [
                                        'en' => 'End year', 'ar' => 'سنة الانتهاء', 'fr' => 'Année de fin',
                                        'es' => 'Año de fin', 'de' => 'Endjahr', 'it' => 'Anno di fine',
                                        'pt' => 'Ano de fim', 'ru' => 'Год окончания', 'hi' => 'समाप्ति वर्ष',
                                    ],
                                ],
                                [
                                    'type' => 'textarea', 'key' => 'achievements',
                                    'settings' => ['rows' => 2],
                                    'validation' => ['max_length' => 1000],
                                    'label' => [
                                        'en' => 'Achievements', 'ar' => 'الإنجازات', 'fr' => 'Résultats obtenus',
                                        'es' => 'Logros', 'de' => 'Erreichtes', 'it' => 'Risultati',
                                        'pt' => 'Conquistas', 'ru' => 'Достижения', 'hi' => 'उपलब्धियाँ',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'back_to_details',
                            'settings' => ['action' => 'back', 'variant' => 'secondary'],
                            'label' => [
                                'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                'ru' => 'Назад', 'hi' => 'वापस',
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'to_experience',
                            'settings' => ['action' => 'next', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Experience',
                    'title' => [
                        'en' => 'Work experience',
                        'ar' => 'الخبرة العملية',
                        'fr' => 'Expérience professionnelle',
                        'es' => 'Experiencia laboral',
                        'de' => 'Berufserfahrung',
                        'it' => 'Esperienza lavorativa',
                        'pt' => 'Experiência profissional',
                        'ru' => 'Опыт работы',
                        'hi' => 'कार्य अनुभव',
                    ],
                    'fields' => [
                        [
                            'type' => 'group', 'key' => 'experience',
                            'settings' => ['min_instances' => 0, 'max_instances' => 15],
                            'label' => [
                                'en' => 'Work experience', 'ar' => 'الخبرة العملية', 'fr' => 'Expérience professionnelle',
                                'es' => 'Experiencia laboral', 'de' => 'Berufserfahrung', 'it' => 'Esperienza lavorativa',
                                'pt' => 'Experiência profissional', 'ru' => 'Опыт работы', 'hi' => 'कार्य अनुभव',
                            ],
                            'children' => [
                                [
                                    'type' => 'text', 'key' => 'company_name', 'is_required' => true,
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Employer', 'ar' => 'جهة العمل', 'fr' => 'Employeur',
                                        'es' => 'Empleador', 'de' => 'Arbeitgeber', 'it' => 'Datore di lavoro',
                                        'pt' => 'Empregador', 'ru' => 'Работодатель', 'hi' => 'नियोक्ता',
                                    ],
                                ],
                                [
                                    'type' => 'text', 'key' => 'job_title',
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Job title', 'ar' => 'المسمى الوظيفي', 'fr' => 'Intitulé du poste',
                                        'es' => 'Puesto', 'de' => 'Position', 'it' => 'Ruolo',
                                        'pt' => 'Cargo', 'ru' => 'Должность', 'hi' => 'पद',
                                    ],
                                ],
                                [
                                    'type' => 'number', 'key' => 'from_year',
                                    'settings' => ['width' => '50'],
                                    'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                    'label' => [
                                        'en' => 'Start year', 'ar' => 'سنة البدء', 'fr' => 'Année de début',
                                        'es' => 'Año de inicio', 'de' => 'Startjahr', 'it' => 'Anno di inizio',
                                        'pt' => 'Ano de início', 'ru' => 'Год начала', 'hi' => 'प्रारंभ वर्ष',
                                    ],
                                ],
                                [
                                    'type' => 'number', 'key' => 'to_year',
                                    'settings' => ['width' => '50'],
                                    'validation' => ['min' => 1950, 'max' => 2100, 'integer_only' => true],
                                    'label' => [
                                        'en' => 'End year', 'ar' => 'سنة الانتهاء', 'fr' => 'Année de fin',
                                        'es' => 'Año de fin', 'de' => 'Endjahr', 'it' => 'Anno di fine',
                                        'pt' => 'Ano de fim', 'ru' => 'Год окончания', 'hi' => 'समाप्ति वर्ष',
                                    ],
                                ],
                                [
                                    'type' => 'textarea', 'key' => 'responsibilities',
                                    'settings' => ['rows' => 3],
                                    'validation' => ['max_length' => 1500],
                                    'label' => [
                                        'en' => 'Responsibilities', 'ar' => 'المهام والمسؤوليات',
                                        'fr' => 'Responsabilités', 'es' => 'Responsabilidades',
                                        'de' => 'Aufgaben', 'it' => 'Responsabilità',
                                        'pt' => 'Responsabilidades', 'ru' => 'Обязанности',
                                        'hi' => 'ज़िम्मेदारियाँ',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'back_to_education',
                            'settings' => ['action' => 'back', 'variant' => 'secondary'],
                            'label' => [
                                'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                'ru' => 'Назад', 'hi' => 'वापस',
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'to_languages',
                            'settings' => ['action' => 'next', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Languages',
                    'title' => [
                        'en' => 'Languages',
                        'ar' => 'اللغات',
                        'fr' => 'Langues',
                        'es' => 'Idiomas',
                        'de' => 'Sprachen',
                        'it' => 'Lingue',
                        'pt' => 'Idiomas',
                        'ru' => 'Языки',
                        'hi' => 'भाषाएँ',
                    ],
                    'fields' => [
                        [
                            'type' => 'group', 'key' => 'languages',
                            'settings' => ['min_instances' => 0, 'max_instances' => 10],
                            'label' => [
                                'en' => 'Languages', 'ar' => 'اللغات', 'fr' => 'Langues',
                                'es' => 'Idiomas', 'de' => 'Sprachen', 'it' => 'Lingue',
                                'pt' => 'Idiomas', 'ru' => 'Языки', 'hi' => 'भाषाएँ',
                            ],
                            'children' => [
                                [
                                    'type' => 'text', 'key' => 'name', 'is_required' => true,
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Language', 'ar' => 'اللغة', 'fr' => 'Langue',
                                        'es' => 'Idioma', 'de' => 'Sprache', 'it' => 'Lingua',
                                        'pt' => 'Idioma', 'ru' => 'Язык', 'hi' => 'भाषा',
                                    ],
                                ],
                                [
                                    // Values mirror Candidate::PROFICIENCIES, so the
                                    // projector writes them straight through.
                                    'type' => 'select', 'key' => 'proficiency',
                                    'settings' => ['width' => '50'],
                                    'label' => [
                                        'en' => 'Level', 'ar' => 'المستوى', 'fr' => 'Niveau',
                                        'es' => 'Nivel', 'de' => 'Niveau', 'it' => 'Livello',
                                        'pt' => 'Nível', 'ru' => 'Уровень', 'hi' => 'स्तर',
                                    ],
                                    'options' => [
                                        ['value' => 'basic', 'label' => [
                                            'en' => 'Basic', 'ar' => 'مبتدئ', 'fr' => 'Débutant', 'es' => 'Básico',
                                            'de' => 'Grundkenntnisse', 'it' => 'Base', 'pt' => 'Básico',
                                            'ru' => 'Базовый', 'hi' => 'बुनियादी',
                                        ]],
                                        ['value' => 'intermediate', 'label' => [
                                            'en' => 'Intermediate', 'ar' => 'متوسط', 'fr' => 'Intermédiaire',
                                            'es' => 'Intermedio', 'de' => 'Mittelstufe', 'it' => 'Intermedio',
                                            'pt' => 'Intermédio', 'ru' => 'Средний', 'hi' => 'मध्यम',
                                        ]],
                                        ['value' => 'advanced', 'label' => [
                                            'en' => 'Advanced', 'ar' => 'متقدم', 'fr' => 'Avancé', 'es' => 'Avanzado',
                                            'de' => 'Fortgeschritten', 'it' => 'Avanzato', 'pt' => 'Avançado',
                                            'ru' => 'Продвинутый', 'hi' => 'उन्नत',
                                        ]],
                                        ['value' => 'native', 'label' => [
                                            'en' => 'Native', 'ar' => 'اللغة الأم', 'fr' => 'Langue maternelle',
                                            'es' => 'Nativo', 'de' => 'Muttersprache', 'it' => 'Madrelingua',
                                            'pt' => 'Nativo', 'ru' => 'Родной', 'hi' => 'मातृभाषा',
                                        ]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'back_to_experience',
                            'settings' => ['action' => 'back', 'variant' => 'secondary'],
                            'label' => [
                                'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                'ru' => 'Назад', 'hi' => 'वापस',
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'to_skills',
                            'settings' => ['action' => 'next', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Continue', 'ar' => 'متابعة', 'fr' => 'Continuer',
                                'es' => 'Continuar', 'de' => 'Weiter', 'it' => 'Continua',
                                'pt' => 'Continuar', 'ru' => 'Продолжить', 'hi' => 'जारी रखें',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Skills',
                    'title' => [
                        'en' => 'Skills',
                        'ar' => 'المهارات',
                        'fr' => 'Compétences',
                        'es' => 'Competencias',
                        'de' => 'Fähigkeiten',
                        'it' => 'Competenze',
                        'pt' => 'Competências',
                        'ru' => 'Навыки',
                        'hi' => 'कौशल',
                    ],
                    'fields' => [
                        [
                            'type' => 'tags', 'key' => 'skills',
                            'validation' => ['max_items' => 20, 'max_length' => 60],
                            'label' => [
                                'en' => 'Skills', 'ar' => 'المهارات', 'fr' => 'Compétences',
                                'es' => 'Competencias', 'de' => 'Fähigkeiten', 'it' => 'Competenze',
                                'pt' => 'Competências', 'ru' => 'Навыки', 'hi' => 'कौशल',
                            ],
                            'placeholder' => [
                                'en' => 'Type a skill and press Enter', 'ar' => 'اكتب مهارة ثم اضغط Enter',
                                'fr' => 'Saisissez une compétence puis Entrée', 'es' => 'Escriba una competencia y pulse Intro',
                                'de' => 'Fähigkeit eingeben und Enter drücken', 'it' => 'Digita una competenza e premi Invio',
                                'pt' => 'Escreva uma competência e prima Enter', 'ru' => 'Введите навык и нажмите Enter',
                                'hi' => 'कौशल लिखें और Enter दबाएँ',
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'back_to_languages',
                            'settings' => ['action' => 'back', 'variant' => 'secondary'],
                            'label' => [
                                'en' => 'Back', 'ar' => 'رجوع', 'fr' => 'Retour', 'es' => 'Atrás',
                                'de' => 'Zurück', 'it' => 'Indietro', 'pt' => 'Voltar',
                                'ru' => 'Назад', 'hi' => 'वापस',
                            ],
                        ],
                        [
                            'type' => 'button', 'key' => 'submit_application',
                            'settings' => ['action' => 'submit', 'variant' => 'primary'],
                            'label' => [
                                'en' => 'Submit application', 'ar' => 'إرسال الطلب',
                                'fr' => 'Envoyer la candidature', 'es' => 'Enviar candidatura',
                                'de' => 'Bewerbung senden', 'it' => 'Invia candidatura',
                                'pt' => 'Enviar candidatura', 'ru' => 'Отправить заявку',
                                'hi' => 'आवेदन भेजें',
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
