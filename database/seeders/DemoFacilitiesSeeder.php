<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilitySlot;
use App\States\Facility\Published;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * DEV FIXTURE. The five venues the school actually rents out, with a term of
 * bookable times.
 *
 * NOT SHIPPED, for the same reason DemoVisitsSeeder is not: the site seeds no
 * content in production, because what a school offers is the school's to say. A
 * facility carries no contract the app depends on — nothing resolves one by a
 * frozen slug, none of them is is_system — so unlike the general job posting it
 * has no reason to exist before an admin makes it.
 *
 * THE BODIES ARE REAL. old/seeders/data/facilities/{slug}{,.ar}.html is 78 KB of
 * finished bilingual copy written for these venues, left behind when the old
 * module was dropped. Reading it is what makes this fixture worth having: a
 * developer opening /facilities sees the actual site rather than lorem ipsum.
 * A missing file degrades to the description, so a checkout without old/ still
 * seeds cleanly.
 *
 * firstOrCreate throughout and deterministic — no rand(), no shuffle — so running
 * it twice changes nothing and two developers see the same calendar.
 */
class DemoFacilitiesSeeder extends Seeder
{
    /** Where the old module's finished copy still lives. */
    protected const BODIES = 'old/seeders/data/facilities';

    /** How far ahead to generate times. */
    protected const WEEKS = 8;

    /** Sunday and Wednesday — a Saudi school week, not a European one. */
    protected const DAYS = [0, 3];

    /** Venues are booked in half-days rather than by the hour. */
    protected const WINDOWS = [[9, 12], [13, 16], [17, 20]];

    public function run(): void
    {
        foreach ($this->facilities() as $order => $definition) {
            $facility = Facility::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'order' => $order,
                    'status' => Published::class,
                    'published_at' => now(),
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'content' => $this->body($definition['slug'], $definition['description']),
                ],
            );

