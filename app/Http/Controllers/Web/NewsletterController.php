<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewsletterGroupSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The unsubscribe link ShipNewsletter swaps into every newsletter body.
 *
 * THE SIGNUP IS NOT HERE. It is a seeded `is_system` builder form
 * (config('newsletter.form')) posted to the ordinary /forms/{locale}/{slug}
 * endpoint and turned into subscriber rows by
 * App\Services\Newsletter\SubscriptionProjector — so it gets the honeypot, the
 * minimum submit time, the captcha, the country and IP blocks, the per-visitor
 * caps and one submission pipeline to export and notify from, none of which a
 * bespoke route could have had without reimplementing them.
 *
 * THIS END STAYS BESPOKE because it is not a submission: it is a GET arriving
 * from somebody's inbox carrying a signature, with no form, no page and no
 * session behind it.
 *
 * IT RENDERS NO VIEW. Like the signup's confirmation, its answer is shown on
 * /newsletters — so there is one page that talks about subscriptions and no
 * /unsubscribed URL a stranger can open cold.
 */
class NewsletterController extends Controller
{
    /**
     * The signup card's id on the newsletters page.
     *
     * The redirect carries it as a fragment, because the card sits under a
     * full-viewport hero: without it a visitor lands on the artwork and the
     * confirmation that they have left the list is somewhere below the fold.
     */
    public const ANCHOR = 'newsletter-subscribe';

    /**
     * The flash key the page reads.
     *
     * Its own key rather than a shared `success`, for two reasons: nothing else
     * on this page flashes, and site::partials.ui.flash — which is what reads
     * `success` — carries data-auto-dismiss, and disclosure.js deletes anything
     * wearing it after five seconds. That is right for a transient flash and
     * wrong for the one confirmation somebody gets that they have left a list.
     */
    public const NOTICE = 'newsletter_notice';

    /**
     * Both the signature and the email must match the SAME row. The signature is
     * what makes the link unguessable; the email means a signature on its own —
     * copied from a forwarded mail, say — cannot unsubscribe somebody else.
     *
     * Still a GET, so a link-prefetching mail scanner can trigger it on the
     * recipient's behalf. The remaining fix is a POST confirmation page.
     */
    public function unsubscribe(Request $request, string $signature): RedirectResponse
    {
        $email = (string) $request->query('email');

        // Iterate: a builder update fires no events, so the observer would audit nothing.
        NewsletterGroupSubscriber::query()
            ->where('signature', $signature)
            ->where('email', $email)
            ->where('is_active', true)
            ->get()
            ->each(fn (NewsletterGroupSubscriber $subscriber) => $subscriber->update(['is_active' => false]));

        /*
         * A wrong pair, an unknown signature and an already-inactive row all get
         * this same answer: anything else would let a stranger probe the list.
         * The signup form answers the same way for the same reason — an address
         * already subscribed is shown the ordinary confirmation.
         *
         * Landing on /newsletters rather than on a bare text response is what
         * makes leaving reversible: the signup form is directly under this
         * message, so someone who unsubscribed by accident, or who only wanted
         * off ONE of several lists, can fix it without hunting for the page.
         */
        return redirect()
            ->route('web.site.newsletters')
            ->withFragment(self::ANCHOR)
            ->with(self::NOTICE, __('site.newsletters.unsubscribed'));
    }
}
