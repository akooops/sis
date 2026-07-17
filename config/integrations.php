<?php

return [

    /*
     * The type catalogue — the capability slots the admin can configure a
     * provider under (Email, SMS, later WhatsApp/OAuth/AI…). This array is the
     * source of truth; database/seeders/ProviderTypesSeeder mirrors it into the
     * provider_types table (for FK integrity + fast counts). Adding a new type
     * is: add a row here, add a driver below, reseed. No core edits.
     */
    'types' => [
        'email' => ['name' => 'Email', 'icon' => 'ki-sms', 'sort' => 1],
        'sms' => ['name' => 'SMS', 'icon' => 'ki-message-text', 'sort' => 2],
        // future: 'whatsapp', 'oauth', 'ai', 'captcha', 'analytics'.
    ],

    /*
     * The registered drivers (vendor implementations). Each class self-describes
     * its type/code/label/schema and does the real work (test/send).
     * App\Services\Integrations\IntegrationRegistry builds a code => class map
     * from this list and resolves via the container — no match, no core edit to
     * add a vendor. A new driver is one class + one line here.
     */
    'drivers' => [
        App\Services\Integrations\Drivers\SmtpDriver::class,
        App\Services\Integrations\Drivers\FourJawalyDriver::class,
    ],

    /*
     * Connection-test guards. The test endpoints reach out to third-party hosts
     * on demand, so they are throttled (per minute, per admin) and time-limited
     * so a hung provider can't tie up a worker.
     */
    'test' => [
        'timeout' => (int) env('INTEGRATIONS_TEST_TIMEOUT', 5),
        'connect_timeout' => (int) env('INTEGRATIONS_TEST_CONNECT_TIMEOUT', 3),
    ],

    /*
     * Provider credentials are stored with Laravel's `encrypted:array` cast,
     * keyed by APP_KEY (AES-256). NOTE (Laravel 10): rotating APP_KEY makes every
     * stored credential undecryptable — there is no APP_PREVIOUS_KEYS fallback
     * until Laravel 11. After an APP_KEY rotation, secrets must be re-entered.
     */

];