            // Only for a venue this run created: regenerating times over one an
            // admin has been editing would resurrect slots they closed.
            if ($facility->wasRecentlyCreated) {
                $this->slots($facility);
            }
        }
    }

    /**
     * The finished body copy, read off disk, per locale.
     *
     * Only `en` and `ar` were ever written. The other seven fall back to the
     * description rather than being left empty, because a blank `content` renders
     * a venue page with a heading and nothing under it.
     *
     * @param  array<string, string>  $description
     * @return array<string, string>
     */
    protected function body(string $slug, array $description): array
    {
        $content = [];

        foreach (['en' => "{$slug}.html", 'ar' => "{$slug}.ar.html"] as $locale => $file) {
            $path = base_path(self::BODIES."/{$file}");

            if (is_readable($path)) {
                $content[$locale] = file_get_contents($path);
            }
        }

        foreach ($description as $locale => $line) {
            $content[$locale] ??= '<p>'.$line.'</p>';
        }

        return $content;
    }

    /**
     * A term of bookable half-days.
     *
     * insertOrIgnore against the unique index, exactly as the admin bulk generator
     * does — so this and that cannot produce different data for the same
     * description of it.
     */
    protected function slots(Facility $facility): void
    {
        $start = CarbonImmutable::now()->startOfWeek()->startOfDay();
        $now = now();
        $rows = [];

        // The model's own generator, not Str::ulid(): insertOrIgnore bypasses
        // Eloquent, and HasUlids lowercases what the helper returns — so reaching
        // for it here would make these the only uppercase ids in the database.
        $blank = new FacilitySlot;

        for ($week = 0; $week < self::WEEKS; $week++) {
            foreach (self::DAYS as $day) {
                $date = $start->addWeeks($week)->addDays($day);

                foreach (self::WINDOWS as $index => [$from, $to]) {
                    $startsAt = $date->setTime($from, 0);

                    // A fixture half-filled with times already gone is a fixture
                    // nobody can book against.
                    if ($startsAt->isPast()) {
                        continue;
                    }

                    $rows[] = [
                        'id' => $blank->newUniqueId(),
                        'facility_id' => $facility->id,
                        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                        'ends_at' => $date->setTime($to, 0)->format('Y-m-d H:i:s'),
                        // Varied on purpose so all four slot states are reachable by
                        // hand: a 1-booking window goes amber then red after one.
                        'capacity' => $index === 0 ? 1 : ($index === 1 ? 2 : 4),
                        'is_open' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            FacilitySlot::query()->insertOrIgnore($chunk);
        }
    }

    /**
     * The five venues, matching the slugs the old module's copy was written for.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function facilities(): array
    {
        return [
            [
                'slug' => 'al-awael-hall',
                'name' => 'Al Awael Hall',
                'title' => [
                    'en' => 'Al Awael Hall', 'ar' => 'قاعة الأوائل', 'fr' => 'Salle Al Awael',
                    'es' => 'Sala Al Awael', 'de' => 'Al-Awael-Saal', 'it' => 'Sala Al Awael',
                    'pt' => 'Salão Al Awael', 'ru' => 'Зал «Аль-Аваэль»', 'hi' => 'अल अवाइल हॉल',
                ],
                'description' => [
                    'en' => 'The school’s main auditorium, for ceremonies, conferences and performances.',
                    'ar' => 'القاعة الرئيسية للمدرسة، للحفلات والمؤتمرات والعروض.',
                    'fr' => 'Le grand auditorium de l’école, pour les cérémonies, conférences et spectacles.',
                    'es' => 'El auditorio principal del colegio, para ceremonias, conferencias y actuaciones.',
                    'de' => 'Das große Auditorium der Schule für Feiern, Konferenzen und Aufführungen.',
                    'it' => 'L’auditorium principale della scuola, per cerimonie, conferenze e spettacoli.',
                    'pt' => 'O auditório principal da escola, para cerimónias, conferências e espetáculos.',
                    'ru' => 'Главный зал школы для церемоний, конференций и выступлений.',
                    'hi' => 'विद्यालय का मुख्य सभागार — समारोहों, सम्मेलनों और प्रस्तुतियों के लिए।',
                ],
            ],
            [
                'slug' => 'the-hive',
                'name' => 'The Hive',
                'title' => [
                    'en' => 'The Hive', 'ar' => 'الخلية', 'fr' => 'The Hive', 'es' => 'The Hive',
                    'de' => 'The Hive', 'it' => 'The Hive', 'pt' => 'The Hive',
                    'ru' => 'The Hive', 'hi' => 'द हाइव',
                ],
                'description' => [
                    'en' => 'A flexible collaboration space for workshops, meetings and group work.',
                    'ar' => 'مساحة عمل مرنة لورش العمل والاجتماعات والعمل الجماعي.',
                    'fr' => 'Un espace de collaboration modulable pour ateliers, réunions et travail de groupe.',
                    'es' => 'Un espacio de colaboración flexible para talleres, reuniones y trabajo en grupo.',
                    'de' => 'Ein flexibler Kollaborationsraum für Workshops, Besprechungen und Gruppenarbeit.',
                    'it' => 'Uno spazio collaborativo flessibile per laboratori, riunioni e lavori di gruppo.',
                    'pt' => 'Um espaço de colaboração flexível para oficinas, reuniões e trabalho de grupo.',
                    'ru' => 'Гибкое пространство для мастер-классов, встреч и групповой работы.',
                    'hi' => 'कार्यशालाओं, बैठकों और सामूहिक कार्य के लिए एक लचीला सहयोग स्थल।',
                ],
            ],
            [
                'slug' => 'the-canvas',
                'name' => 'The Canvas',
                'title' => [
                    'en' => 'The Canvas', 'ar' => 'الكانفس', 'fr' => 'The Canvas', 'es' => 'The Canvas',
                    'de' => 'The Canvas', 'it' => 'The Canvas', 'pt' => 'The Canvas',
                    'ru' => 'The Canvas', 'hi' => 'द कैनवास',
                ],
                'description' => [
                    'en' => 'A studio for art, design and exhibitions.',
                    'ar' => 'استوديو للفنون والتصميم والمعارض.',
                    'fr' => 'Un studio dédié à l’art, au design et aux expositions.',
                    'es' => 'Un estudio para arte, diseño y exposiciones.',
                    'de' => 'Ein Atelier für Kunst, Design und Ausstellungen.',
                    'it' => 'Uno studio per arte, design ed esposizioni.',
                    'pt' => 'Um estúdio para arte, design e exposições.',
                    'ru' => 'Студия для искусства, дизайна и выставок.',
                    'hi' => 'कला, डिज़ाइन और प्रदर्शनियों के लिए एक स्टूडियो।',
                ],
            ],
            [
                'slug' => 'innovation-labs',
                'name' => 'Academic Innovation Labs',
                'title' => [
                    'en' => 'Academic Innovation Labs', 'ar' => 'مختبرات الابتكار الأكاديمي',
                    'fr' => 'Laboratoires d’innovation', 'es' => 'Laboratorios de innovación',
                    'de' => 'Innovationslabore', 'it' => 'Laboratori di innovazione',
                    'pt' => 'Laboratórios de inovação', 'ru' => 'Лаборатории инноваций',
                    'hi' => 'शैक्षणिक नवाचार प्रयोगशालाएँ',
                ],
                'description' => [
                    'en' => 'Science, robotics and maker labs for hands-on learning.',
                    'ar' => 'مختبرات للعلوم والروبوتات والابتكار للتعلم التطبيقي.',
                    'fr' => 'Laboratoires de sciences, de robotique et de fabrication pour un apprentissage pratique.',
                    'es' => 'Laboratorios de ciencia, robótica y creación para el aprendizaje práctico.',
                    'de' => 'Labore für Naturwissenschaften, Robotik und Making für praktisches Lernen.',
                    'it' => 'Laboratori di scienze, robotica e making per un apprendimento pratico.',
                    'pt' => 'Laboratórios de ciência, robótica e criação para aprendizagem prática.',
                    'ru' => 'Лаборатории науки, робототехники и творчества для практического обучения.',
                    'hi' => 'व्यावहारिक शिक्षा के लिए विज्ञान, रोबोटिक्स और मेकर प्रयोगशालाएँ।',
                ],
            ],
            [
                'slug' => 'sports-complex',
                'name' => 'Sports & Wellness Complex',
                'title' => [
                    'en' => 'Sports & Wellness Complex', 'ar' => 'المجمع الرياضي والصحي',
                    'fr' => 'Complexe sportif et bien-être', 'es' => 'Complejo deportivo y de bienestar',
                    'de' => 'Sport- und Wellnesskomplex', 'it' => 'Complesso sportivo e benessere',
                    'pt' => 'Complexo desportivo e de bem-estar', 'ru' => 'Спортивно-оздоровительный комплекс',
                    'hi' => 'खेल एवं स्वास्थ्य परिसर',
                ],
                'description' => [
                    'en' => 'Courts, a pool and a gymnasium, available for clubs and events.',
                    'ar' => 'ملاعب ومسبح وصالة رياضية، متاحة للأندية والفعاليات.',
                    'fr' => 'Terrains, piscine et gymnase, disponibles pour clubs et événements.',
                    'es' => 'Pistas, piscina y gimnasio, disponibles para clubes y eventos.',
                    'de' => 'Plätze, ein Schwimmbad und eine Sporthalle, verfügbar für Vereine und Veranstaltungen.',
                    'it' => 'Campi, piscina e palestra, disponibili per club ed eventi.',
                    'pt' => 'Campos, piscina e ginásio, disponíveis para clubes e eventos.',
                    'ru' => 'Площадки, бассейн и спортзал — доступны для клубов и мероприятий.',
                    'hi' => 'कोर्ट, एक पूल और व्यायामशाला — क्लबों और आयोजनों के लिए उपलब्ध।',
                ],
            ],
        ];
    }
}
