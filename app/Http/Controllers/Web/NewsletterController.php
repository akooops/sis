<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewsletterGroupSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public target for the unsubscribe link ShipNewsletter swaps into every body.
 * Deliberately bare — a stub the public site will dress up later.
 */
class NewsletterController extends Controller
{
    /**
     * Both the signature and the email must match the SAME row. The signature is
     * what makes the link unguessable; the email means a signature on its own —
     * copied from a forwarded mail, say — cannot unsubscribe somebody else.
     *
     * Still a GET, so a link-prefetching mail scanner can trigger it on the
     * recipient's behalf. The remaining fix is a POST confirmation page.
     */
    public function unsubscribe(Request $request, string $signature): Response
    {
        $email = (string) $request->query('email');

        // Iterate: a builder update fires no events, so the observer would audit nothing.
        NewsletterGroupSubscriber::query()
            ->where('signature', $signature)
            ->where('email', $email)
            ->where('is_active', true)
            ->get()
            ->each(fn (NewsletterGroupSubscriber $subscriber) => $subscriber->update(['is_active' => false]));

        // A wrong pair, an unknown signature and an already-inactive row all get
        // this same answer: anything else would let a stranger probe the list.
        return response('You have been unsubscribed and will no longer receive these emails.')
            ->header('Content-Type', 'text/plain');
    }
}
