<?php

return [

    /*
     * The two seeded forms, by slug. Both are system forms, so their structure is
     * frozen and the map below can be a constant rather than an admin-editable
     * mapping UI — the same bargain config/jobs.php and config/visits.php strike.
     *
     * TWO FORMS, TWO PAGES. /facilities/{slug}/contact carries one and
     * /facilities/{slug}/reserve the other, because the public form renderer emits
     * one payload per page: fixed script ids and a single mount root. Splitting
     * the URLs is what keeps this module from having to generalise the forms
     * module — and it is also just the clearer site, since a person arrives
     * wanting to do one of the two things.
     */
    'reservation_form' => 'facility-reservation',
    'contact_form' => 'facility-contact',

    /*
     * THE SEAM: form answer key => visitors column.
     *
     * The SAME `visitors` table the visits module writes, matched the same way,
     * so one person booking a school tour and a hall is one contact record.
     * Answers whose key is not named here are NOT lost — they stay on the
     * FormSubmission, which the reservation points at.
     */
    'fields' => [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
    ],

    /*
     * The hidden fields the pages fill in before the visitor sees an input.
     *
     * `facility_id` is a server-side preset on both forms — the page IS one venue,
     * so it knows. `facility_slot_id` cannot be, because which time is chosen
     * happens in the browser after the page has rendered; site/facilities.js hands
     * it to the renderer as an initial value.
     *
     * Both are RE-VERIFIED server-side. A hidden input is a suggestion.
     */
    'facility_field' => 'facility_id',
    'slot_field' => 'facility_slot_id',

    /*
     * Remaining seats at or below this render amber rather than green — the same
     * knob visits carries, and the difference between a visitor reading "there is
     * room" and "there is nearly no room".
     */
    'limited_at' => (int) env('FACILITIES_LIMITED_AT', 2),

    /*
     * How wide a window the public month feed will answer in one request. A
     * calendar asks for the month it shows plus the days either side that fill the
     * grid, so 62 covers any real view. It is a cap on a public, unauthenticated
     * endpoint, not a preference.
     */
    'feed_max_days' => 62,
];
