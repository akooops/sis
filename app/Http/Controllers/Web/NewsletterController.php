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
    public function unsubscribe(Request $request): Response
    {
        // Known limitation: the link carries only an email, so anyone can unsubscribe
        // anyone and a link-prefetching mail scanner can do it for the recipient —
        // move to URL::signedRoute or a per-subscriber token when this stops being a stub.
        $email = $request->validate(['email' => ['required', 'email']])['email'];

        // Iterate: a builder update fires no events, so the observer would audit nothing.
        $subscribers = NewsletterGroupSubscriber::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->get();

        foreach ($subscribers as $subscriber) {
            $subscriber->update(['is_active' => false]);
        }

        // Unknown and already-inactive addresses get this same answer: a 404 would
        // tell a stranger which addresses are on our lists.
        return response('You have been unsubscribed and will no longer receive these emails.')
            ->header('Content-Type', 'text/plain');
    }
}
