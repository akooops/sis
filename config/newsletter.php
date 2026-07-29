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

];
