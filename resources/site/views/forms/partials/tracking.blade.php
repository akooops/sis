{{--
    The form funnel's marker.

    Read by resources/site/js/lib/forms/ga.js. Its PRESENCE is the switch: the
    shim no-ops when this is undefined (or when gtag never loads — a blocker, or
    no analytics integration configured), so nothing downstream has to branch.

    It emits NO TAG. The layout already loaded gtag and configured the site's one
    property; this only tells the shim which form it is looking at, so
    sisf_form_view / start / submit / complete land in that same property. A form
    used to be able to pin a different one, which made the funnel unjoinable.

    Expects: $form, and $stage — 'form' on the form itself, 'complete' on the
    confirmation page.
--}}
@if ($site->analytics()->enabled())
    <script>
        window.sisfAnalytics = @js([
            'provider' => $site->analytics()->client()['provider'] ?? null,
            'stage' => $stage,
            'form' => [
                'id' => $form->id,
                'slug' => $form->slug,
            ],
            // A failed round-trip comes back as a fresh page load, so the count
            // of server-side errors is how sisf_form_error is detected at all.
            'errors' => count($errors->all()),
        ]);
    </script>
@endif
