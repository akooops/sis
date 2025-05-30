<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\LanguageKey;
use App\Models\Translation;

class LanguageTranslationSeeder extends Seeder
{
    public function run()
    {
        // Create languages
        $en = Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_default' => true, 'is_rtl' => false]
        );
        
        $ar = Language::updateOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'is_default' => false, 'is_rtl' => true]
        );

        // Translation keys and values
        $keys = [
            'website_title' => [
                'en' => 'Saud International Schools',
                'ar' => 'مدارس سعود العالمية'
            ],
            'navbar_visits_nav_link' => [
                'en' => 'Visit',
                'ar' => 'زيارة'
            ],
            'navbar_inquiries_nav_link' => [
                'en' => 'Inquire',
                'ar' => 'استفسار'
            ],
            'navbar_applications_nav_link' => [
                'en' => 'Apply',
                'ar' => 'تقديم'
            ],
            'navbar_change_language_nav_link' => [
                'en' => 'Change language',
                'ar' => 'تغيير اللغة'
            ],
            'navbar_search_form_placeholder' => [
                'en' => 'Type keyword and hit enter',
                'ar' => 'اكتب كلمة مفتاحية واضغط إنتر'
            ],
            'footer_main_title' => [
                'en' => 'Welcome to our schools',
                'ar' => 'مرحباً بكم في مدارسنا'
            ],
            'footer_main_subtitle' => [
                'en' => '',
                'ar' => ''
            ],
            'footer_first_menu_title' => [
                'en' => 'Need help?',
                'ar' => 'تحتاج مساعدة؟'
            ],
            'footer_second_menu_title' => [
                'en' => 'Learn more',
                'ar' => 'اعرف المزيد'
            ],
            'footer_get_in_touch_title' => [
                'en' => 'Get in touch',
                'ar' => 'تواصل معنا'
            ],
            'footer_get_in_touch_address' => [
                'en' => '',
                'ar' => ''
            ],
            'footer_get_in_touch_email' => [
                'en' => '',
                'ar' => ''
            ],
            'footer_get_in_touch_phone' => [
                'en' => '',
                'ar' => ''
            ],
            'footer_all_rights_reserved' => [
                'en' => '© 2025 Saudi international schools. All rights reserved.',
                'ar' => '© 2025 مدارس سعود العالمية. جميع الحقوق محفوظة.'
            ],
            'index_page_first_section_title' => [
                'en' => 'Welcome to Saud international schools',
                'ar' => 'مرحباً بكم في مدارس سعود العالمية'
            ],
            'index_page_first_section_subtitle' => [
                'en' => 'The mission of Saud International School is to provide a challenging educational environment that meets the needs of the diverse community by offering a worldwide known curriculum implemented with state of the art technology along with  extra-curricular activities.',
                'ar' => 'مهمة مدارس سعود العالمية هي توفير بيئة تعليمية تحدي تلبي احتياجات المجتمع المتنوع من خلال تقديم منهج معروف عالمياً مطبق بأحدث التقنيات مع الأنشطة اللامنهجية.'
            ],
            'index_page_first_section_cta' => [
                'en' => 'Learn more',
                'ar' => 'اعرف المزيد'
            ],
            'index_page_second_section_title' => [
                'en' => 'Saud international schools In number',
                'ar' => 'مدارس سعود العالمية بالأرقام'
            ],
            'index_page_second_section_subtitle' => [
                'en' => 'The vision of Saud International School is to create a supportive environment enabling us to equip our students with skills and abilities which will allow them to develop intellectually, physically, socially, emotionally and morally as they become successful and productive lifelong learners in the 21st Century.',
                'ar' => 'رؤية مدارس سعود العالمية هي خلق بيئة داعمة تمكننا من تزويد طلابنا بالمهارات والقدرات التي تسمح لهم بالتطور فكرياً وجسدياً واجتماعياً وعاطفياً وأخلاقياً ليصبحوا متعلمين مدى الحياة ناجحين ومنتجين في القرن الحادي والعشرين.'
            ],
            'index_page_second_section_cta' => [
                'en' => 'Learn more',
                'ar' => 'اعرف المزيد'
            ],
            'index_page_third_section_title' => [
                'en' => 'Message From The Head of School',
                'ar' => 'رسالة من مدير المدرسة'
            ],
            'index_page_third_section_subtitle' => [
                'en' => 'Message From The Head of School',
                'ar' => 'رسالة من مدير المدرسة'
            ],
            'index_page_third_section_cta' => [
                'en' => 'Learn more',
                'ar' => 'اعرف المزيد'
            ],
            'index_page_forth_section_title' => [
                'en' => 'Saud international schools ',
                'ar' => 'مدارس سعود العالمية'
            ],
            'index_page_forth_section_subtitle' => [
                'en' => 'Latest articles',
                'ar' => 'أحدث المقالات'
            ],
            'index_page_forth_section_cta' => [
                'en' => 'View more articles',
                'ar' => 'عرض المزيد من المقالات'
            ],
            'index_page_fifth_section_title' => [
                'en' => 'Saud international schools ',
                'ar' => 'مدارس سعود العالمية'
            ],
            'index_page_fifth_section_subtitle' => [
                'en' => 'Latest albums',
                'ar' => 'أحدث الألبومات'
            ],
            'index_page_fifth_section_cta' => [
                'en' => 'View more albums',
                'ar' => 'عرض المزيد من الألبومات'
            ],
            'index_page_first_number_title' => [
                'en' => 'Schools',
                'ar' => 'مدارس'
            ],
            'index_page_first_number_value' => [
                'en' => '4',
                'ar' => '4'
            ],
            'index_page_second_number_title' => [
                'en' => 'Students',
                'ar' => 'طلاب'
            ],
            'index_page_second_number_value' => [
                'en' => '7518',
                'ar' => '7518'
            ],
            'index_page_third_number_title' => [
                'en' => 'Alumni',
                'ar' => 'خريجون'
            ],
            'index_page_third_number_value' => [
                'en' => '10000',
                'ar' => '10000'
            ],
            'index_page_forth_number_title' => [
                'en' => 'School in saudi',
                'ar' => 'مدرسة في السعودية'
            ],
            'index_page_forth_number_value' => [
                'en' => '1st',
                'ar' => 'أول'
            ],
        ];

        // Seed language keys and translations
        foreach ($keys as $key => $translations) {
            $languageKey = LanguageKey::updateOrCreate(['key' => $key]);

            foreach (['en', 'ar'] as $lang) {
                $language = Language::where('code', $lang)->first();
                if ($language) {
                    Translation::updateOrCreate([
                        'translatable_type' => LanguageKey::class,
                        'translatable_id' => $languageKey->id,
                        'field' => 'content',
                        'language_id' => $language->id,
                    ], [
                        'value' => $translations[$lang] ?? '',
                    ]);
                }
            }
        }
    }
}
