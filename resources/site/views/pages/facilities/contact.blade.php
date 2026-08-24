@extends('site::layout')

@section('content')
    @php($facilityTitle = $facility->getTranslation('title', $site->locale(), true) ?: $facility->name)

    @include('site::partials.content.hero', [
        'image' => $facility->thumbnail_url,
        'title' => $facilityTitle,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                @lang('facilities.contact.title', ['venue' => $facilityTitle])
            </h2>

            <hr class="mb-6 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @include('site::partials.ui.flash')

            {{-- THE WHOLE PAGE. No page-specific JavaScript, no wizard, no gate:
                 the venue is known server-side so `facility_id` is an ordinary
                 preset, and the standard `data-sisf` marker has site/forms.js mount
                 this exactly as it does on /inquiries.

                 That is the dividend of giving the contact form its own URL rather
                 than sharing a page with the booking wizard. --}}
            <div class="card">
                <div class="card-body p-2 sm:p-8">
                    @include('site::partials.forms.embed', [
                        'form' => $form,
                        'presentation' => $presentation,
                        'notice' => $notice,
                        'presets' => $presets,
                        'chrome' => false,
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection
