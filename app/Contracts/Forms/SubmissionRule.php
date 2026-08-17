<?php

namespace App\Contracts\Forms;

use App\Models\Form;

/**
 * Extra submit-time rules a particular form needs, which its FIELDS cannot
 * express because they depend on more than one answer or on another table.
 *
 * The case this exists for: a job application must be refused when this
 * candidate has already applied to THIS posting, and when the posting has
 * closed. Neither is a property of a field — the first needs the email and the
 * hidden posting id together, the second needs a row in job_offers — so neither
 * can come from the FieldType registry.
 *
 * IT RETURNS RULES RATHER THAN THROWING, so a failure is an ordinary validation
 * error against a real field path and the visitor sees it under the input they
 * can fix. The alternative was letting the submission succeed and the domain
 * projection fail behind it, which tells the applicant nothing and leaves a
 * completed submission that never became an application.
 *
 * Wired by SLUG in config('forms.submission_rules'), not by a column: which
 * rules a form needs is application code, and a database row pointing at a class
 * name is a deployment that can break a live form.
 */
interface SubmissionRule
{
    /**
     * Rules keyed by their full validator path — `fields.<key>`, exactly as
     * SubmissionValidator builds them — merged onto whatever the fields already
     * declared rather than replacing it.
     *
     * $answers is the visitor's submitted map, already compacted (blank repeat
     * rows dropped), so a rule reading one answer to decide about another sees
     * what will actually be stored.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, array<int, mixed>>
     */
    public function rules(Form $form, array $answers): array;
}
