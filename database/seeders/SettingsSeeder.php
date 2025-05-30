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
                'value' => '',
                'description' => 'Google Maps url',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],

            // Menu Settings
            [
                'key' => 'header_primary_menu',
                'type' => 'menu',
                'value' => '',
                'description' => 'Primary menu displayed in header',
                'group' => 'menus',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'footer_primary_menu',
                'type' => 'menu',
                'value' => '',
                'description' => 'Primary menu displayed in footer',
                'group' => 'menus',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'footer_secondary_menu',
                'type' => 'menu',
                'value' => '',
                'description' => 'Secondary menu displayed in footer',
                'group' => 'menus',
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
                'key' => 'index_page_second_section_cta_page',
                'type' => 'page',
                'value' => '',
                'description' => 'Page linked to second section CTA button',
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
