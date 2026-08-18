<?php

return [

    /*
     * The seeded reservation form, by slug. It is a system form, so its structure
     * is frozen and the map below can be a constant rather than an admin-editable
     * mapping UI — the same bargain config/jobs.php strikes.
     *
     * READ BY THE SEEDER AND THE SITE, NOT BY THE SEAM. Which projector runs for
     * which form is declared in config('forms.projectors'); that registry belongs
     * to the forms module, and this module is the second thing to use it.
     */
    'form' => 'visit-reservation',

    /*
     * THE SEAM: form answer key => visitors column.
     *
     * Forms own intake, visits own the household, and this is the only place that
     * knows both vocabularies. Answers whose key is not named here are NOT lost —
     * they stay on the FormSubmission, which the reservation points at. This map
     * says only what earns a typed column.
     */
    'fields' => [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
    ],

    /*
     * The three hidden fields the booking wizard fills in before the visitor ever
     * sees an input. They are hidden rather than absent because the submit is a
     * plain POST: the chosen service, slot and party size have to travel with the
     * answers, and a hidden field is how the forms module already carries a value
     * the page knows and the visitor does not type.
     *
     * All three are RE-VERIFIED server-side. A hidden input is a suggestion.
     */
    'service_field' => 'visit_service_id',
    'slot_field' => 'visit_slot_id',
    'visitors_field' => 'visitors_count',

    /*
     * Repeatable group => child table, then child answer key => column.
     *
     * One group today. The children are prefixed `student_` because field keys are
     * unique per FORM including group children (the DB enforces it, and the upload
     * endpoint resolves a file field by key alone) — so the group cannot reuse
     * `first_name`, which the guardian already holds. Mapping them back onto the
     * shared column names here is exactly what this map is for.
     */
    'groups' => [
        'students' => [
            'table' => App\Models\VisitAttendee::class,
            'map' => [
                'student_first_name' => 'first_name',
                'student_last_name' => 'last_name',
                'grade' => 'grade',
                'current_school' => 'current_school',
            ],
        ],
    ],

    /*
     * Remaining seats at or below this render amber rather than green.
     *
     * Cosmetic, but it is the difference between a visitor seeing "there is room"
     * and "there is nearly no room", which is what makes them book now. The
     * stylesheet has carried .fc-event.is-limited since the site was written and
     * nothing ever emitted it; VisitSlot::state() finally does.
     */
    'limited_at' => (int) env('VISITS_LIMITED_AT', 2),

    /*
     * How wide a window the public month feed will answer in one request.
     *
     * A calendar asks for the month it is showing plus the days either side that
     * fill the grid, so 62 covers any real view with room to spare. It is a cap on
     * a public, unauthenticated endpoint, not a preference.
     */
    'feed_max_days' => 62,
];
