<?php

return [

    /*
     * The fallback mailing list. NewsletterGroupsSeeder keys its firstOrCreate on
     * this code, so a reseed finds the row it created last time instead of adding
     * a second one; the name and the public copy of that row stay editable.
     *
     * Which list is actually the default is the `is_default` flag, not this code:
     * NewsletterGroup::default() reads the flag, NewsletterGroupObserver keeps
     * exactly one row carrying it, and the controller refuses to delete that row.
     */
    'default_group' => env('NEWSLETTER_DEFAULT_GROUP', 'general'),

    /*
     * THE SEAM BETWEEN THE SIGNUP FORM AND THIS DOMAIN — the twin of
     * config/jobs.php and config/visits.php.
     *
     * The signup is a seeded `is_system` Form, so its slug and its field keys are
     * frozen and a constant map is safe; the WIRING that makes a completed
     * submission become subscriber rows is the one line in
     * config('forms.projectors'), not here.
     */
    'form' => 'newsletter-subscribe',

    'fields' => [
        'email' => 'email',

        /*
         * The multi-value picker. Its options are `form_field_options` rows kept
         * in step with `newsletter_groups` by App\Services\Newsletter\FormOptions
         * — the seeder can only write them once, so an admin adding a list later
         * depends on that sync. Each stored answer is a group's `code`.
         */
        'groups' => 'newsletter_groups',
    ],

];
