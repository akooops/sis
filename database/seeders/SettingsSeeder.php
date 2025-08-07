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
            // Custom codes
            [
                'key' => 'head_code',
                'type' => 'text',
                'value' => '',
                'description' => 'Code to be added to the head of the page',
                'group' => 'code',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'foot_code',
                'type' => 'text',
                'value' => '',
                'description' => 'Code to be added to the foot of the page',
                'group' => 'code',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'support_button_code',
                'type' => 'text',
                'value' => '',
                'description' => 'Code to be added to the support button',
                'group' => 'code',
                'is_encrypted' => false,
                'options' => null,
            ],

            // Google Settings
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
                'key' => 'google_maps_embed_url',
                'type' => 'text',
                'value' => 'https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3491.1677000940404!2d46.61173627536739!3d24.752069478000166!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjTCsDQ1JzA3LjUiTiA0NsKwMzYnNTEuNSJF!5e1!3m2!1sen!2sdz!4v1754475328828!5m2!1sen!2sdz',
                'description' => 'Google Maps url that will be used in the map embed',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'google_maps_url',
                'type' => 'text',
                'value' => 'https://maps.app.goo.gl/jm83RmQLtMY2sRee7',
                'description' => 'Google Maps url that will be used in the map link',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],


            // Social Media URLs
            [
                'key' => 'social_facebook_url',
                'type' => 'text',
                'value' => 'https://www.facebook.com/saudInternationalschool',
                'description' => 'Facebook page URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_instagram_url',
                'type' => 'text',
                'value' => 'https://www.instagram.com/saudinternationalschool',
                'description' => 'Instagram profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_twitter_url',
                'type' => 'text',
                'value' => 'https://twitter.com/saud_school',
                'description' => 'Twitter profile URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_youtube_url',
                'type' => 'text',
                'value' => 'https://www.youtube.com/channel/UC523jdlxy5Nwiht1LgX-X0w',
                'description' => 'YouTube channel URL',
                'group' => 'social',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'social_linkedin_url',
                'type' => 'text',
                'value' => 'https://www.linkedin.com/company/saud-international-school-riyadh',
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
                'value' => 'Saud International School, Abi Al Abbas Al Shafei Street, Hiteen District, Riyadh, Kingdom of Saudi Arabia.',
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
