<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Nationality;
use Illuminate\Database\Seeder;

class NationalitySeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::all()->keyBy('code');

        $nationalities = [
            'SA' => ['en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية'],
            'AE' => ['en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة'],
            'EG' => ['en' => 'Egypt', 'ar' => 'مصر'],
            'TR' => ['en' => 'Turkey', 'ar' => 'تركيا'],
            'US' => ['en' => 'United States', 'ar' => 'الولايات المتحدة'],
            'GB' => ['en' => 'United Kingdom', 'ar' => 'المملكة المتحدة'],
            'CA' => ['en' => 'Canada', 'ar' => 'كندا'],
            'FR' => ['en' => 'France', 'ar' => 'فرنسا'],
            'DE' => ['en' => 'Germany', 'ar' => 'ألمانيا'],
            'IT' => ['en' => 'Italy', 'ar' => 'إيطاليا'],
            'ES' => ['en' => 'Spain', 'ar' => 'إسبانيا'],
            'PT' => ['en' => 'Portugal', 'ar' => 'البرتغال'],
            'NL' => ['en' => 'Netherlands', 'ar' => 'هولندا'],
            'BE' => ['en' => 'Belgium', 'ar' => 'بلجيكا'],
            'CH' => ['en' => 'Switzerland', 'ar' => 'سويسرا'],
            'SE' => ['en' => 'Sweden', 'ar' => 'السويد'],
            'NO' => ['en' => 'Norway', 'ar' => 'النرويج'],
            'DK' => ['en' => 'Denmark', 'ar' => 'الدنمارك'],
            'FI' => ['en' => 'Finland', 'ar' => 'فنلندا'],
            'RU' => ['en' => 'Russia', 'ar' => 'روسيا'],
            'UA' => ['en' => 'Ukraine', 'ar' => 'أوكرانيا'],
            'GR' => ['en' => 'Greece', 'ar' => 'اليونان'],
            'PL' => ['en' => 'Poland', 'ar' => 'بولندا'],
            'RO' => ['en' => 'Romania', 'ar' => 'رومانيا'],
            'BG' => ['en' => 'Bulgaria', 'ar' => 'بلغاريا'],
            'IN' => ['en' => 'India', 'ar' => 'الهند'],
            'PK' => ['en' => 'Pakistan', 'ar' => 'باكستان'],
            'BD' => ['en' => 'Bangladesh', 'ar' => 'بنغلاديش'],
            'CN' => ['en' => 'China', 'ar' => 'الصين'],
            'JP' => ['en' => 'Japan', 'ar' => 'اليابان'],
            'KR' => ['en' => 'South Korea', 'ar' => 'كوريا الجنوبية'],
            'ID' => ['en' => 'Indonesia', 'ar' => 'إندونيسيا'],
            'MY' => ['en' => 'Malaysia', 'ar' => 'ماليزيا'],
            'TH' => ['en' => 'Thailand', 'ar' => 'تايلاند'],
            'PH' => ['en' => 'Philippines', 'ar' => 'الفلبين'],
            'VN' => ['en' => 'Vietnam', 'ar' => 'فيتنام'],
            'AU' => ['en' => 'Australia', 'ar' => 'أستراليا'],
            'NZ' => ['en' => 'New Zealand', 'ar' => 'نيوزيلندا'],
            'ZA' => ['en' => 'South Africa', 'ar' => 'جنوب أفريقيا'],
            'NG' => ['en' => 'Nigeria', 'ar' => 'نيجيريا'],
            'MA' => ['en' => 'Morocco', 'ar' => 'المغرب'],
            'DZ' => ['en' => 'Algeria', 'ar' => 'الجزائر'],
            'TN' => ['en' => 'Tunisia', 'ar' => 'تونس'],
            'LY' => ['en' => 'Libya', 'ar' => 'ليبيا'],
            'SD' => ['en' => 'Sudan', 'ar' => 'السودان'],
            'QA' => ['en' => 'Qatar', 'ar' => 'قطر'],
            'KW' => ['en' => 'Kuwait', 'ar' => 'الكويت'],
            'BH' => ['en' => 'Bahrain', 'ar' => 'البحرين'],
            'OM' => ['en' => 'Oman', 'ar' => 'عمان'],
            'JO' => ['en' => 'Jordan', 'ar' => 'الأردن'],
            'LB' => ['en' => 'Lebanon', 'ar' => 'لبنان'],
            'SY' => ['en' => 'Syria', 'ar' => 'سوريا'],
            'IQ' => ['en' => 'Iraq', 'ar' => 'العراق'],
            'YE' => ['en' => 'Yemen', 'ar' => 'اليمن'],
            'PS' => ['en' => 'Palestine', 'ar' => 'فلسطين'],
            'IR' => ['en' => 'Iran', 'ar' => 'إيران'],
            'BR' => ['en' => 'Brazil', 'ar' => 'البرازيل'],
            'AR' => ['en' => 'Argentina', 'ar' => 'الأرجنتين'],
            'MX' => ['en' => 'Mexico', 'ar' => 'المكسيك'],
            'CL' => ['en' => 'Chile', 'ar' => 'تشيلي'],
            'CO' => ['en' => 'Colombia', 'ar' => 'كولومبيا'],
        ];

        foreach ($nationalities as $code => $translations) {
            $nationality = Nationality::updateOrCreate(
                ['code' => $code],
                ['name' => $translations['en']]
            );

            foreach ($languages as $languageCode => $language) {
                $value = $translations[$languageCode] ?? $translations['en'];
                $nationality->setTranslation('title', $languageCode, $value);
            }
        }
    }
}
