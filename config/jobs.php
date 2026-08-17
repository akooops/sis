<?php

return [

    /*
     * The seeded application form, by slug. It is a system form, so its structure
     * is frozen and the map below can be a constant rather than an admin-editable
     * mapping UI — which is the whole reason the seam is cheap.
     *
     * READ BY THE SEEDER AND THE SITE, NOT BY THE SEAM. Which projector runs for
     * which form is declared in config('forms.projectors') — that registry belongs
     * to the forms module, because it is the mechanism a second domain (a visit
     * reservation, say) reuses without touching anything here.
     */
    'form' => 'job-application',

    /*
     * THE SEAM: form answer key => domain column.
     *
     * Forms own intake, jobs own the candidate, and this is the only place that
     * knows both vocabularies. Everything the projector writes it reads through
     * here, so renaming a question means editing one line rather than hunting
     * through a service.
     *
     * Answers whose key is not named here are NOT lost — they stay on the
     * FormSubmission, which the application row points at. This map says only
     * what gets a typed column, and a typed column is what shortlist filters and
     * the scorer can actually use.
     */
    'fields' => [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
        'address' => 'address',
    ],

    /* The hidden field carrying which posting is being applied to. */
    'job_offer_field' => 'job_offer_id',

    /* Nationality: a country code or id, resolved to countries.id. */
    'nationality_field' => 'nationality',

    /* The CV upload — a media id, which becomes candidates.cv_media_id. */
    'cv_field' => 'cv',

    /* The tag list. */
    'skills_field' => 'skills',

    /*
     * Repeatable groups => their child key => the column on the child table.
     *
     * Child key and column name are the same here because we seed the form and
     * chose both; the map is written out anyway so a future rename has somewhere
     * to be recorded rather than becoming a silent mismatch.
     */
    'groups' => [
        'education' => [
            'table' => App\Models\CandidateEducation::class,
            'map' => [
                'institution' => 'institution',
                'degree' => 'degree',
                'field_of_study' => 'field_of_study',
                'start_year' => 'start_year',
                'end_year' => 'end_year',
                'achievements' => 'description',
            ],
        ],
        'experience' => [
            'table' => App\Models\CandidateExperience::class,
            'map' => [
                'company_name' => 'company_name',
                'job_title' => 'job_title',
                // The form keys differ from the columns HERE and only here:
                // form field keys are unique per FORM, so a second group cannot
                // reuse `start_year` after education has taken it. This is what
                // the map is for.
                'from_year' => 'start_year',
                'to_year' => 'end_year',
                // No `is_current`: the form stopped asking it. An ongoing role is
                // one with no end year, and Candidate::yearsOfExperience() already
                // counts a missing end year up to today — so the column stays,
                // simply never set from an application.
                'responsibilities' => 'description',
            ],
        ],
        'languages' => [
            'table' => App\Models\CandidateLanguage::class,
            'map' => [
                'name' => 'name',
                'proficiency' => 'proficiency',
            ],
        ],
    ],

    /*
     * WHERE THE SCHOOL IS, in the scorer's own words.
     *
     * Kept as configuration rather than buried in a prompt class so it can be
     * corrected without a deploy, and so the standing context every scoring call
     * carries is legible in one place.
     */
    'context' => env('JOBS_AI_CONTEXT', 'An international school in Riyadh, Saudi Arabia, teaching an international curriculum.'),

    /*
     * A match below this is not put in front of a manager. Mirrors
     * CandidateMatch::SHORTLIST_THRESHOLD; set here so HR can tune it.
     */
    'shortlist_threshold' => (int) env('JOBS_SHORTLIST_THRESHOLD', 60),

];
