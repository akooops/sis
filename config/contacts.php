<?php

return [

    /*
     * The contact-detail catalogue — the kinds of contact a footer, a contact
     * page or a map card can hold. This array is the source of truth; there is
     * no table mirroring it, because a mirror drifts the moment someone deploys
     * without reseeding and the form would then offer a type the server has no
     * rule for. Grown by code, never CRUD: add a row here.
     *
     * `code` is the array key — what lands in contact_details.type; `name`/`icon`
     * are display, `sort` orders the type picker.
     *
     * `value` names the SHAPE of contact_details.value, and drives both halves at
     * once: the rule the server applies and the input the form renders.
     *   phone -> E164, validated by App\Rules\PhoneNumber
     *   email -> an address
     *   url   -> an absolute link
     *   null  -> the type carries no scalar value at all. `address` is the one
     *            such type, and its text lives translated in the `address` column.
     */
    'types' => [
        'phone' => ['name' => 'Phone', 'icon' => 'ki-phone', 'value' => 'phone', 'sort' => 1],
        'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'ki-whatsapp', 'value' => 'phone', 'sort' => 2],
        'email' => ['name' => 'Email', 'icon' => 'ki-sms', 'value' => 'email', 'sort' => 3],
        'address' => ['name' => 'Address', 'icon' => 'ki-geolocation', 'value' => null, 'sort' => 4],
        'social' => ['name' => 'Social', 'icon' => 'ki-social-media', 'value' => 'url', 'sort' => 5],
    ],

    /*
     * The networks a social row may name. A closed list rather than free text:
     * the admin picks a code, so two rows cannot spell Instagram differently and
     * the public site can style by network.
     *
     * The icon is derived here rather than chosen per row — a network has one
     * mark, and asking the admin to pick it again on every row is a question with
     * one right answer. It is also why nothing renders admin-supplied text into a
     * class attribute.
     *
     * `ki-social-media` is the stand-in for a network keenicons ships no glyph
     * for; add a row here to grow the list.
     */
    'platforms' => [
        'facebook' => ['name' => 'Facebook', 'icon' => 'ki-facebook'],
        'instagram' => ['name' => 'Instagram', 'icon' => 'ki-instagram'],
        'twitter' => ['name' => 'X', 'icon' => 'ki-twitter'],
        'youtube' => ['name' => 'YouTube', 'icon' => 'ki-youtube'],
        'linkedin' => ['name' => 'LinkedIn', 'icon' => 'ki-social-media'],
        'tiktok' => ['name' => 'TikTok', 'icon' => 'ki-tiktok'],
        'snapchat' => ['name' => 'Snapchat', 'icon' => 'ki-snapchat'],
        'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'ki-whatsapp'],
        'behance' => ['name' => 'Behance', 'icon' => 'ki-behance'],
        'dribbble' => ['name' => 'Dribbble', 'icon' => 'ki-dribbble'],
    ],
];
