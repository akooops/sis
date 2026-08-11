<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\File;
use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandsSeeder extends Seeder
{
    public function run()
    {
        $en = Language::where('code', 'en')->first();
        $ar = Language::where('code', 'ar')->first();

        $brands = [
            [
                'name' => 'Visual Identity',
                'slug' => 'visual-identity',
                'order' => 1,
                'en' => [
                    'title' => 'Visual Identity',
                    'tagline' => 'Our logos, fonts, colors and the rules that keep them consistent',
                    'description' => 'An organized library of the official SIS logos, fonts and the brand guideline booklet, together with the rules for correct logo usage: components, safe spaces, colors, suitable backgrounds and what to avoid.',
                    'content' => <<<'HTML'
<p>The visual identity is how Saud International Schools is recognized at a glance. This page gathers the official visual assets — the logo library, the approved fonts and the brand guideline booklet — and summarizes the core rules for using them correctly.</p>
<h3>Logo Components</h3>
<p>The logo combines the SIS emblem and the school wordmark in Arabic and English. Always use the complete, approved artwork from the library below — never rebuild, redraw or retype any part of it.</p>
<h3>Correct Usage</h3>
<p>Use the logo files exactly as provided, in their original proportions and colors. Choose the version (full logo, emblem, horizontal) that fits the layout, and always start from the source files in the library.</p>
<h3>Safe Space</h3>
<p>Keep a clear area around the logo — free of text, images and other graphics — at least equal to the height of the emblem, so the logo always has room to breathe.</p>
<h3>Colors</h3>
<p>Use only the official brand colors defined in the guideline booklet. Do not recolor the logo or apply gradients, shadows or effects that are not part of the approved artwork.</p>
<h3>Backgrounds</h3>
<p>Place the logo on clean, uncluttered backgrounds with sufficient contrast. Use the light version on dark backgrounds and the dark version on light backgrounds, as shown in the guideline.</p>
<h3>What to Avoid</h3>
<p>Do not stretch, rotate, crop or outline the logo; do not change its colors or fonts; do not place it over busy imagery or low-contrast backgrounds; and do not combine it with other marks without approval.</p>
<h3>Official Fonts</h3>
<p>Official communications use <strong>DIN Next</strong> for Arabic and <strong>Mulish</strong> for English. Both font packages are available for download below.</p>
<p><em>For the complete rules, please refer to the identity guideline booklet in the library below.</em></p>
HTML,
                ],
                'ar' => [
                    'title' => 'الهوية المرئية',
                    'tagline' => 'شعاراتنا وخطوطنا وألواننا والقواعد التي تحفظ اتساقها',
                    'description' => 'مكتبة منظمة لشعارات مدارس سعود العالمية الرسمية والخطوط المعتمدة وكتيب الهوية الإرشادي، مع قواعد الاستخدام الصحيح للشعار: مكوناته، والمساحات الآمنة، والألوان، والخلفيات المناسبة، وما يجب تجنبه.',
                    'content' => <<<'HTML'
<p>الهوية المرئية هي ما يجعل مدارس سعود العالمية مميزة من النظرة الأولى. تجمع هذه الصفحة الأصول المرئية الرسمية — مكتبة الشعارات، والخطوط المعتمدة، وكتيب الهوية الإرشادي — وتلخص أهم قواعد استخدامها بشكل صحيح.</p>
<h3>مكونات الشعار</h3>
<p>يتكون الشعار من رمز المدارس والاسم الكتابي بالعربية والإنجليزية. استخدم دائماً الملفات الكاملة المعتمدة من المكتبة أدناه، ولا تُعد بناء أو رسم أو كتابة أي جزء منه.</p>
<h3>الاستخدامات الصحيحة</h3>
<p>استخدم ملفات الشعار كما هي، بنسبها وألوانها الأصلية. اختر النسخة المناسبة للتصميم (الشعار الكامل، الرمز، النسخة الأفقية) وابدأ دائماً من الملفات المصدرية في المكتبة.</p>
<h3>المساحات الآمنة</h3>
<p>اترك مساحة فارغة حول الشعار — خالية من النصوص والصور والعناصر الأخرى — لا تقل عن ارتفاع الرمز، ليحافظ الشعار على وضوحه وحضوره.</p>
<h3>الألوان</h3>
<p>استخدم الألوان الرسمية المعتمدة في كتيب الهوية فقط، ولا تغيّر ألوان الشعار أو تضف تدرجات أو ظلالاً أو مؤثرات غير معتمدة.</p>
<h3>الخلفيات المناسبة</h3>
<p>ضع الشعار على خلفيات نظيفة وغير مزدحمة وبتباين كافٍ. استخدم النسخة الفاتحة على الخلفيات الداكنة والنسخة الداكنة على الخلفيات الفاتحة كما هو موضح في الكتيب.</p>
<h3>ما يجب تجنبه</h3>
<p>لا تقم بتمديد الشعار أو تدويره أو اقتصاصه أو تحديده؛ ولا تغيّر ألوانه أو خطوطه؛ ولا تضعه فوق صور مزدحمة أو خلفيات منخفضة التباين؛ ولا تدمجه مع علامات أخرى دون اعتماد.</p>
<h3>الخطوط الرسمية</h3>
<p>تُستخدم في الاتصالات الرسمية خطوط <strong>DIN Next</strong> للعربية و<strong>Mulish</strong> للإنجليزية، وكلاهما متاح للتحميل أدناه.</p>
<p><em>للاطلاع على القواعد الكاملة، يرجى الرجوع إلى كتيب الهوية الإرشادي في المكتبة أدناه.</em></p>
HTML,
                ],
            ],
            [
                'name' => 'Audio Identity',
                'slug' => 'audio-identity',
                'order' => 2,
                'en' => [
                    'title' => 'Audio Identity',
                    'tagline' => 'The official approved sounds and music of SIS',
                    'description' => 'The library of approved official sounds and music tracks. These tracks are the only audio approved for SIS videos, events, podcasts and productions.',
                    'content' => <<<'HTML'
<p>The audio identity gives Saud International Schools a recognizable sound. The library below contains the approved official tracks — the identity track, its variations and shorter cuts — for use in school videos, event openings, podcasts and other productions.</p>
<p>Always use the tracks as provided. Do not remix, re-record or layer them with other music without approval.</p>
HTML,
                ],
                'ar' => [
                    'title' => 'الهوية الصوتية',
                    'tagline' => 'الأصوات والموسيقى الرسمية المعتمدة لمدارس سعود العالمية',
                    'description' => 'مكتبة الأصوات والموسيقى الرسمية المعتمدة. هذه المقاطع هي الوحيدة المعتمدة للاستخدام في فيديوهات المدارس وفعالياتها وبودكاستها وإنتاجاتها.',
                    'content' => <<<'HTML'
<p>تمنح الهوية الصوتية مدارس سعود العالمية صوتاً مميزاً يسهل تذكره. تضم المكتبة أدناه المقاطع الرسمية المعتمدة — المقطع التعريفي للهوية ونسخه المختلفة والمقاطع القصيرة — للاستخدام في فيديوهات المدارس وافتتاحيات الفعاليات والبودكاست وسائر الإنتاجات.</p>
<p>استخدم المقاطع كما هي دائماً، ولا تقم بإعادة مزجها أو تسجيلها أو دمجها مع موسيقى أخرى دون اعتماد.</p>
HTML,
                ],
            ],
            [
                'name' => 'Scent Identity',
                'slug' => 'scent-identity',
                'order' => 3,
                'en' => [
                    'title' => 'Scent Identity',
                    'tagline' => 'The signature scent of SIS spaces',
                    'description' => 'The scent identity extends the SIS brand to the senses: a signature essential-oil scent used across our campuses, with its official labels and imagery.',
                    'content' => <<<'HTML'
<p>The scent identity extends the Saud International Schools brand beyond what you see and hear: a signature essential-oil scent welcomes students, parents and guests across our campuses. Below you will find the official scent labels and imagery.</p>
HTML,
                ],
                'ar' => [
                    'title' => 'الهوية العطرية',
                    'tagline' => 'العطر المميز لمساحات مدارس سعود العالمية',
                    'description' => 'تمتد الهوية العطرية بعلامة المدارس إلى الحواس: عطر مميز من الزيوت العطرية يُستخدم في جميع مرافقنا، مع ملصقاته وصوره الرسمية.',
                    'content' => <<<'HTML'
<p>تمتد الهوية العطرية بعلامة مدارس سعود العالمية إلى ما هو أبعد مما تراه وتسمعه: عطر مميز من الزيوت العطرية يستقبل الطلاب وأولياء الأمور والضيوف في جميع مرافقنا. تجدون أدناه الملصقات والصور الرسمية للهوية العطرية.</p>
HTML,
                ],
            ],
        ];

        foreach ($brands as $data) {
            $brand = Brand::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'status' => 'published',
                    'order' => $data['order'],
                ]
            );

            foreach (['en' => $en, 'ar' => $ar] as $code => $language) {
                if (! $language) {
                    continue;
                }

                foreach ($data[$code] as $field => $value) {
                    $brand->setTranslation($field, $language->code, $value);
                }
            }
        }

        // ------------------------------------------------------------------
        // Asset import from the client's content/ folder
        // ------------------------------------------------------------------
        $visual = Brand::where('slug', 'visual-identity')->first();
        $audio = Brand::where('slug', 'audio-identity')->first();
        $scent = Brand::where('slug', 'scent-identity')->first();

        $contentRoot = base_path('content');

        $this->importAsset($visual, $contentRoot . '/vis-brand/SIS Identity Guiedline (1).pdf', 'SIS Identity Guideline', 'guidelines');
        $this->importAsset($visual, $contentRoot . '/vis-brand/DIN NEXT-ARABIC FONT.zip', 'DIN Next — Arabic Font', 'fonts');
        $this->importAsset($visual, $contentRoot . '/vis-brand/Mulish-ENGLISH FONT.zip', 'Mulish — English Font', 'fonts');

        $audioTracks = [
            'Saud identity track.mp3' => 'Saud Identity Track',
            'Saud track 1.mp3' => 'Saud Track 1',
            'Saud track 2.mp3' => 'Saud Track 2',
            'Saud track 3.mp3' => 'Saud Track 3',
            'Saud track 4.mp3' => 'Saud Track 4',
            'Saud track 5.mp3' => 'Saud Track 5',
            'Saud track 6.mp3' => 'Saud Track 6',
            'Saud track 7.mp3' => 'Saud Track 7',
            'Saud track mini 1.mp3' => 'Saud Track Mini 1',
            'Saud track mini 2.mp3' => 'Saud Track Mini 2',
            'Saud track mini 3.mp3' => 'Saud Track Mini 3',
            'Saud track national day.mp3' => 'Saud Track — National Day',
            'Copy of SIS - Full Track - 03.mp3' => 'SIS Full Track 03',
            'Copy of 1 - Occi.mp3' => 'Occi',
            '2 - Saxo.mp3' => 'Saxo',
            '3 - Asia.mp3' => 'Asia',
            '4 - Afro.mp3' => 'Afro',
            'Document from F.mp3' => 'SIS Track — Extra',
        ];

        foreach ($audioTracks as $fileName => $displayName) {
            $this->importAsset($audio, $contentRoot . '/audio-brand/' . $fileName, $displayName, 'audio');
        }

        $this->importAsset($scent, $contentRoot . '/smelling-brand/essential oil stickers-01.jpg.jpeg', 'Essential Oil Sticker 01', 'images');
        $this->importAsset($scent, $contentRoot . '/smelling-brand/essential oil stickers-02.jpg.jpeg', 'Essential Oil Sticker 02', 'images');
        $this->importAsset($scent, $contentRoot . '/smelling-brand/essential oil stickers_Artboard 1 copy 2.jpg.jpeg', 'Essential Oil Sticker 03', 'images');

        // ------------------------------------------------------------------
        // Identity landing page
        // ------------------------------------------------------------------
        $identityPage = Page::updateOrCreate(
            ['slug' => 'identity'],
            [
                'name' => 'Identity',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ]
        );

        $pageTranslations = [
            'en' => [
                'title' => 'Our Identity',
                'description' => 'The Saud International Schools brand: our visual identity, audio identity and scent identity, with their official asset libraries.',
                'content' => '<p>The SIS brand goes beyond a logo. Explore each side of our identity below — every one has its own page with a description and an organized library of official, downloadable assets.</p>',
            ],
            'ar' => [
                'title' => 'هويتنا',
                'description' => 'هوية مدارس سعود العالمية: الهوية المرئية والهوية الصوتية والهوية العطرية، مع مكتبات أصولها الرسمية.',
                'content' => '<p>هوية مدارس سعود العالمية أكثر من مجرد شعار. استكشف كل جانب من جوانب هويتنا أدناه — لكل منها صفحة خاصة تتضمن وصفاً ومكتبة منظمة من الأصول الرسمية القابلة للتحميل.</p>',
            ],
        ];

        foreach ($pageTranslations as $code => $fields) {
            foreach ($fields as $field => $value) {
                $identityPage->setTranslation($field, $code, $value);
            }
        }

        // ------------------------------------------------------------------
        // UI language keys
        // ------------------------------------------------------------------
        $keys = [
            'breadcrumbs_identity_page_title' => [
                'en' => 'Our Identity',
                'ar' => 'هويتنا',
            ],
            'identity_page_view_button' => [
                'en' => 'Explore',
                'ar' => 'استكشف',
            ],
            'brand_group_logos' => [
                'en' => 'Logos',
                'ar' => 'الشعارات',
            ],
            'brand_group_colors' => [
                'en' => 'Colors',
                'ar' => 'الألوان',
            ],
            'brand_group_fonts' => [
                'en' => 'Official Fonts',
                'ar' => 'الخطوط الرسمية',
            ],
            'brand_group_guidelines' => [
                'en' => 'Guidelines',
                'ar' => 'الكتيبات الإرشادية',
            ],
            'brand_group_audio' => [
                'en' => 'Official Audio Library',
                'ar' => 'مكتبة الأصوات الرسمية',
            ],
            'brand_group_images' => [
                'en' => 'Imagery',
                'ar' => 'الصور',
            ],
            'brand_group_documents' => [
                'en' => 'Documents',
                'ar' => 'المستندات',
            ],
            'brand_download_button' => [
                'en' => 'Download',
                'ar' => 'تحميل',
            ],
            'brand_table_header_name' => [
                'en' => 'Name',
                'ar' => 'الاسم',
            ],
            'brand_table_header_size' => [
                'en' => 'Size',
                'ar' => 'الحجم',
            ],
            'brand_empty_assets_message' => [
                'en' => 'The asset library is being prepared. Check back soon!',
                'ar' => 'مكتبة الأصول قيد التجهيز. عد قريباً!',
            ],
        ];

        $languages = Language::all()->keyBy('code');

        foreach ($keys as $key => $translations) {
            $languageKey = LanguageKey::updateOrCreate(['key' => $key], []);

            foreach ($translations as $code => $value) {
                if (isset($languages[$code])) {
                    $languageKey->setTranslation('content', $code, $value);
                }
            }
        }
    }

    /**
     * Copy a local file into the public uploads disk and attach it to the
     * brand as an asset (skipped when the asset already exists or the source
     * file is missing). Mirrors FileService::upload for non-request files.
     */
    protected function importAsset(?Brand $brand, string $sourcePath, string $name, string $group)
    {
        if (! $brand || ! file_exists($sourcePath)) {
            return;
        }

        if ($brand->assets()->where('name', $name)->exists()) {
            return;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = 'uploads/' . $fileName;

        // Stream the copy — some assets (e.g. the guideline PDF) are larger
        // than the PHP memory limit, so never buffer them with file_get_contents.
        $stream = fopen($sourcePath, 'rb');
        Storage::disk('public')->put($filePath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $mime = mime_content_type($sourcePath) ?: match (strtolower($extension)) {
            'mp3' => 'audio/mpeg',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };

        $asset = BrandAsset::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'group' => $group,
            'order' => $brand->assets()->count(),
        ]);

        File::create([
            'original_name' => basename($sourcePath),
            'name' => $fileName,
            'path' => $filePath,
            'size' => filesize($sourcePath),
            'type' => $mime,
            'is_main' => 1,
            'model_type' => 'App\\Models\\BrandAsset',
            'model_id' => $asset->id,
        ]);
    }
}
