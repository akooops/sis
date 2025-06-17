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
                'en' => 'The mission of Saud International School is to provide a challenging educational environment that meets the needs of the diverse community by offering a worldwide known curriculum implemented with state of the art technology along with extra-curricular activities.',
                'ar' => 'تتمثل رؤية مدرسة سعود العالمية في خلق بيئة داعمة تمكننا من تزويد طلابنا بالمهارات والقدرات التي ستتيح لهم التطور فكريا وجسديا واجتماعيا وعاطفيا وأخلاقيا وعندها يصبحون متعلمين ناجحين ومنتجين.'
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
            'get_in_touch_address' => [
                'en' => 'Saud International School Hamdan Street, Sulaimaniyah, Riyadh Kingdom of Saudi Arabia',
                'ar' => 'Saud International School Hamdan Street, Sulaimaniyah, Riyadh Kingdom of Saudi Arabia'
            ],
            'get_in_touch_email' => [
                'en' => 'enquiries@sis.edu.sa',
                'ar' => 'enquiries@sis.edu.sa'
            ],
            'get_in_touch_phone' => [
                'en' => '(966) 920002877',
                'ar' => '(966) 920002877'
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
            'breadcrumbs_index_page_title' => [
                'en' => 'Home',
                'ar' => 'الرئيسية'
            ],
            'sidebar_search_form_placeholder' => [
                'en' => 'Search...',
                'ar' => 'قم بالبحث'
            ],
            'sidebar_popular_article_title' => [
                'en' => 'Popular articles',
                'ar' => 'أشهر المقالات'
            ],
            'breadcrumbs_articles_page_title' => [
                'en' => 'Articles',
                'ar' => 'المقالات'
            ],
            'breadcrumbs_albums_page_title' => [
                'en' => 'Albums',
                'ar' => 'الألبومات'
            ],
            'program_page_collapse_title' => [
                'en' => 'Grades',
                'ar' => 'الدرجات'
            ],
            'program_page_table_header_grade' => [
                'en' => 'Grade',
                'ar' => 'الدرجة'
            ],
            'program_page_table_header_option' => [
                'en' => 'Options',
                'ar' => 'الإطلاع'
            ],
            'program_page_table_cta' => [
                'en' => 'Check it out',
                'ar' => 'الإطلاع'
            ],
            'grade_page_collapse_title' => [
                'en' => 'Files',
                'ar' => 'الملفات'
            ],
            'grade_page_table_header_file' => [
                'en' => 'File',
                'ar' => 'الملف'
            ],
            'grade_page_table_header_option' => [
                'en' => 'Options',
                'ar' => 'الإطلاع'
            ],
            'grade_page_table_cta' => [
                'en' => 'Download',
                'ar' => 'التحميل'
            ],
            'breadcrumbs_events_page_title' => [
                'en' => 'Events',
                'ar' => 'الفعاليات'
            ],
            'visits_page_read_more_cta' => [
                'en' => 'Read more',
                'ar' => 'تعرف على المزيد'
            ],
            'visits_page_select_button' => [
                'en' => 'Select',
                'ar' => 'إختيار'
            ],
            'visit_page_step_1' => [
                'en' => 'Service',
                'ar' => 'الخدمة'
            ],
            'visit_page_step_2' => [
                'en' => 'Time',
                'ar' => 'الوقت'
            ],
            'visit_page_step_3' => [
                'en' => 'Confirm',
                'ar' => 'تأكيد'
            ],
            'visit_pages_visitors_count' => [
                'en' => 'Visitors',
                'ar' => 'الزوار'
            ],
            'visits_page_confirmation_title' => [
                'en' => 'Please, confirm details',
                'ar' => 'من فضلك قم بالتأكيد'
            ],
            'visits_page_name_input' => [
                'en' => 'Enter your name',
                'ar' => 'قم بإدخال إسمك'
            ],
            'visits_page_email_input' => [
                'en' => 'Enter your email',
                'ar' => 'قم بإدخال بريدك الإلكتروني'
            ],
            'visits_page_phone_input' => [
                'en' => 'Enter your phone',
                'ar' => 'قم بإدخال رقم الهاتف'
            ],
            'visits_page_student_name_input' => [
                'en' => 'Enter student name',
                'ar' => 'قم بإدخال إسم التلميذ'
            ],
            'visits_page_student_grade_input' => [
                'en' => 'Enter student grade',
                'ar' => 'قم بإدخال المستوى الدراسي للتلميذ'
            ],
            'visits_page_student_school_input' => [
                'en' => 'Enter student school',
                'ar' => 'قم بإدخال المدرسة الحالية للتميذ'
            ],
            'visits_page_confirm_button' => [
                'en' => 'Confirm booking',
                'ar' => 'تأكيد الحجز'
            ],
            'visits_page_date_time' => [
                'en' => 'Date & Time',
                'ar' => 'التاريخ والوقت'
            ],
            'visit_pages_selected_service' => [
                'en' => 'Selected service',
                'ar' => 'الخدمة المختارة'
            ],
            'visit_pages_booking_success' => [
                'en' => 'Your booking has been confirmed successfully, our service will contact you soon!',
                'ar' => 'تم تأكيد حجزك بنجاح، سيقوم فريقنا بالإتصال بكم قريبا!'
            ],
            'visit_pages_booking_error' => [
                'en' => 'An error occurred while processing your booking. Please try again.',
                'ar' => 'حدث خطأ أثناء معالجة حجزك. يرجى المحاولة مرة أخرى.'
            ],
            'visit_pages_slot_reserved_error' => [
                'en' => 'This time slot is already reserved. Please select another time.',
                'ar' => 'هذا الوقت محجوز بالفعل. يرجى اختيار وقت آخر.'
            ],

            'visit_pages_available_slots' => [
                'en' => 'Available',
                'ar' => 'متاح'
            ],

            'visit_pages_reserved_slots' => [
                'en' => 'Reserved',
                'ar' => 'محجوز'
            ],
            'recaptcha_error' => [
                'en' => 'Security verification failed. Please try again.',
                'ar' => 'فشل التحقق الأمني. يرجى المحاولة مرة أخرى.'
            ],
            'inquiry_page_guardian_name_input' => [
                'en' => 'Guardian Name',
                'ar' => 'اسم ولي الأمر'
            ],
            'inquiry_page_email_input' => [
                'en' => 'Email Address',
                'ar' => 'البريد الإلكتروني'
            ],
            'inquiry_page_phone_input' => [
                'en' => 'Phone Number',
                'ar' => 'رقم الهاتف'
            ],
            'inquiry_page_student_name_input' => [
                'en' => 'Student Name',
                'ar' => 'اسم الطالب'
            ],
            'inquiry_page_birthdate_input' => [
                'en' => 'Student Birthdate',
                'ar' => 'تاريخ ميلاد الطالب'
            ],
            'inquiry_page_student_school_input' => [
                'en' => 'Current School',
                'ar' => 'المدرسة الحالية'
            ],
            'inquiry_page_select_academic_year' => [
                'en' => 'Select Academic Year',
                'ar' => 'اختر السنة الأكاديمية'
            ],
            'inquiry_page_select_grade' => [
                'en' => 'Select Grade',
                'ar' => 'اختر الصف'
            ],
            'inquiry_page_questions_input' => [
                'en' => 'Questions or Comments',
                'ar' => 'الأسئلة أو التعليقات'
            ],
            'inquiry_page_submit_button' => [
                'en' => 'Submit Inquiry',
                'ar' => 'إرسال الاستفسار'
            ],
            'inquiry_success_message' => [
                'en' => 'Your inquiry has been submitted successfully! We will contact you soon.',
                'ar' => 'تم إرسال استفسارك بنجاح! سنتواصل معك قريباً.'
            ],
            'contact_page_name_input' => [
                'en' => 'Full Name',
                'ar' => 'الاسم الكامل'
            ],
            'contact_page_email_input' => [
                'en' => 'Email Address',
                'ar' => 'البريد الإلكتروني'
            ],
            'contact_page_phone_input' => [
                'en' => 'Phone Number',
                'ar' => 'رقم الهاتف'
            ],
            'contact_page_subject_input' => [
                'en' => 'Subject',
                'ar' => 'الموضوع'
            ],
            'contact_page_message_input' => [
                'en' => 'Your Message',
                'ar' => 'رسالتك'
            ],
            'contact_page_submit_button' => [
                'en' => 'Send Message',
                'ar' => 'إرسال الرسالة'
            ],
            'contact_submission_success_message' => [
                'en' => 'Thank you for your message! We will get back to you as soon as possible.',
                'ar' => 'شكراً لك على رسالتك! سنتواصل معك في أقرب وقت ممكن.'
            ],
            'contact_page_address_title' => [
                'en' => 'Address',
                'ar' => 'العنوان'
            ],
            'contact_page_email_title' => [
                'en' => 'Email',
                'ar' => 'البريد الإلكتروني'
            ],
            'contact_page_phone_title' => [
                'en' => 'Phone',
                'ar' => 'رقم الهاتف'
            ],
            'error_page_button_title' => [
                'en' => 'Home page',
                'ar' => 'الصفحة الرئيسية'
            ],
            'jobs_search_placeholder' => [
                'en' => 'Search for job positions, keywords...',
                'ar' => 'ابحث عن الوظائف، الكلمات المفتاحية...'
            ],
            'jobs_search_button' => [
                'en' => 'Search',
                'ar' => 'بحث'
            ],
            'jobs_full_time' => [
                'en' => 'Full Time',
                'ar' => 'دوام كامل'
            ],
            'jobs_part_time' => [
                'en' => 'Part Time',
                'ar' => 'دوام جزئي'
            ],
            'jobs_internship' => [
                'en' => 'Internship',
                'ar' => 'تدريب'
            ],
            'jobs_remote' => [
                'en' => 'Remote',
                'ar' => 'عن بُعد'
            ],
            'jobs_view_details' => [
                'en' => 'View Details',
                'ar' => 'عرض التفاصيل'
            ],
            'jobs_deadline' => [
                'en' => 'Deadline',
                'ar' => 'الموعد النهائي'
            ],
            'jobs_no_jobs_available' => [
                'en' => 'No job openings available at the moment',
                'ar' => 'لا توجد وظائف شاغرة متاحة في الوقت الحالي'
            ],
            'breadcrumbs_jobs_page_title' => [
                'en' => 'Jobs',
                'ar' => 'الوظائف', 
            ],
            'job_description_title' => [
                'en' => 'Job Description',
                'ar' => 'وصف الوظيفة'
            ],

            'job_required_skills_title' => [
                'en' => 'Required Skills',
                'ar' => 'المهارات المطلوبة'
            ],

            'job_application_expired' => [
                'en' => 'This job application has expired',
                'ar' => 'انتهت صلاحية التقديم لهذه الوظيفة'
            ],

            'job_apply_now' => [
                'en' => 'Apply Now',
                'ar' => 'قدم الآن'
            ],

            'job_details_title' => [
                'en' => 'Job Details',
                'ar' => 'تفاصيل الوظيفة'
            ],

            'job_experience_required' => [
                'en' => 'Years of Experience',
                'ar' => 'سنوات الخبرة'
            ],

            'job_years' => [
                'en' => 'years',
                'ar' => 'سنوات'
            ],

            'job_skills_title' => [
                'en' => 'Skills',
                'ar' => 'المهارات'
            ],

            'job_employment_type' => [
                'en' => 'Employment Type',
                'ar' => 'نوع التوظيف'
            ],

            'jobs_full_time' => [
                'en' => 'Full Time',
                'ar' => 'دوام كامل'
            ],

            'jobs_part_time' => [
                'en' => 'Part Time',
                'ar' => 'دوام جزئي'
            ],

            'jobs_internship' => [
                'en' => 'Internship',
                'ar' => 'تدريب'
            ],

            'job_work_type' => [
                'en' => 'Work Type',
                'ar' => 'نوع العمل'
            ],

            'jobs_remote' => [
                'en' => 'Remote',
                'ar' => 'عن بُعد'
            ],

            'jobs_onsite' => [
                'en' => 'On-site',
                'ar' => 'في الموقع'
            ],

            'job_positions_available' => [
                'en' => 'Positions Available',
                'ar' => 'الوظائف المتاحة'
            ],

            'job_application_deadline' => [
                'en' => 'Application Deadline',
                'ar' => 'الموعد النهائي للتقديم'
            ],

            'jobs_closing_soon' => [
                'en' => 'Closing Soon',
                'ar' => 'ينتهي قريباً'
            ],

            'job_posted_date' => [
                'en' => 'Posted Date',
                'ar' => 'تاريخ النشر'
            ],

            'jobs_full_time' => ['en' => 'Full Time', 'ar' => 'دوام كامل'],
            'jobs_part_time' => ['en' => 'Part Time', 'ar' => 'دوام جزئي'],
            'jobs_internship' => ['en' => 'Internship', 'ar' => 'تدريب'],
            'jobs_remote' => ['en' => 'Remote', 'ar' => 'عن بُعد'],
            'breadcrumbs_index_page_title' => ['en' => 'Home', 'ar' => 'الرئيسية'],
            'jobs_breadcrumb' => ['en' => 'Jobs', 'ar' => 'الوظائف'],
            'job_description_title' => ['en' => 'Job Description', 'ar' => 'وصف الوظيفة'],
            'job_application_expired' => ['en' => 'This job application has expired', 'ar' => 'انتهت صلاحية التقديم لهذه الوظيفة'],
            'job_apply_now' => ['en' => 'Apply Now', 'ar' => 'تقدم الآن'],
            'job_application_title' => ['en' => 'Job Application', 'ar' => 'طلب التوظيف'],
            'job_close_form' => ['en' => 'Close', 'ar' => 'إغلاق'],
            'job_step_personal' => ['en' => 'Personal', 'ar' => 'شخصي'],
            'job_step_education' => ['en' => 'Education', 'ar' => 'التعليم'],
            'job_step_experience' => ['en' => 'Experience', 'ar' => 'الخبرة'],
            'job_step_languages' => ['en' => 'Languages', 'ar' => 'اللغات'],
            'job_step_skills' => ['en' => 'Skills', 'ar' => 'المهارات'],
            'job_step_documents' => ['en' => 'Documents', 'ar' => 'المستندات'],
            'job_step_review' => ['en' => 'Review', 'ar' => 'المراجعة'],
            'job_personal_info' => ['en' => 'Personal Information', 'ar' => 'المعلومات الشخصية'],
            'job_first_name' => ['en' => 'First Name', 'ar' => 'الاسم الأول'],
            'job_last_name' => ['en' => 'Last Name', 'ar' => 'الاسم الأخير'],
            'job_email' => ['en' => 'Email', 'ar' => 'البريد الإلكتروني'],
            'job_phone' => ['en' => 'Phone', 'ar' => 'رقم الهاتف'],
            'job_nationality' => ['en' => 'Nationality', 'ar' => 'الجنسية'],
            'job_date_birth' => ['en' => 'Date of Birth', 'ar' => 'تاريخ الميلاد'],
            'job_address' => ['en' => 'Address', 'ar' => 'العنوان'],
            'job_education' => ['en' => 'Education', 'ar' => 'التعليم'],
            'job_institution' => ['en' => 'Institution', 'ar' => 'المؤسسة التعليمية'],
            'job_degree' => ['en' => 'Degree', 'ar' => 'الدرجة العلمية'],
            'job_field_study' => ['en' => 'Field of Study', 'ar' => 'مجال الدراسة'],
            'job_start_year' => ['en' => 'Start Year', 'ar' => 'سنة البداية'],
            'job_end_year' => ['en' => 'End Year', 'ar' => 'سنة النهاية'],
            'job_description' => ['en' => 'Description', 'ar' => 'الوصف'],
            'job_add_education' => ['en' => 'Add Education', 'ar' => 'إضافة تعليم'],
            'job_work_experience' => ['en' => 'Work Experience', 'ar' => 'الخبرة العملية'],
            'job_company_name' => ['en' => 'Company Name', 'ar' => 'اسم الشركة'],
            'job_job_title' => ['en' => 'Job Title', 'ar' => 'المسمى الوظيفي'],
            'job_start_date' => ['en' => 'Start Date', 'ar' => 'تاريخ البداية'],
            'job_end_date' => ['en' => 'End Date', 'ar' => 'تاريخ النهاية'],
            'job_current_job' => ['en' => 'Currently working here', 'ar' => 'أعمل هنا حاليًا'],
            'job_job_description' => ['en' => 'Job Description', 'ar' => 'وصف الوظيفة'],
            'job_add_experience' => ['en' => 'Add Experience', 'ar' => 'إضافة خبرة'],
            'job_languages' => ['en' => 'Languages', 'ar' => 'اللغات'],
            'job_language_name' => ['en' => 'Language', 'ar' => 'اللغة'],
            'job_proficiency' => ['en' => 'Proficiency Level', 'ar' => 'مستوى الإتقان'],
            'job_select_proficiency' => ['en' => 'Select Proficiency', 'ar' => 'اختر مستوى الإتقان'],
            'job_basic' => ['en' => 'Basic', 'ar' => 'أساسي'],
            'job_intermediate' => ['en' => 'Intermediate', 'ar' => 'متوسط'],
            'job_advanced' => ['en' => 'Advanced', 'ar' => 'متقدم'],
            'job_native' => ['en' => 'Native', 'ar' => 'لغة أم'],
            'job_add_language' => ['en' => 'Add Language', 'ar' => 'إضافة لغة'],
            'job_skills' => ['en' => 'Skills', 'ar' => 'المهارات'],
            'job_add_skills' => ['en' => 'Add your skills (press Enter after each skill)', 'ar' => 'أضف مهاراتك (اضغط Enter بعد كل مهارة)'],
            'job_skill_placeholder' => ['en' => 'Type a skill and press Enter', 'ar' => 'اكتب مهارة واضغط Enter'],
            'job_no_skills' => ['en' => 'No skills added yet. Type a skill above and press Enter.', 'ar' => 'لم تتم إضافة أي مهارات بعد. اكتب مهارة أعلاه واضغط Enter.'],
            'job_documents' => ['en' => 'Documents', 'ar' => 'المستندات'],
            'job_cv_required' => ['en' => 'CV/Resume', 'ar' => 'السيرة الذاتية'],
            'job_drag_cv' => ['en' => 'Drag & drop your CV here or', 'ar' => 'اسحب وأفلت سيرتك الذاتية هنا أو'],
            'job_browse_files' => ['en' => 'browse files', 'ar' => 'تصفح الملفات'],
            'job_cv_formats' => ['en' => 'Supported formats: PDF, DOC, DOCX (Max: 5MB)', 'ar' => 'الصيغ المدعومة: PDF, DOC, DOCX (الحد الأقصى: 5 ميجابايت)'],
            'job_additional_docs' => ['en' => 'Additional Documents', 'ar' => 'مستندات إضافية'],
            'job_optional' => ['en' => 'Optional', 'ar' => 'اختياري'],
            'job_drag_additional' => ['en' => 'Drag & drop additional documents or', 'ar' => 'اسحب وأفلت المستندات الإضافية أو'],
            'job_additional_formats' => ['en' => 'Certificates, portfolios, etc. (Max: 5MB each)', 'ar' => 'الشهادات، أعمال سابقة، إلخ (الحد الأقصى: 5 ميجابايت لكل ملف)'],
            'job_review_submit' => ['en' => 'Review & Submit', 'ar' => 'المراجعة والإرسال'],
            'job_name' => ['en' => 'Name', 'ar' => 'الاسم'],
            'job_previous' => ['en' => 'Previous', 'ar' => 'السابق'],
            'job_next' => ['en' => 'Next', 'ar' => 'التالي'],
            'job_submit_application' => ['en' => 'Submit Application', 'ar' => 'إرسال الطلب'],
            'job_details_title' => ['en' => 'Job Details', 'ar' => 'تفاصيل الوظيفة'],
            'job_experience_required' => ['en' => 'Years of Experience', 'ar' => 'سنوات الخبرة'],
            'job_years' => ['en' => 'years', 'ar' => 'سنوات'],
            'job_employment_type' => ['en' => 'Employment Type', 'ar' => 'نوع التوظيف'],
            'job_work_type' => ['en' => 'Work Type', 'ar' => 'نوع العمل'],
            'jobs_onsite' => ['en' => 'On-site', 'ar' => 'في الموقع'],
            'job_positions_available' => ['en' => 'Positions Available', 'ar' => 'المناصب المتاحة'],
            'job_application_deadline' => ['en' => 'Application Deadline', 'ar' => 'الموعد النهائي للتقديم'],
            'jobs_closing_soon' => ['en' => 'Closing Soon', 'ar' => 'ينتهي قريباً'],
            'job_posted_date' => ['en' => 'Posted Date', 'ar' => 'تاريخ النشر'],
            'job_about_school' => ['en' => 'About Our School', 'ar' => 'عن مدرستنا'],
            'job_school_description' => ['en' => 'Saud International Schools is committed to providing excellence in education for a diverse community.', 'ar' => 'تلتزم مدارس سعود العالمية بتقديم التميز في التعليم لمجتمع متنوع.'],
            'job_contact_us' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
            'job_application_success' => ['en' => 'Application submitted successfully!', 'ar' => 'تم إرسال الطلب بنجاح!'],
            'job_application_error' => ['en' => 'Error submitting application', 'ar' => 'خطأ في إرسال الطلب'],
            'job_link_copied' => ['en' => 'Job link copied to clipboard!', 'ar' => 'تم نسخ رابط الوظيفة!'],

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
