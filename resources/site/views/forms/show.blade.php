{{--
    The standalone public form page.

    `$seo` comes from FormsController like every other page's does — these views
    used to call a mutable builder on the site context from inside a php block,
    which meant the page's <head> was decided in the view and read back by the
    layout that had already started rendering.

    The direction is the layout's job and comes from the Language row, so this
    page and the thanks/closed/blocked pages are all correct in Arabic from one
    source. They were not before — only this view passed it and the other three
    rendered LTR.

    The mount point and the whole payload live in the shared renderer partial,
    which the site's contact and admissions pages include too.
--}}
@extends('site::layout')

@section('content')
    <section>
        <div class="container py-10">
            {{-- data-sisf ONLY — no `sisf` class. FormRenderer emits its own
                 `.sisf` root inside [data-sisf-root], and nesting one inside
                 another applies the token block and its padding twice.

                 The marker is on this wrapper rather than on [data-sisf-root]
                 because the thanks page has no root but still has to load the
                 module to report the completion. --}}
            <div data-sisf>
                @include('site::forms.partials.renderer')
            </div>
        </div>
    </section>
@endsection
