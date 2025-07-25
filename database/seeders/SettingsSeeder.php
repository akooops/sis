<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            [
                'key' => 'google_recaptcha_public_key',
                'type' => 'text',
                'value' => '',
                'description' => 'Google reCAPTCHA public/site key',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'google_recaptcha_secret_key',
                'type' => 'text',
                'value' => '',
                'description' => 'Google reCAPTCHA secret key',
                'group' => 'google',
                'is_encrypted' => true,
                'options' => null,
            ],
            [
                'key' => 'google_analytics_id',
                'type' => 'text',
                'value' => '',
                'description' => 'Google Analytics tracking ID (e.g., GA-XXXXXXXXX-X)',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'google_maps_url',
                'type' => 'text',
                'value' => 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14498.930662207516!2d46.69119!3d24.701715!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x5bd680a41f772144!2sSaud%20International%20School!5e0!3m2!1sen!2sus!4v1662044871512!5m2!1sen!2sus',
                'description' => 'Google Maps url',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],

            // Social Media URLs
            [
                'key' => 'social_facebook_url',
                'type' => 'text',
                'value' => '',
                'description' => 'Facebook page URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_instagram_url',
                'type' => 'text',
                'value' => '',
                'description' => 'Instagram profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_twitter_url',
                'type' => 'text',
                'value' => '',
                'description' => 'Twitter profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_youtube_url',
                'type' => 'text',
                'value' => '',
                'description' => 'YouTube channel URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_linkedin_url',
                'type' => 'text',
                'value' => '',
                'description' => 'LinkedIn profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_snapchat_url',
                'type' => 'text',
                'value' => '',
                'description' => 'Snapchat profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            // Index Page CTA Pages
            [
                'key' => 'index_page_first_section_cta_page',
                'type' => 'page',
                'value' => '',
                'description' => 'Page linked to first section CTA button',
                'group' => 'homepage',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'index_page_third_section_cta_page',
                'type' => 'page',
                'value' => '',
                'description' => 'Page linked to third section CTA button',
                'group' => 'homepage',
                'is_encrypted' => false,
                'options' => null,
            ],

            // Contact Information
            [
                'key' => 'emails',
                'type' => 'array',
                'value' => json_encode(['enquiries@sis.edu.sa']),
                'description' => 'Contact email addresses',
                'group' => 'contact',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'phones',
                'type' => 'array',
                'value' => json_encode(['(966) 920002877']),
                'description' => 'Contact phone numbers',
                'group' => 'contact',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'address',
                'type' => 'text',
                'value' => 'Hamdan Street, Sulaimaniyah, Riyadh Kingdom of Saudi Arabia',
                'description' => 'School physical address',
                'group' => 'contact',
                'is_encrypted' => false,
                'options' => null,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'type' => $setting['type'],
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'group' => $setting['group'],
                    'is_encrypted' => $setting['is_encrypted'],
                    'options' => $setting['options'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
