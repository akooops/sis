<?php

namespace Database\Seeders;

use App\Models\VisitService;
use App\Models\VisitSlot;
use App\States\VisitService\Published;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * DEV FIXTURE. Four visits and a term of times, so a fresh checkout has a /visits
 * page with something on it.
 *
 * NOT SHIPPED, and that is a decision rather than an omission. The site seeds no
 * content in production — no articles, no events, no programmes — because what a
 * school offers is the school's to say. A visit service carries no contract the
 * app depends on (nothing resolves one by slug, none of them is is_system), so
 * unlike the general job posting it has no reason to exist before an admin makes
 * it.
 *
 * firstOrCreate throughout and deterministic — no rand(), no shuffle — so running
 * it twice changes nothing and two developers see the same calendar.
 */
class DemoVisitsSeeder extends Seeder
{
    /**
     * How far ahead to generate times. Long enough that the month the calendar
     * opens on is never empty, short enough that the fixture stays small.
     */
    protected const WEEKS = 8;

    /** Sunday and Tuesday — a Saudi school week, not a European one. */
    protected const DAYS = [0, 2];

    /** Tours run through the morning, on the hour. */
    protected const HOURS = [9, 10, 11];

    public function run(): void
    {
        foreach ($this->services() as $order => $definition) {
            $service = VisitService::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'duration_minutes' => 60,
                    'max_visitors' => 5,
                    'order' => $order,
                    'status' => Published::class,
                    'published_at' => now(),
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'content' => $definition['content'],
                ],
            );

