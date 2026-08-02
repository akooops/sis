<?php

return [

    /*
     * The type catalogue — the capability slots the admin can configure an
     * integration under. This array is the source of truth; IntegrationTypesSeeder
     * mirrors it into the integration_types table. Adding a new type is: add a row
     * here, add a driver below, reseed. No core edits.
     */
    'types' => [
        'email' => ['name' => 'Email', 'icon' => 'ki-sms', 'sort' => 1],
        'sms' => ['name' => 'SMS', 'icon' => 'ki-message-text', 'sort' => 2],
        'ai' => ['name' => 'AI', 'icon' => 'ki-abstract-26', 'sort' => 3],
        'captcha' => ['name' => 'Bot protection', 'icon' => 'ki-shield-tick', 'sort' => 4],
        'analytics' => ['name' => 'Analytics', 'icon' => 'ki-chart-line-up', 'sort' => 5],
    ],

    /*
     * The registered drivers (vendor implementations). Each class self-describes
     * its type/code/label/schema and does the real work (send/generate).
     * App\Services\Integrations\Registry builds a code => class map from this list;
     * IntegrationDriversSeeder mirrors them into the integration_drivers table.
     * A new driver is one class + one line here. Drivers are intentionally thin —
     * optimise them later without touching the framework around them.
     */
    'drivers' => [
        App\Services\Integrations\Drivers\SmtpDriver::class,
        App\Services\Integrations\Drivers\TwilioEmailDriver::class,
        App\Services\Integrations\Drivers\FourJawalyDriver::class,
        App\Services\Integrations\Drivers\OpenAiDriver::class,
        App\Services\Integrations\Drivers\RecaptchaDriver::class,
        App\Services\Integrations\Drivers\GoogleAnalyticsDriver::class,
    ],

    /*
     * Outbound HTTP timeout (seconds) for driver calls (SMS/AI providers).
     */
    'timeout' => (int) env('INTEGRATIONS_TIMEOUT', 15),
];
