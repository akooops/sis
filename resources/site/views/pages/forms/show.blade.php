{{--
    A public form's page.

    An ordinary site page, built the same way as an article or a Page: hero,
    breadcrumb, heading, rule, copy, then the form. Its four ingredients are the
    form's OWN columns — thumbnail, title, description, content — which is why a
    form needed no new content model to become a page.

    It replaces four standalone views. show/thanks/closed/blocked were one bare
    <section> and three dead ends; every outcome is now an alert here, drawn by
    site::partials.form-embed — the same partial /contact and /inquiries use.

    `chrome` is false from the controller: the title, description and content
    below ARE the form's, so the renderer must not print its own copy of all
    three again inside the form.
--}}
@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $form->thumbnail_url,
        'title' => $title,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                {{ $title }}
            </h2>

            @if ($description = $form->getTranslation('description', $site->locale(), true))
                <p data-aos="fade-up" data-aos-duration="1000">{{ $description }}</p>
            @endif

            <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @if ($content = $form->getTranslation('content', $site->locale(), true))
                {{-- No content-styles include here: a Form's css_url/custom_css
                     are emitted by site::partials.form-renderer, which this page
                     already reaches through form-embed below. The hook is still
                     named the same, so one instruction covers every type. --}}
                <div id="page-content" class="prose mb-8" data-aos="fade-up" data-aos-duration="1000">
                    {!! $content !!}
                </div>
            @endif

            @include('site::partials.form-embed')
        </div>
    </section>
@endsection