            // Only for a service this run created: regenerating times over one an
            // admin has been editing would resurrect slots they closed.
            if ($service->wasRecentlyCreated) {
                $this->slots($service);
            }
        }
    }

    /**
     * A term of hourly tours.
     *
     * insertOrIgnore against the unique index, exactly as the admin bulk
     * generator does — so this and that cannot produce different data for the
     * same description of it.
     */
    protected function slots(VisitService $service): void
    {
        $start = CarbonImmutable::now()->startOfWeek()->startOfDay();
        $now = now();
        $rows = [];

        // The model's own generator, not Str::ulid(): insertOrIgnore bypasses
        // Eloquent, and HasUlids lowercases what the helper returns — so reaching
        // for it here would make these the only uppercase ids in the database.
        $blank = new VisitSlot;

        for ($week = 0; $week < self::WEEKS; $week++) {
            foreach (self::DAYS as $day) {
                $date = $start->addWeeks($week)->addDays($day);

                foreach (self::HOURS as $hour) {
                    $startsAt = $date->setTime($hour, 0);

                    // A fixture that half-fills the calendar with times already
                    // gone is a fixture nobody can book against.
                    if ($startsAt->isPast()) {
                        continue;
                    }

                    $rows[] = [
                        'id' => $blank->newUniqueId(),
                        'visit_service_id' => $service->id,
                        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                        'ends_at' => $startsAt->addHour()->format('Y-m-d H:i:s'),
                        // Varied on purpose so the four slot states are all
                        // reachable by hand: a 1-seat slot goes amber then red
                        // after a single booking.
                        'capacity' => $hour === 9 ? 1 : ($hour === 10 ? 3 : 8),
                        'is_open' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            VisitSlot::query()->insertOrIgnore($chunk);
        }
    }

    /**
     * The four stages the school actually tours, matching the live site.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function services(): array
    {
        return [
            [
                'slug' => 'early-learning',
                'name' => 'Early Learning',
                'title' => [
                    'en' => 'Early Learning', 'ar' => 'مرحلة التعلم المبكر', 'fr' => 'Petite enfance',
                    'es' => 'Primera infancia', 'de' => 'Frühe Bildung', 'it' => 'Prima infanzia',
                    'pt' => 'Primeira infância', 'ru' => 'Раннее развитие', 'hi' => 'प्रारंभिक शिक्षा',
                ],
                'description' => [
                    'en' => 'A play-based early years programme, from KG3 to Reception 2.',
                    'ar' => 'برنامج للسنوات المبكرة قائم على اللعب، من الروضة الثالثة إلى التمهيدي الثاني.',
                    'fr' => 'Un programme des premières années fondé sur le jeu, de la KG3 à la Reception 2.',
                    'es' => 'Un programa de primeros años basado en el juego, de KG3 a Reception 2.',
                    'de' => 'Ein spielbasiertes Frühjahresprogramm, von KG3 bis Reception 2.',
                    'it' => 'Un programma per la prima infanzia basato sul gioco, dalla KG3 alla Reception 2.',
                    'pt' => 'Um programa de primeiros anos baseado no brincar, do KG3 ao Reception 2.',
                    'ru' => 'Программа раннего развития через игру, от KG3 до Reception 2.',
                    'hi' => 'खेल-आधारित प्रारंभिक वर्ष कार्यक्रम, KG3 से Reception 2 तक।',
                ],
                'content' => $this->body(
                    'Come and see how our youngest pupils learn: through play, conversation and structured discovery. The tour takes in the early years classrooms, the outdoor learning area and the library, and finishes with time to ask our early years lead anything you like.',
                    'تعرّف على كيفية تعلّم أصغر طلابنا: عبر اللعب والحوار والاستكشاف المنظَّم. تشمل الجولة فصول السنوات المبكرة ومنطقة التعلم الخارجية والمكتبة، وتنتهي بوقت لطرح أي سؤال على مسؤولة المرحلة.',
                    'Venez voir comment nos plus jeunes élèves apprennent : par le jeu, la conversation et la découverte structurée. La visite comprend les salles de classe, l’espace extérieur et la bibliothèque.',
                    'Venga a ver cómo aprenden nuestros alumnos más pequeños: mediante el juego, la conversación y el descubrimiento estructurado. La visita incluye las aulas, el espacio exterior y la biblioteca.',
                    'Sehen Sie, wie unsere jüngsten Schülerinnen und Schüler lernen: durch Spiel, Gespräch und strukturiertes Entdecken. Der Rundgang führt durch die Klassenräume, den Außenbereich und die Bibliothek.',
                    'Venite a vedere come imparano i nostri alunni più piccoli: attraverso il gioco, la conversazione e la scoperta guidata. La visita comprende le aule, lo spazio esterno e la biblioteca.',
                    'Venha ver como aprendem os nossos alunos mais novos: pela brincadeira, pela conversa e pela descoberta estruturada. A visita inclui as salas, o espaço exterior e a biblioteca.',
                    'Посмотрите, как учатся наши самые младшие ученики: через игру, общение и структурированное открытие. Экскурсия включает классы, площадку и библиотеку.',
                    'देखिए हमारे सबसे छोटे विद्यार्थी कैसे सीखते हैं: खेल, संवाद और संरचित खोज के माध्यम से। भ्रमण में कक्षाएँ, बाहरी क्षेत्र और पुस्तकालय शामिल हैं।',
                ),
            ],
            [
                'slug' => 'elementary-school',
                'name' => 'Elementary School',
                'title' => [
                    'en' => 'Elementary School', 'ar' => 'المرحلة الابتدائية', 'fr' => 'École élémentaire',
                    'es' => 'Escuela primaria', 'de' => 'Grundschule', 'it' => 'Scuola elementare',
                    'pt' => 'Ensino básico', 'ru' => 'Начальная школа', 'hi' => 'प्राथमिक विद्यालय',
                ],
                'description' => [
                    'en' => 'An inquiry-based curriculum for Grades 1 to 5.',
                    'ar' => 'منهج قائم على الاستقصاء للصفوف من الأول إلى الخامس.',
                    'fr' => 'Un programme fondé sur l’investigation, de la 1re à la 5e année.',
                    'es' => 'Un currículo basado en la indagación, de 1.º a 5.º curso.',
                    'de' => 'Ein forschendes Curriculum für die Klassen 1 bis 5.',
                    'it' => 'Un curricolo basato sull’indagine, dalla classe 1 alla 5.',
                    'pt' => 'Um currículo baseado na investigação, do 1.º ao 5.º ano.',
                    'ru' => 'Исследовательская программа для 1–5 классов.',
                    'hi' => 'कक्षा 1 से 5 तक जिज्ञासा-आधारित पाठ्यक्रम।',
                ],
                'content' => $this->body(
                    'A walk through the elementary corridors during a normal working day, so you see lessons as they are rather than as a showcase. The tour covers the classrooms, the science and art rooms, the library and the play areas.',
                    'جولة في أروقة المرحلة الابتدائية خلال يوم دراسي عادي، لترى الدروس كما هي لا كعرض. تشمل الجولة الفصول ومختبرات العلوم وغرف الفنون والمكتبة وساحات اللعب.',
                    'Une promenade dans les couloirs de l’élémentaire pendant une journée ordinaire, pour voir les cours tels qu’ils sont. La visite couvre les salles de classe, les salles de sciences et d’arts, la bibliothèque et les cours de récréation.',
                    'Un recorrido por primaria durante una jornada normal, para ver las clases tal como son. Incluye las aulas, los laboratorios, las salas de arte, la biblioteca y los patios.',
                    'Ein Rundgang durch die Grundschule an einem gewöhnlichen Schultag. Der Rundgang umfasst die Klassenräume, die Natur- und Kunsträume, die Bibliothek und die Pausenhöfe.',
                    'Una passeggiata nei corridoi della primaria durante una normale giornata di scuola. La visita comprende le aule, i laboratori di scienze e arte, la biblioteca e i cortili.',
                    'Um percurso pelo ensino básico durante um dia normal de aulas. A visita inclui as salas, os laboratórios, a biblioteca e os recreios.',
                    'Прогулка по начальной школе в обычный учебный день. Экскурсия охватывает классы, кабинеты наук и искусств, библиотеку и площадки.',
                    'एक सामान्य कार्यदिवस में प्राथमिक विभाग का भ्रमण। इसमें कक्षाएँ, विज्ञान और कला कक्ष, पुस्तकालय और खेल क्षेत्र शामिल हैं।',
                ),
            ],
            [
                'slug' => 'middle-school',
                'name' => 'Middle School',
                'title' => [
                    'en' => 'Middle School', 'ar' => 'المرحلة المتوسطة', 'fr' => 'Collège',
                    'es' => 'Escuela media', 'de' => 'Mittelstufe', 'it' => 'Scuola media',
                    'pt' => 'Ensino intermédio', 'ru' => 'Средняя школа', 'hi' => 'माध्यमिक विद्यालय',
                ],
                'description' => [
                    'en' => 'Critical thinking, creativity and global awareness for Grades 6 to 8.',
                    'ar' => 'التفكير النقدي والإبداع والوعي العالمي للصفوف من السادس إلى الثامن.',
                    'fr' => 'Esprit critique, créativité et ouverture au monde, de la 6e à la 8e année.',
                    'es' => 'Pensamiento crítico, creatividad y conciencia global, de 6.º a 8.º.',
                    'de' => 'Kritisches Denken, Kreativität und Weltoffenheit für die Klassen 6 bis 8.',
                    'it' => 'Pensiero critico, creatività e apertura al mondo, dalla classe 6 alla 8.',
                    'pt' => 'Pensamento crítico, criatividade e consciência global, do 6.º ao 8.º ano.',
                    'ru' => 'Критическое мышление, творчество и глобальная осведомлённость, 6–8 классы.',
                    'hi' => 'कक्षा 6 से 8 के लिए आलोचनात्मक चिंतन, रचनात्मकता और वैश्विक जागरूकता।',
                ],
                'content' => $this->body(
                    'The middle years are where pupils start choosing their own direction. The tour takes in the specialist rooms, the technology suite and the sports facilities, and includes a conversation with a head of year about how we support the move up from elementary.',
                    'المرحلة المتوسطة هي التي يبدأ فيها الطلاب اختيار مسارهم. تشمل الجولة الغرف التخصصية وجناح التقنية والمرافق الرياضية، مع لقاء مع رائد الصف حول كيفية دعم الانتقال من الابتدائية.',
                    'Les années de collège sont celles où les élèves commencent à choisir leur voie. La visite comprend les salles spécialisées, le pôle technologique et les installations sportives.',
                    'En la escuela media los alumnos empiezan a elegir su propio camino. La visita incluye las aulas especializadas, el espacio de tecnología y las instalaciones deportivas.',
                    'In der Mittelstufe beginnen die Schülerinnen und Schüler, ihren eigenen Weg zu wählen. Der Rundgang umfasst die Fachräume, den Technikbereich und die Sportanlagen.',
                    'Negli anni della scuola media gli studenti iniziano a scegliere la propria direzione. La visita comprende le aule speciali, il polo tecnologico e le strutture sportive.',
                    'No ensino intermédio os alunos começam a escolher o seu caminho. A visita inclui as salas especializadas, o polo tecnológico e as instalações desportivas.',
                    'В средней школе ученики начинают выбирать собственное направление. Экскурсия включает профильные кабинеты, технологический центр и спортивные площадки.',
                    'माध्यमिक वर्षों में विद्यार्थी अपनी दिशा चुनने लगते हैं। भ्रमण में विशेष कक्ष, प्रौद्योगिकी केंद्र और खेल सुविधाएँ शामिल हैं।',
                ),
            ],
            [
                'slug' => 'high-school',
                'name' => 'High School',
                'title' => [
                    'en' => 'High School', 'ar' => 'المرحلة الثانوية', 'fr' => 'Lycée',
                    'es' => 'Escuela secundaria', 'de' => 'Oberstufe', 'it' => 'Scuola superiore',
                    'pt' => 'Ensino secundário', 'ru' => 'Старшая школа', 'hi' => 'उच्चतर विद्यालय',
                ],
                'description' => [
                    'en' => 'The IGCSE and A-Level pathway, Grades 9 to 12.',
                    'ar' => 'مسار الـ IGCSE والمستوى المتقدم، من الصف التاسع إلى الثاني عشر.',
                    'fr' => 'Le parcours IGCSE et A-Level, de la 9e à la 12e année.',
                    'es' => 'El itinerario IGCSE y A-Level, de 9.º a 12.º.',
                    'de' => 'Der IGCSE- und A-Level-Weg, Klassen 9 bis 12.',
                    'it' => 'Il percorso IGCSE e A-Level, dalla classe 9 alla 12.',
                    'pt' => 'O percurso IGCSE e A-Level, do 9.º ao 12.º ano.',
                    'ru' => 'Программа IGCSE и A-Level, 9–12 классы.',
                    'hi' => 'IGCSE और A-Level मार्ग, कक्षा 9 से 12।',
                ],
                'content' => $this->body(
                    'A tour built around the questions families actually ask at this stage: subject choices, examinations, and where our leavers go next. It covers the science laboratories, the sixth form study areas and the careers office.',
                    'جولة مبنية على الأسئلة التي تطرحها الأسر فعليًا في هذه المرحلة: اختيار المواد، والاختبارات، ووجهات خريجينا. تشمل مختبرات العلوم وقاعات الدراسة ومكتب الإرشاد المهني.',
                    'Une visite construite autour des questions que les familles posent vraiment à ce stade : choix des matières, examens et orientation. Elle couvre les laboratoires, les espaces de travail et le bureau d’orientation.',
                    'Una visita centrada en las preguntas que las familias hacen de verdad en esta etapa: asignaturas, exámenes y salidas. Incluye los laboratorios, las salas de estudio y la oficina de orientación.',
                    'Ein Rundgang rund um die Fragen, die Familien in dieser Phase wirklich stellen: Fächerwahl, Prüfungen und Anschlusswege. Er umfasst die Labore, die Lernbereiche und das Berufsberatungsbüro.',
                    'Una visita costruita attorno alle domande che le famiglie pongono davvero in questa fase: scelta delle materie, esami e sbocchi. Comprende i laboratori, le aule studio e l’ufficio orientamento.',
                    'Uma visita construída em torno das perguntas que as famílias fazem nesta fase: escolha de disciplinas, exames e percursos. Inclui os laboratórios, as salas de estudo e o gabinete de orientação.',
                    'Экскурсия строится вокруг вопросов, которые семьи действительно задают на этом этапе: выбор предметов, экзамены и дальнейший путь. Включает лаборатории, учебные зоны и центр профориентации.',
                    'यह भ्रमण उन प्रश्नों पर केंद्रित है जो परिवार इस चरण में वास्तव में पूछते हैं: विषय चयन, परीक्षाएँ और आगे की राह। इसमें प्रयोगशालाएँ, अध्ययन क्षेत्र और करियर कार्यालय शामिल हैं।',
                ),
            ],
        ];
    }

    /**
     * One paragraph of popup body per locale, wrapped so the prose block has
     * something to style.
     *
     * @return array<string, string>
     */
    protected function body(string ...$lines): array
    {
        $codes = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'hi'];

        return array_map(fn (string $line) => '<p>'.$line.'</p>', array_combine($codes, $lines));
    }
}
