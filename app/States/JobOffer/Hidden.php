<?php

namespace App\States\JobOffer;

/**
 * Withdrawn from the public site. Unlike Draft, this job offer HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends JobOfferStatus
{
    public static string $name = 'hidden';
}
