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
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3491.184195429717!2d46.614758599999995!3d24.7514823!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2ee3a97f2ef8f9%3A0x191e451556a20c7a!2z2YXYr9in2LHYsyDYs9i52YjYryDYp9mE2LnYp9mE2YXZitipIHwgU2F1ZCBJbnRlcm5hdGlvbmFsIFNjaG9vbA!5e1!3m2!1sen!2sdz!4v1754580709950!5m2!1sen!2sdz',
                'description' => 'Google Maps url that will be used in the map embed',
                'group' => 'google',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'google_maps_url',
                'type' => 'text',
                'value' => 'https://maps.app.goo.gl/2wzChEohJMe4BMp86',
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

            // Academic Pathways
            [
                'key' => 'pathway_american_url',
                'type' => 'text',
                'value' => '',
                'description' => 'URL for the American academic pathway page',
                'group' => 'pathway',
                'is_encrypted' => false,
                'options' => null,
            ],
            [
                'key' => 'pathway_british_url',
                'type' => 'text',
                'value' => '',
                'description' => 'URL for the British academic pathway page',
                'group' => 'pathway',
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
