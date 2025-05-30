<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Str;

class PagesSeeder extends Seeder
{
    public function run()
    {
        $systemPages = [
            [
                'name' => 'Home',
                'slug' => 'home',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Articles',
                'slug' => 'articles',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Albums',
                'slug' => 'albums',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Events',
                'slug' => 'events',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ]
        ];

        foreach ($systemPages as $pageData) {
            $page = Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                [
                    'name' => $pageData['name'],
                    'is_system_page' => $pageData['is_system_page'],
                    'status' => $pageData['status'],
                    'menu_id' => $pageData['menu_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Add default translations for each page
            $this->addPageTranslations($page);
        }
    }

    private function addPageTranslations($page)
    {
        $translations = [
            'home' => [
                'title' => [
                    'en' => 'Welcome to Saud International Schools',
                    'ar' => 'مرحباً بكم في مدارس سعود العالمية'
                ],
                'description' => [
                    'en' => 'Providing excellence in education for a diverse community',
                    'ar' => 'توفير التميز في التعليم لمجتمع متنوع'
                ],
                'content' => [
                    'en' => 'Welcome to our homepage content',
                    'ar' => 'مرحباً بكم في محتوى صفحتنا الرئيسية'
                ]
            ],
            'articles' => [
                'title' => [
                    'en' => 'Articles',
                    'ar' => 'المقالات'
                ],
                'description' => [
                    'en' => 'Latest news and articles from our school',
                    'ar' => 'آخر الأخبار والمقالات من مدرستنا'
                ],
                'content' => [
                    'en' => 'Browse our latest articles and news',
                    'ar' => 'تصفح أحدث مقالاتنا وأخبارنا'
                ]
            ],
            'albums' => [
                'title' => [
                    'en' => 'Photo Albums',
                    'ar' => 'ألبومات الصور'
                ],
                'description' => [
                    'en' => 'View our photo galleries and memories',
                    'ar' => 'شاهد معارض الصور والذكريات'
                ],
                'content' => [
                    'en' => 'Explore our photo albums',
                    'ar' => 'استكشف ألبومات الصور'
                ]
            ],
            'events' => [
                'title' => [
                    'en' => 'School Events',
                    'ar' => 'فعاليات المدرسة'
                ],
                'description' => [
                    'en' => 'Upcoming and past school events',
                    'ar' => 'فعاليات المدرسة القادمة والسابقة'
                ],
                'content' => [
                    'en' => 'Stay updated with our school events',
                    'ar' => 'ابق على اطلاع بفعاليات مدرستنا'
                ]
            ]
        ];

        if (isset($translations[$page->slug])) {
            $pageTranslations = $translations[$page->slug];
            
            foreach ($pageTranslations as $field => $languages) {
                foreach ($languages as $lang => $value) {
                    $page->setTranslation($field, $lang, $value);
                }
            }
        }
    }
}
