<?php

namespace App\States\Newsletter;

/** Terminal: nothing transitions out of Sent. */
class Sent extends NewsletterStatus
{
    public static string $name = 'sent';
}
