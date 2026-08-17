<?php

namespace App\Contracts\Forms;

use App\Models\FormSubmission;

/**
 * What a completed submission BECOMES once the form's job is done.
 *
 * THE SEAM BETWEEN FORMS AND A DOMAIN, and the reason it is an interface rather
 * than a line in a job: a form collects, a domain means something. Job applications
 * were the first consumer; a visit reservation is the same shape — take the
 * captcha, the honeypot, the country blocks, the multi-step builder-rendered form
 * and the file handling from the forms module, and once a submission is COMPLETE,
 * turn it into the thing the business actually tracks.
 *
 * Registered by form slug in config('forms.projectors'), so adding a domain is a
 * class plus a config line and nothing in the forms module changes.
 *
 * CALLED AFTER THE RESPONSE HAS GONE, from ProcessFormSubmission — never from
 * FormSubmissionObserver, which fires inside the submit transaction and would hold
 * a write lock across the whole domain write while the visitor waits.
 *
 * Two guarantees an implementation may rely on, and one it must provide:
 *  - it only ever sees a `completed` submission (spam, abandoned drafts and
 *    validation failures never reach it — the fake-confirmation path deliberately
 *    writes a `spam` row that must not become a person or a booking);
 *  - a throw is caught and logged, not fatal, because the answers are already
 *    stored and losing the notification and the webhook over a projection failure
 *    would be worse than the failure;
 *  - IT MUST BE IDEMPOTENT. It is re-runnable by design — that is what makes a
 *    failed projection repairable and a mapping change fixable over old rows —
 *    so running it twice over one submission must produce one result, not two.
 */
interface SubmissionProjector
{
    /**
     * Project one completed submission into its domain.
     *
     * @return mixed whatever the domain row is, or null when there was nothing
     *               to project — the caller does not inspect it.
     */
    public function project(FormSubmission $submission): mixed;
}
