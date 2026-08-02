<?php

/*
 * Public-facing form messages.
 *
 * Registered as a group in config/translations.php, so the Translations admin
 * page can edit them — which also means TranslationKeysSeeder owns the English
 * source here. Do not hand-edit the other locales expecting a reseed to keep
 * them; add the key to config/translations.php instead.
 */

return [
    'invalid_session' => 'We could not verify this form submission. Please reload the page and try again.',
    'expired' => 'This form has been open for a while. Please reload the page and submit again.',
    'closed' => 'This form is no longer accepting responses.',
    'already_submitted' => 'You have already responded to this form.',
    'blocked' => 'This form is not available from your location.',
    'submit' => 'Submit',
    'next' => 'Next',
    'back' => 'Back',
    'sending' => 'Sending…',
    'thanks_title' => 'Thank you',
    /* :reference is the submission's id — it is the only reference there is. */
    'thanks_reference' => 'Your reference is :reference.',
    'javascript_required' => 'This form needs JavaScript. Please enable it and reload the page.',
];
