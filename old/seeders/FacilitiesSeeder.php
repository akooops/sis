<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Page;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    public function run()
    {
        $en = Language::where('code', 'en')->first();
        $ar = Language::where('code', 'ar')->first();

        $facilities = [
            [
                'name' => 'Al Awael Hall',
                'slug' => 'al-awael-hall',
                'domain' => 'hall',
                'order' => 1,
                'theme' => ['primary_color' => '#8a6d3b', 'secondary_color' => '#1f2a44'],
                'title' => 'Al Awael Hall',
                'tagline' => 'A Prestigious Auditorium Designed for Exceptional Events',
                'description' => 'Al Awael Hall is a refined, state-of-the-art auditorium designed to host impactful gatherings, formal events, educational programs, performances, presentations, and community occasions. With a seating capacity of approximately 370 guests, it offers the scale, atmosphere, and professional setting required for events that deserve a distinguished venue.',
                'title_ar' => 'قاعة الأوائل',
                'tagline_ar' => 'قاعة فاخرة صُممت لاستضافة فعاليات استثنائية',
                'description_ar' => 'قاعة الأوائل قاعة احتفالات راقية ومجهزة بأحدث التقنيات، صُممت لاستضافة التجمعات المؤثرة والفعاليات الرسمية والبرامج التعليمية والعروض والمناسبات المجتمعية. وبسعة تقارب 370 مقعداً، توفر المساحة والأجواء والبيئة الاحترافية التي تستحقها الفعاليات المميزة.',
            ],
            [
                'name' => 'The Hive',
                'slug' => 'the-hive',
                'domain' => 'hive',
                'order' => 2,
                'theme' => ['primary_color' => '#d59d28', 'secondary_color' => '#3a3325'],
                'title' => 'The Hive',
                'tagline' => 'A Contemporary Learning Space for Ideas, Dialogue, and Collaboration',
                'description' => 'The Hive is a modern, purpose-designed learning area created for meaningful conversations, creative thinking, professional development, and community engagement. It offers a refined setting for trainings, seminars, workshops, book events, discussions, and collaborative sessions.',
                'title_ar' => 'ذا هايف',
                'tagline_ar' => 'مساحة تعلم عصرية للأفكار والحوار والتعاون',
                'description_ar' => 'ذا هايف مساحة تعلم حديثة صُممت للحوارات الهادفة والتفكير الإبداعي والتطوير المهني والمشاركة المجتمعية، وتقدم إطاراً راقياً للدورات التدريبية والندوات وورش العمل وفعاليات الكتب والجلسات الحوارية والتعاونية.',
            ],
            [
                'name' => 'The Canvas',
                'slug' => 'the-canvas',
                'domain' => 'canvas',
                'order' => 3,
                'theme' => ['primary_color' => '#2a9d8f', 'secondary_color' => '#264653'],
                'title' => 'The Canvas',
                'tagline' => 'A Creative Learning and Media Space for Ideas, Expression, and Innovation',
                'description' => 'The Canvas is a modern, design-led learning area created for collaboration, creativity, knowledge sharing, and high-quality content experiences — including dedicated podcast rooms equipped with cameras, microphones, and lighting for professional recordings.',
                'title_ar' => 'ذا كانفس',
                'tagline_ar' => 'مساحة إبداعية للتعلم والإعلام: للأفكار والتعبير والابتكار',
                'description_ar' => 'ذا كانفس مساحة تعلم عصرية بتصميم مميز أُنشئت للتعاون والإبداع ومشاركة المعرفة وتجارب المحتوى عالية الجودة — وتشمل غرف بودكاست مخصصة مجهزة بالكاميرات والميكروفونات والإضاءة للتسجيلات الاحترافية.',
            ],
            [
                'name' => 'Academic Innovation Labs',
                'slug' => 'innovation-labs',
                'domain' => 'labs',
                'order' => 4,
                'theme' => ['primary_color' => '#2563eb', 'secondary_color' => '#1e293b'],
                'title' => 'Academic Innovation Labs',
                'tagline' => 'Purpose-Built Spaces for Scientific Inquiry, Digital Learning, Practical Skills, and Future-Ready Innovation',
                'description' => 'Our Academic Innovation Labs are specialized learning environments where students can observe, explore, test, build, question, create, and apply what they learn — from computer science and robotics to chemistry, physics, and life skills.',
                'title_ar' => 'مختبرات الابتكار الأكاديمي',
                'tagline_ar' => 'مساحات مصممة خصيصاً للاستقصاء العلمي والتعلم الرقمي والمهارات العملية والابتكار',
                'description_ar' => 'مختبرات الابتكار الأكاديمي بيئات تعلم متخصصة يستطيع الطلاب فيها الملاحظة والاستكشاف والتجربة والبناء والتساؤل والإبداع وتطبيق ما يتعلمونه — من علوم الحاسب والروبوتات إلى الكيمياء والفيزياء والمهارات الحياتية.',
            ],
            [
                'name' => 'Sports & Wellness Complex',
                'slug' => 'sports-complex',
                'domain' => 'sports',
                'order' => 5,
                'theme' => ['primary_color' => '#15803d', 'secondary_color' => '#14532d'],
                'title' => 'Sports & Wellness Complex',
                'tagline' => 'A Complete Athletic Environment for Fitness, Training, Recreation, and Competitive Sport',
                'description' => 'A modern, fully equipped athletic environment bringing together swimming pools, basketball courts, football fields, gymnastics and martial arts areas, a professional fitness and weightlifting zone, and dedicated activity spaces for different age groups.',
                'title_ar' => 'المجمع الرياضي والصحي',
                'tagline_ar' => 'بيئة رياضية متكاملة للياقة والتدريب والترفيه والرياضة التنافسية',
                'description_ar' => 'بيئة رياضية حديثة ومجهزة بالكامل تجمع بين المسابح وملاعب كرة السلة وملاعب كرة القدم ومناطق الجمباز والفنون القتالية ومنطقة احترافية للياقة ورفع الأثقال ومساحات أنشطة مخصصة لمختلف الفئات العمرية.',
            ],
        ];

        foreach ($facilities as $data) {
            $facility = Facility::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'domain' => $data['domain'],
                    'status' => 'published',
                    'order' => $data['order'],
                    'theme' => $data['theme'],
                ]
            );

            if ($en) {
                $facility->setTranslation('title', $en->code, $data['title']);
                $facility->setTranslation('tagline', $en->code, $data['tagline']);
                $facility->setTranslation('description', $en->code, $data['description']);

                $contentPath = database_path("seeders/data/facilities/{$data['slug']}.html");
                if (file_exists($contentPath)) {
                    $facility->setTranslation('content', $en->code, file_get_contents($contentPath));
                }
            }

            if ($ar) {
                $facility->setTranslation('title', $ar->code, $data['title_ar']);
                $facility->setTranslation('tagline', $ar->code, $data['tagline_ar']);
                $facility->setTranslation('description', $ar->code, $data['description_ar']);

                $arContentPath = database_path("seeders/data/facilities/{$data['slug']}.ar.html");
                if (file_exists($arContentPath)) {
                    $facility->setTranslation('content', $ar->code, file_get_contents($arContentPath));
                }
            }
        }

        // Main-site facilities landing page
        $facilitiesPage = Page::updateOrCreate(
            ['slug' => 'facilities'],
            [
                'name' => 'Facilities',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ]
        );

        $pageTranslations = [
            'en' => [
                'title' => 'Our Facilities',
                'description' => 'Discover our facilities: Al Awael Hall, The Hive, The Canvas, the Academic Innovation Labs and the Sports & Wellness Complex.',
                'content' => '<p>Each of our facilities has its own dedicated website where you can explore its spaces, browse news and albums, follow events, get in touch, and request a reservation.</p>',
            ],
            'ar' => [
                'title' => 'مرافقنا',
                'description' => 'اكتشف مرافقنا: قاعة الأوائل، ذا هايف، ذا كانفس، مختبرات الابتكار الأكاديمي، والمجمع الرياضي.',
                'content' => '<p>لكل مرفق من مرافقنا موقع خاص به يمكنك من خلاله استكشاف المساحات وتصفح الأخبار والألبومات ومتابعة الفعاليات والتواصل وطلب الحجز.</p>',
            ],
        ];

        foreach ($pageTranslations as $code => $fields) {
            foreach ($fields as $field => $value) {
                $facilitiesPage->setTranslation($field, $code, $value);
            }
        }

        // Mini-site UI language keys
        $keys = [
            'facilities_page_visit_button' => [
                'en' => 'Visit Website',
                'ar' => 'زيارة الموقع',
            ],
            'facility_nav_home' => [
                'en' => 'Home',
                'ar' => 'الرئيسية',
            ],
            'facility_nav_articles' => [
                'en' => 'Articles',
                'ar' => 'المقالات',
            ],
            'facility_nav_albums' => [
                'en' => 'Albums',
                'ar' => 'الألبومات',
            ],
            'facility_nav_events' => [
                'en' => 'Events',
                'ar' => 'الفعاليات',
            ],
            'facility_nav_contact' => [
                'en' => 'Contact Us',
                'ar' => 'اتصل بنا',
            ],
            'facility_nav_reserve' => [
                'en' => 'Reserve Now',
                'ar' => 'احجز الآن',
            ],
            'facility_home_reserve_cta' => [
                'en' => 'Reserve this facility',
                'ar' => 'احجز هذا المرفق',
            ],
            'facility_home_events_title' => [
                'en' => 'Upcoming Events',
                'ar' => 'الفعاليات القادمة',
            ],
            'facility_home_articles_title' => [
                'en' => 'Latest Articles',
                'ar' => 'أحدث المقالات',
            ],
            'facility_home_albums_title' => [
                'en' => 'Albums',
                'ar' => 'الألبومات',
            ],
            'facility_home_view_all' => [
                'en' => 'View All',
                'ar' => 'عرض الكل',
            ],
            'facility_article_more_title' => [
                'en' => 'More Articles',
                'ar' => 'المزيد من المقالات',
            ],
            'facility_empty_list_message' => [
                'en' => 'Nothing here yet. Check back soon!',
                'ar' => 'لا يوجد محتوى بعد. عد قريباً!',
            ],
            'facility_footer_contact_title' => [
                'en' => 'Get in Touch',
                'ar' => 'تواصل معنا',
            ],
            'facility_footer_links_title' => [
                'en' => 'Quick Links',
                'ar' => 'روابط سريعة',
            ],
            'facility_footer_main_site_link' => [
                'en' => 'Main Website',
                'ar' => 'الموقع الرئيسي',
            ],
            'facility_footer_rights' => [
                'en' => 'All rights reserved.',
                'ar' => 'جميع الحقوق محفوظة.',
            ],
            'facility_contact_page_title' => [
                'en' => 'Contact Us',
                'ar' => 'اتصل بنا',
            ],
            'facility_contact_success_message' => [
                'en' => 'Your message has been sent successfully. We will get back to you soon!',
                'ar' => 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً!',
            ],
            'facility_reserve_page_title' => [
                'en' => 'Request a Reservation',
                'ar' => 'طلب حجز',
            ],
            'facility_reserve_page_intro' => [
                'en' => 'Pick an available time slot on the calendar, fill in your details, and our team will contact you to confirm your reservation.',
                'ar' => 'اختر موعداً متاحاً من التقويم، ثم أدخل بياناتك وسيتواصل معك فريقنا لتأكيد الحجز.',
            ],
            'facility_reserve_step_1' => [
                'en' => 'Choose a Time Slot',
                'ar' => 'اختر الموعد',
            ],
            'facility_reserve_step_2' => [
                'en' => 'Your Details & Confirmation',
                'ar' => 'بياناتك والتأكيد',
            ],
            'facility_reserve_confirmation_title' => [
                'en' => 'Confirm Your Reservation Request',
                'ar' => 'تأكيد طلب الحجز',
            ],
            'facility_reserve_guests_input' => [
                'en' => 'Number of guests',
                'ar' => 'عدد الضيوف',
            ],
            'facility_reserve_message_input' => [
                'en' => 'Tell us about your event (optional)',
                'ar' => 'أخبرنا عن فعاليتك (اختياري)',
            ],
            'facility_reserve_selected_facility' => [
                'en' => 'Facility',
                'ar' => 'المرفق',
            ],
            'facility_reserve_success_message' => [
                'en' => 'Your reservation request has been sent. Our team will contact you soon to confirm!',
                'ar' => 'تم إرسال طلب الحجز الخاص بك. سيتواصل معك فريقنا قريباً للتأكيد!',
            ],
            'facility_reserve_error_message' => [
                'en' => 'Something went wrong while sending your request. Please try again.',
                'ar' => 'حدث خطأ أثناء إرسال طلبك. يرجى المحاولة مرة أخرى.',
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
}
