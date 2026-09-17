{{--
    Newsletters: the signup, then the archive.

    SIGNUP FIRST, and it is not a taste call. The signup is a seeded builder form,
    so its confirmation renders where the embed sits — and SubmitController
    returns the visitor with back(), which carries no fragment. Anything below the
    archive table is off-screen on landing, which is exactly how the bespoke
    version of this page managed to say nothing at all after a successful signup.

    THE FORM IS A BUILDER FORM, not a native POST. Nothing on this page renders
    inputs, repopulates old() or reads an error bag: partials.forms.embed draws
    the form, its confirmation, or the reason there is neither, and the guard
    chain behind it — honeypot, minimum submit time, captcha, country and IP
    blocks, per-visitor caps — is the same one every other public form gets.

    THE UNSUBSCRIBE MESSAGE IS THE ONE THING THIS PAGE STILL SAYS ITSELF, because
    it arrives from NewsletterController rather than from a submission. It is NOT
    site::partials.ui.flash: that partial carries data-auto-dismiss, and
    disclosure.js deletes anything wearing it after five seconds — right for a
    transient flash, wrong for the confirmation that somebody has left a list.

    Everything here comes from ResourcesController::newsletters().
--}}
@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            {{--
                The id is the controller's ANCHOR constant rather than a literal:
                every redirect it makes carries that fragment, so a rename in one
                place cannot quietly stop scrolling people to the other.

                `scroll-mt-24` is load-bearing, not spacing. This is the only
                withFragment() in the app, and .site-header goes position:fixed at
                88px (--header-height-scrolled) the moment the page is scrolled at
                all — which a fragment jump does before anything else has painted.
                Without a scroll margin the browser aligns this div's top edge with
                y=0 and the unsubscribe confirmation lands entirely BEHIND the
                navbar. --spacing is 5px here, so scroll-mt-24 is 120px: the
                header plus a little air.
            --}}
            <div id="{{ \App\Http\Controllers\Web\NewsletterController::ANCHOR }}" class="mb-8 scroll-mt-24">
                @if (session(\App\Http\Controllers\Web\NewsletterController::NOTICE))
                    <div class="alert alert-success mb-4 flex items-center gap-2" role="alert">
                        <i class="uil uil-check-circle" aria-hidden="true"></i>
                        <span class="flex-1">{{ session(\App\Http\Controllers\Web\NewsletterController::NOTICE) }}</span>
                    </div>
                @endif

                @if ($form)
                    <div class="card">
                        <div class="card-header">
                            <button type="button" class="panel-toggle" data-toggle="collapse"
                                data-target="#newsletter-subscribe-panel" aria-expanded="true"
                                aria-controls="newsletter-subscribe-panel">
                                {{ $form->getTranslation('title', $site->locale(), true) ?: $form->name }}
                            </button>
                        </div>

                        {{-- Rendered OPEN and left open. A rejected submit arrives
                             as a fresh page load, and a panel that started closed
                             would seal the visitor's answers and their field
                             errors out of sight — and FormRenderer's
                             scroll-to-first-error is a no-op inside a
                             height:0;overflow:hidden panel. --}}
                        <div id="newsletter-subscribe-panel" class="collapse-panel is-open" aria-hidden="false">
                            <div>
                                <div class="card-body">
                                    {{--
                                        ONLY WHEN THE FORM IS ACTUALLY DRAWN. The
                                        embed is a three-way branch — confirmation,
                                        notice, or form — and `$presentation` is
                                        non-null in exactly the third case.
                                        Unconditional, this paragraph told a
                                        visitor to "choose a list and enter your
                                        email address" directly above their own
                                        confirmation that they had, and printed the
                                        unsubscribe sentence twice running; above a
                                        `closed` notice it invited them to fill in
                                        a form that was not there.
                                    --}}
                                    @if ($presentation)
                                        <p class="mb-6">@lang('site.newsletters.subscribe.intro')</p>
                                    @endif

                                    @include('site::partials.forms.embed')
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <button type="button" class="panel-toggle" data-toggle="collapse" data-target="#newsletters-panel"
                        aria-expanded="true" aria-controls="newsletters-panel">
                        @lang('site.newsletters.panel')
                    </button>
                </div>

                <div id="newsletters-panel" class="collapse-panel is-open" aria-hidden="false">
                    <div>
                        <div class="card-body">
                            @if ($newsletters->isEmpty())
                                <p class="text-muted mb-0">@lang('site.guidelines.empty')</p>
                            @else
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>@lang('site.table.file')</th>
                                            <th>@lang('common.file_size')</th>
                                            <th>@lang('site.table.action')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($newsletters as $newsletter)
                                            @php($file = $newsletter->getMedia(\App\Models\Newsletter::FILE_COLLECTION)->first())

                                            <tr>
                                                <td>{{ $newsletter->getTranslation('title', $site->locale(), true) ?: $newsletter->name }}</td>
                                                <td>{{ $file ? round($file->size / 1024) . ' KB' : '—' }}</td>
                                                <td>
                                                    @if ($file)
                                                        <a class="btn btn-sm" href="{{ $file->url }}" download>
                                                            <i class="uil uil-download-alt" aria-hidden="true"></i>
                                                            @lang('common.download')
                                                        </a>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
