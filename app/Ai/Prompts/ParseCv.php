<?php

namespace App\Ai\Prompts;

use App\Ai\Prompt;

/**
 * Pull a CV apart into the answers the application form asks for.
 *
 * THE CV IS ATTACHED, NOT PASTED. This prompt carries no text of its own: the
 * file goes to the provider as its own content part and the provider reads it —
 * see App\Contracts\Integrations\ReadsDocuments. Extracting the text here first
 * is what this replaced, and it returned nothing usable for most real CVs.
 * That is also why user() says almost nothing about the document itself: the
 * model is looking at the pages, so describing them would be inventing detail.
 *
 * PREFILL, NEVER SUBMIT. What comes back is put in front of the applicant to
 * check and correct — it is a typing aid, not a decision. A model that misreads a
 * date must cost someone ten seconds, not their application.
 *
 * The shape returned is FLAT and keyed by the form's own field keys, because the
 * island writes it straight into the renderer's initial values. Repeats come back
 * as JSON strings and are decoded by the caller: asking a model for nested
 * objects inside a JSON object is where they most often produce something almost
 * right, and a string that fails to parse is a cheap, obvious failure.
 */
class ParseCv extends Prompt
{
    public function system(): string
    {
        return <<<'TEXT'
        You read a CV and extract what it actually says.

        Copy, do not infer. If the CV does not give a value, leave it empty — a
        blank the applicant fills in themselves is far better than a plausible
        guess they do not notice and do not correct.

        Dates are YEARS ONLY, as four digits. "Present", "current" and "now" mean
        the role is ongoing: leave its end year empty and set is_current true.

        Names of employers, schools and qualifications keep the CV's own wording.
        Do not translate them, expand abbreviations, or tidy them up.

        Nationality is the ONE exception to copying: answer with the ISO 3166-1
        alpha-2 code in capitals — "Saudi", "saoudien" and "سعودي" are all SA.
        The form offers a fixed list of countries and anything else matches
        nothing in it. If the CV does not say, leave it empty.
        TEXT;
    }

    public function user(): string
    {
        return 'Extract the CV attached to this message. Read the file itself — there is no copy of its text here.'
            .$this->section('The school receiving it', $this->school())
            .$this->section('The repeated sections', static::repeatShapes());
    }

    /**
     * Keys mirror the seeded form's field keys — see config('jobs.fields') and
     * config('jobs.groups'). The three repeats are JSON strings; the endpoint
     * decodes them before handing anything to the browser.
     *
     * @return array<string, string>
     */
    public function schema(): array
    {
        return [
            'first_name' => 'string',
            'last_name' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'address' => 'string',
            'nationality' => 'string',
            'skills' => 'string[]',
            'education' => 'string',
            'experience' => 'string',
            'languages' => 'string',
        ];
    }

    /**
     * What each repeat string must contain.
     *
     * Interpolated into user() rather than declared in schema(), which only
     * carries scalar shapes — so this is the only place the model is told which
     * keys CvParser::shape() will hand to the renderer, and it has to be said or
     * the decoded rows come back keyed however the model felt like keying them.
     */
    public static function repeatShapes(): string
    {
        return <<<'TEXT'
        "education", "experience" and "languages" must each be a JSON ARRAY encoded
        as a STRING, with these keys and no others:

          education:  institution, degree, field_of_study, start_year, end_year, achievements
          experience: company_name, job_title, from_year, to_year, is_current, responsibilities
          languages:  name, proficiency   (proficiency is one of basic, intermediate, advanced, native)

        Use "[]" when the CV says nothing about one of them.
        TEXT;
    }
}
