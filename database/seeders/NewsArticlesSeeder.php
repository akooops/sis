<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\File;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsArticlesSeeder extends Seeder
{
    public function run()
    {
        $articles = [
            [
                'name' => 'Mathematics Competition 2025-2026 Winners',
                'slug' => 'mathematics-competition-2025-2026',
                'main' => 'منافسات الرياضيات-01.jpg',
                'gallery' => ['منافسة الرياضيات-01.jpg', 'منافسات الرياضيات-02.jpg', 'منافسات الرياضيات-03.jpg', 'منافسات الرياضيات-04.jpg', 'منافسات الرياضيات-05.jpg', 'منافسات الرياضيات-06.jpg', 'منافسة الرياضيات-03.jpg', 'منافسة الرياضيات-04.jpg'],
                'en' => [
                    'title' => 'Mathematics Competition 2025-2026 Winners',
                    'description' => 'Congratulations to the winners of the SIS Mathematics Competition for the academic year 2025-2026.',
                    'content' => '<p>Congratulations to the winners of the Saud International School Mathematics Competition for the academic year 2025-2026.</p><p><strong>First Place — Grade 4 Math Competition:</strong> Karim Saied Ibrahim (Grade 4A), awarded a Certificate of Achievement for securing first place in the Grade 4 Math Competition, Academic Year 2025-2026.</p><p><strong>Second Place — Grade 5 Math Competition:</strong> Ibrahim Omer Mekki Burai (Grade 5D), awarded a Certificate of Achievement for winning second place in the Grade 5 Math Competition, AY 2025-2026.</p>',
                ],
                'ar' => [
                    'title' => 'الفائزون في منافسة الرياضيات 2025-2026',
                    'description' => 'تهانينا للفائزين في منافسة الرياضيات بمدارس سعود العالمية للعام الدراسي 2025-2026.',
                    'content' => '<p>تهانينا للفائزين في منافسة الرياضيات بمدارس سعود العالمية للعام الدراسي 2025-2026.</p><p><strong>المركز الأول — منافسة الرياضيات للصف الرابع:</strong> كريم سعيد إبراهيم (الصف 4A)، حصل على شهادة إنجاز لتحقيقه المركز الأول في منافسة الرياضيات للصف الرابع للعام الدراسي 2025-2026.</p><p><strong>المركز الثاني — منافسة الرياضيات للصف الخامس:</strong> إبراهيم عمر مكي براي (الصف 5D)، حصل على شهادة إنجاز لفوزه بالمركز الثاني في منافسة الرياضيات للصف الخامس للعام الدراسي 2025-2026.</p>',
                ],
            ],
            [
                'name' => 'FIFA 26 Championship Winners',
                'slug' => 'fifa-26-championship-winners',
                'main' => 'فيفا-03.jpg',
                'gallery' => ['فيفا-01.jpg', 'فيفا-04.jpg'],
                'en' => [
                    'title' => 'FIFA 26 Championship Winners',
                    'description' => 'Congratulations to the first and second place winners of the FIFA 26 Championship.',
                    'content' => '<p>Congratulations to the winners of the FIFA 26 Championship at Saud International School.</p><p><strong>1st Place Winner</strong> — awarded a PlayStation 5 with EA FC 26.</p><p><strong>2nd Place Winner</strong> — awarded an iPad.</p><p>Congratulations on your achievement, we are proud of you!</p>',
                ],
                'ar' => [
                    'title' => 'الفائزون في بطولة فيفا 26',
                    'description' => 'تهانينا للفائزين بالمركزين الأول والثاني في بطولة فيفا 26.',
                    'content' => '<p>تهانينا للفائزين في بطولة فيفا 26 بمدارس سعود العالمية.</p><p><strong>الفائز بالمركز الأول</strong> — حصل على جهاز بلايستيشن 5 مع لعبة EA FC 26.</p><p><strong>الفائز بالمركز الثاني</strong> — حصل على جهاز آيباد.</p><p>مبارك إنجازكم، نحن فخورون بكم!</p>',
                ],
            ],
            [
                'name' => 'Happy New Year 2026',
                'slug' => 'happy-new-year-2026',
                'main' => 'NewYear-01.jpg',
                'gallery' => ['new year-01.jpg', 'new year-02.jpg'],
                'en' => [
                    'title' => 'Happy New Year 2026',
                    'description' => 'Our dear students! We wish you a year full of success, learning, and happiness.',
                    'content' => '<p>Happy New Year 2026!</p><p>Our dear students! We wish you a year full of success, learning, and happiness.</p>',
                ],
                'ar' => [
                    'title' => 'عام جديد سعيد 2026',
                    'description' => 'طلابنا الأعزاء! نتمنى لكم عاماً مليئاً بالنجاح والتعلم والسعادة.',
                    'content' => '<p>عام جديد سعيد 2026!</p><p>طلابنا الأعزاء! نتمنى لكم عاماً مليئاً بالنجاح والتعلم والسعادة.</p>',
                ],
            ],
            [
                'name' => 'Achievements of 2025',
                'slug' => 'achievements-of-2025',
                'main' => 'انجازات الطلاب-01.jpg',
                'gallery' => [],
                'en' => [
                    'title' => 'Achievements of 2025',
                    'description' => 'Celebrating our students\' outstanding achievements of 2025.',
                    'content' => '<p>Celebrating our students\' outstanding achievements of 2025:</p><ul><li><strong>Maryam Seif</strong> — Second Place in the Saudi Swimming Federation Competition.</li><li><strong>Hamza Islam</strong> — First Place Worldwide, Python Programming Hackathon.</li><li><strong>Vihangi Chamaya Herath</strong> — First Place in Central Province, Outstanding Cambridge Learner Awards.</li><li><strong>Sufyan Zubair</strong> — First Place, Spelling Bee Competition.</li><li><strong>Naviya Kumari</strong> — Top in World, English as a Second Language.</li><li><strong>Mahmood Saleem</strong> — The Gold Medal, The Educational Administration level.</li></ul>',
                ],
                'ar' => [
                    'title' => 'إنجازات عام 2025',
                    'description' => 'نحتفي بإنجازات طلابنا المتميزة لعام 2025.',
                    'content' => '<p>نحتفي بإنجازات طلابنا المتميزة لعام 2025:</p><ul><li><strong>مريم سيف</strong> — المركز الثاني في منافسة الاتحاد السعودي للسباحة.</li><li><strong>حمزة إسلام</strong> — المركز الأول عالمياً في هاكاثون البرمجة بلغة بايثون.</li><li><strong>فيهانجي تشامايا هيراث</strong> — المركز الأول في المنطقة الوسطى، جوائز كامبريدج للمتعلمين المتميزين.</li><li><strong>سفيان زبير</strong> — المركز الأول في مسابقة التهجئة Spelling Bee.</li><li><strong>نافيا كوماري</strong> — الأولى على العالم في اللغة الإنجليزية كلغة ثانية.</li><li><strong>محمود سليم</strong> — الميدالية الذهبية على مستوى الإدارة التعليمية.</li></ul>',
                ],
            ],
            [
                'name' => 'Vihangi Chamaya Herath — First Place in Central Province',
                'slug' => 'vihangi-herath-outstanding-cambridge-learner',
                'main' => 'تكريم الطالبة-تعديل-01.jpg',
                'gallery' => ['مريم سيف-01.jpg'],
                'en' => [
                    'title' => 'Vihangi Chamaya Herath — First Place in Central Province',
                    'description' => 'First Place in Central Province, June 2025 — Outstanding Cambridge Learner Awards.',
                    'content' => '<p>Congratulations to our student <strong>Vihangi Chamaya Herath</strong> for achieving <strong>First Place in Central Province</strong> (June 2025) in the <strong>Outstanding Cambridge Learner Awards</strong>.</p>',
                ],
                'ar' => [
                    'title' => 'فيهانجي تشامايا هيراث — المركز الأول في المنطقة الوسطى',
                    'description' => 'المركز الأول في المنطقة الوسطى، يونيو 2025 — جوائز كامبريدج للمتعلمين المتميزين.',
                    'content' => '<p>تهانينا لطالبتنا <strong>فيهانجي تشامايا هيراث</strong> لتحقيقها <strong>المركز الأول في المنطقة الوسطى</strong> (يونيو 2025) ضمن <strong>جوائز كامبريدج للمتعلمين المتميزين</strong>.</p>',
                ],
            ],
            [
                'name' => 'Arabic Language Day',
                'slug' => 'arabic-language-day',
                'main' => 'يوم اللغة العربية-01.jpg',
                'gallery' => [],
                'en' => [
                    'title' => 'Arabic Language Day',
                    'description' => 'Saud International School celebrates Arabic Language Day.',
                    'content' => '<p>Saud International School celebrates <strong>Arabic Language Day</strong> — يوم اللغة العربية.</p>',
                ],
                'ar' => [
                    'title' => 'يوم اللغة العربية',
                    'description' => 'مدارس سعود العالمية تحتفي بيوم اللغة العربية.',
                    'content' => '<p>مدارس سعود العالمية تحتفي بـ<strong>يوم اللغة العربية</strong>.</p>',
                ],
            ],
            [
                'name' => 'Saud International School Joins SYC League',
                'slug' => 'sis-joins-syc-league',
                'main' => 'بطولة الشباب.jpg',
                'gallery' => ['Saud League-updated-02.jpg', 'تعديل كرة قدم ٣٠٠٠-01.jpg'],
                'en' => [
                    'title' => 'Saud International School Joins SYC League',
                    'description' => 'SIS joins the SYC League (Saudi Youth Champions) — don\'t just watch, be in the game!',
                    'content' => '<p><strong>Saud International School joins SYC League</strong> (Saudi Youth Champions).</p><p>SYC League is your game, your stage, your chance to be a champion. It\'s football; but with new twists, fun rules, and school spirit turned into a show.</p><p>Small Teams • Big Energy • Fast Matches • Game-Changer Rules</p><p><strong>Don\'t just watch! Be in the game! Create your team now.</strong></p>',
                ],
                'ar' => [
                    'title' => 'مدارس سعود العالمية تنضم إلى دوري SYC',
                    'description' => 'مدارس سعود العالمية تنضم إلى دوري أبطال الشباب السعودي SYC — لا تكتفِ بالمشاهدة، كن في قلب اللعبة!',
                    'content' => '<p><strong>مدارس سعود العالمية تنضم إلى دوري SYC</strong> (أبطال الشباب السعودي).</p><p>دوري SYC هو لعبتك ومسرحك وفرصتك لتكون بطلاً. إنها كرة القدم؛ لكن بلمسات جديدة وقواعد ممتعة وروح مدرسية تتحول إلى عرض مبهر.</p><p>فرق صغيرة • طاقة كبيرة • مباريات سريعة • قواعد تغيّر اللعبة</p><p><strong>لا تكتفِ بالمشاهدة! كن في قلب اللعبة! أنشئ فريقك الآن.</strong></p>',
                ],
            ],
            [
                'name' => 'Academic Year 2026-2027 — Two International Pathways',
                'slug' => 'academic-year-2026-2027-pathways',
                'main' => 'Summer camp - post1_Artboard 1 copy 3.jpg',
                'gallery' => ['Summer camp - post1_Artboard 1 copy 4.jpg', 'Summer camp - post1_Artboard 1 copy 5.jpg'],
                'en' => [
                    'title' => 'Two International Pathways. One School.',
                    'description' => 'Academic Year 2026-2027: expanded academic pathways — American Stream and British Stream, from Early Years to Graduation.',
                    'content' => '<p><strong>Two International Pathways. One School.</strong></p><p>Academic Year 2026-2027 — Expanded Academic Pathways:</p><ul><li>American Stream</li><li>British Stream</li></ul><p>From Early Years to Graduation — Internationally Aligned Education.</p><p>sis.edu.sa — 920002877</p>',
                ],
                'ar' => [
                    'title' => 'مساران عالميان. مدرسة واحدة.',
                    'description' => 'العام الدراسي 2026-2027: مسارات أكاديمية موسعة — المسار الأمريكي والمسار البريطاني، من السنوات المبكرة حتى التخرج.',
                    'content' => '<p><strong>مساران عالميان. مدرسة واحدة.</strong></p><p>العام الدراسي 2026-2027 — مسارات أكاديمية موسعة:</p><ul><li>المسار الأمريكي</li><li>المسار البريطاني</li></ul><p>من السنوات المبكرة حتى التخرج — تعليم متوافق مع المعايير العالمية.</p><p>sis.edu.sa — 920002877</p>',
                ],
            ],
            [
                'name' => 'Winter Season Notice to Parents',
                'slug' => 'winter-season-notice',
                'main' => 'الشتاء-01-01.jpg',
                'gallery' => [],
                'en' => [
                    'title' => 'Winter Season Notice to Parents',
                    'description' => 'A kind reminder to parents about students\' winter clothing.',
                    'content' => '<p><strong>Dear parents,</strong></p><p>In order to ensure the safety of our sons and daughters during the winter season, we kindly ask you to pay attention to their winter clothing and provide the necessary protection for them.</p>',
                ],
                'ar' => [
                    'title' => 'تنبيه فصل الشتاء لأولياء الأمور',
                    'description' => 'تذكير لأولياء الأمور بالاهتمام بالملابس الشتوية للطلاب.',
                    'content' => '<p><strong>أولياء الأمور الكرام،</strong></p><p>حرصاً على سلامة أبنائنا وبناتنا خلال فصل الشتاء، نرجو منكم الاهتمام بملابسهم الشتوية وتوفير الحماية اللازمة لهم.</p>',
                ],
            ],
            [
                'name' => 'Authorised IELTS Test Centre Agreement with the British Council',
                'slug' => 'ielts-test-centre-agreement',
                'main' => 'اتفاقية.jpg',
                'gallery' => ['agreement_بوست انستقرام.jpg', 'اتفاقية - الاوائل .jpg'],
                'en' => [
                    'title' => 'SIS and the British Council Sign IELTS Test Centre Agreement',
                    'description' => 'Saud International School and the British Council have officially signed the agreement to establish an Authorised IELTS Test Centre.',
                    'content' => '<p><strong>Saud International School and the British Council</strong> have officially signed the agreement to establish an <strong>Authorised IELTS Test Centre</strong>.</p>',
                ],
                'ar' => [
                    'title' => 'توقيع اتفاقية مركز IELTS المعتمد مع المجلس الثقافي البريطاني',
                    'description' => 'وقّعت مدارس سعود العالمية والمجلس الثقافي البريطاني رسمياً اتفاقية إنشاء مركز معتمد لاختبار IELTS.',
                    'content' => '<p>وقّعت <strong>مدارس سعود العالمية والمجلس الثقافي البريطاني</strong> رسمياً اتفاقية إنشاء <strong>مركز معتمد لاختبار IELTS</strong>.</p>',
                ],
            ],
            [
                'name' => 'Exams — An Opportunity to Shine',
                'slug' => 'exams-opportunity-to-shine',
                'main' => 'الاختبارات-بشعار المدرسة-01.jpg',
                'gallery' => [],
                'en' => [
                    'title' => 'Exams — An Opportunity to Shine',
                    'description' => 'Exams are a real opportunity to demonstrate what we have learned throughout the year.',
                    'content' => '<p><strong>Exams are a real opportunity to demonstrate what we have learned throughout the year.</strong></p><p>Be confident, manage your time well, and stay focused in your performance; diligence is the path to success, and commitment is the foundation of excellence.</p>',
                ],
                'ar' => [
                    'title' => 'الاختبارات — فرصة للتألق',
                    'description' => 'الاختبارات فرصة حقيقية لإظهار ما تعلمناه على مدار العام.',
                    'content' => '<p><strong>الاختبارات فرصة حقيقية لإظهار ما تعلمناه على مدار العام.</strong></p><p>كن واثقاً، وأحسن إدارة وقتك، وحافظ على تركيزك في أدائك؛ فالاجتهاد طريق النجاح، والالتزام أساس التميز.</p>',
                ],
            ],
            [
                'name' => 'Meet Us at The International School Expo',
                'slug' => 'international-school-expo-2026',
                'main' => 'معرض المدارس2.jpg',
                'gallery' => [],
                'en' => [
                    'title' => 'Meet Us at The International School Expo',
                    'description' => 'Join us and meet our team at The International School Expo — Booth S6, The Arena Riyadh, 19-21 May 2026.',
                    'content' => '<p><strong>Join us and meet our team at The International School Expo!</strong></p><ul><li>Booth: <strong>S6</strong></li><li>Time: from 4:00 pm to 10:00 pm</li><li>Location: The Arena, Riyadh</li><li>Date: 19-21 May 2026</li></ul>',
                ],
                'ar' => [
                    'title' => 'التقوا بنا في معرض المدارس الدولي',
                    'description' => 'انضموا إلينا والتقوا بفريقنا في معرض المدارس الدولي — الجناح S6، ذا أرينا الرياض، 19-21 مايو 2026.',
                    'content' => '<p><strong>انضموا إلينا والتقوا بفريقنا في معرض المدارس الدولي!</strong></p><ul><li>الجناح: <strong>S6</strong></li><li>الوقت: من 4:00 عصراً حتى 10:00 مساءً</li><li>المكان: ذا أرينا، الرياض</li><li>التاريخ: 19-21 مايو 2026</li></ul>',
                ],
            ],
        ];

        $en = Language::where('code', 'en')->first();
        $ar = Language::where('code', 'ar')->first();

        foreach ($articles as $data) {
            $article = Article::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'status' => 'published',
                    'facility_id' => null,
                ]
            );

            foreach (['en' => $en, 'ar' => $ar] as $code => $language) {
                if (! $language) {
                    continue;
                }

                foreach ($data[$code] as $field => $value) {
                    $article->setTranslation($field, $language->code, $value);
                }
            }

            // Thumbnail (is_main) copied from content/news
            if (! $article->file()->exists()) {
                $this->importImage($article, $data['main'], true);
            }
        }
    }

    /**
     * Copy a news image from content/news into the public uploads disk and
     * attach it to the article (skipped when the source file is missing).
     */
    protected function importImage(Article $article, string $fileName, bool $isMain)
    {
        $sourcePath = base_path('content/news/' . $fileName);

        if (! file_exists($sourcePath)) {
            return;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $newName = Str::uuid() . '.' . $extension;
        $filePath = 'uploads/' . $newName;

        Storage::disk('public')->put($filePath, file_get_contents($sourcePath));

        File::create([
            'original_name' => $fileName,
            'name' => $newName,
            'path' => $filePath,
            'size' => filesize($sourcePath),
            'type' => 'image/jpeg',
            'is_main' => $isMain ? 1 : 0,
            'model_type' => 'App\\Models\\Article',
            'model_id' => $article->id,
        ]);
    }
}
