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
            ],
            [
                'name' => 'Visits',
                'slug' => 'visits',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Inquiries',
                'slug' => 'inquiries',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Contact',
                'slug' => 'contact',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Jobs',
                'slug' => 'jobs',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Documents',
                'slug' => 'documents',
                'is_system_page' => true,
                'status' => 'published',
                'menu_id' => null,
            ],
            [
                'name' => 'Error',
                'slug' => 'error',
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
                ],
            ],
            'visits' => [
                'title' => [
                    'en' => 'School Visits',
                    'ar' => 'زيارات المدرسة'
                ],
                'description' => [
                    'en' => 'Book a visit to explore our school facilities and programs',
                    'ar' => 'احجز زيارة لاستكشاف مرافق وبرامج مدرستنا'
                ],
                'content' => [
                    'en' => 'We welcome you to visit our school and discover what makes us special. Choose from our available visit services and book a convenient time for your tour.',
                    'ar' => 'نرحب بكم لزيارة مدرستنا واكتشاف ما يجعلنا مميزين. اختر من خدمات الزيارة المتاحة واحجز وقتاً مناسباً لجولتك.'
                ]
            ],
            'inquiries' => [
                'title' => [
                    'en' => 'Student Inquiries',
                    'ar' => 'استفسارات الطلاب'
                ],
                'description' => [
                    'en' => 'Submit your inquiry about admissions and school programs',
                    'ar' => 'قدم استفسارك حول القبول وبرامج المدرسة'
                ],
                'content' => [
                    'en' => 'Have questions about our school? We\'re here to help! Please fill out the form below with your inquiry about admissions, programs, or any other questions you may have. Our admissions team will get back to you as soon as possible.',
                    'ar' => 'لديك أسئلة حول مدرستنا؟ نحن هنا للمساعدة! يرجى ملء النموذج أدناه باستفسارك حول القبول أو البرامج أو أي أسئلة أخرى قد تكون لديك. سيتواصل معك فريق القبول في أقرب وقت ممكن.'
                ]
            ],
            'contact' => [
                'title' => [
                    'en' => 'Contact Us',
                    'ar' => 'اتصل بنا'
                ],
                'description' => [
                    'en' => 'Get in touch with us for any questions or information',
                    'ar' => 'تواصل معنا لأي أسئلة أو معلومات'
                ],
                'content' => [
                    'en' => 'We would love to hear from you! Please feel free to reach out to us with any questions, comments, or inquiries. Our team is here to help and will respond to your message as soon as possible.',
                    'ar' => 'نحن نحب أن نسمع منك! لا تتردد في التواصل معنا لأي أسئلة أو تعليقات أو استفسارات. فريقنا هنا للمساعدة وسيرد على رسالتك في أقرب وقت ممكن.'
                ]
            ],
            'jobs' => [ 
                'title' => [
                    'en' => 'Career Opportunities',
                    'ar' => 'الفرص الوظيفية'
                ],
                'description' => [
                    'en' => 'Join our team of dedicated educators and staff',
                    'ar' => 'انضم إلى فريقنا من المعلمين والموظفين المتفانين'
                ],
                'content' => [
                    'en' => 'Discover exciting career opportunities at Saud International Schools. We are always looking for passionate educators and dedicated professionals to join our team and help shape the future of education.',
                    'ar' => 'اكتشف الفرص الوظيفية المثيرة في مدارس سعود العالمية. نحن نبحث دائماً عن معلمين متحمسين ومهنيين متفانين للانضمام إلى فريقنا والمساعدة في تشكيل مستقبل التعليم.'
                ]
            ],
            'documents' => [
                'title' => [
                    'en' => 'School Documents',
                    'ar' => 'وثائق المدرسة'
                ],
                'description' => [
                    'en' => 'Access important school documents, policies, and forms',
                    'ar' => 'الوصول إلى وثائق المدرسة المهمة والسياسات والنماذج'
                ],
                'content' => [
                    'en' => 'Find all the important school documents including policies, handbooks, forms, and official communications. All documents are available for download.',
                    'ar' => 'ابحث عن جميع وثائق المدرسة المهمة بما في ذلك السياسات والكتيبات والنماذج والمراسلات الرسمية. جميع الوثائق متاحة للتحميل.'
                ]
            ],
            'error' => [
                'title' => [
                    'en' => 'Page Not Found',
                    'ar' => 'الصفحة غير موجودة'
                ],
                'description' => [
                    'en' => 'The page you are looking for could not be found',
                    'ar' => 'لا يمكن العثور على الصفحة التي تبحث عنها'
                ],
                'content' => [
                    'en' => 'Sorry, the page you are looking for could not be found. Please check the URL or return to our homepage.',
                    'ar' => 'عذراً، لا يمكن العثور على الصفحة التي تبحث عنها. يرجى التحقق من الرابط أو العودة إلى صفحتنا الرئيسية.'
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
