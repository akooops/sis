{{--
    Contact.

    Laid out as the old page was: heading, rule, the map card with its three
    contact channels across the full width, then the form beneath it — NOT a
    form-plus-sidebar grid. The channels come from ContactDetail rows rather than
    the old loose settings keys.

    The form is the seeded `contact` system form, rendered by the ordinary
    renderer through the shared partial, so it posts to the unchanged
    /forms/{locale}/{slug} endpoint. A validation failure calls back(), whose
    previous URL is THIS page, so errors and old input land right here.

    $presentation is null when the form has been unpublished or the visitor is
    blocked; the page still renders its copy and the channels, because a missing
    form must not take the school's contact page down with it.

    $address, $emails, $phones and $mapEmbed all arrive from
    ContactController::contact() — including the keyless map URL, which is built
    from the address row's own coordinates rather than from a pasted embed
    setting.
--}}
@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.page-menu', ['menu' => $page->menu])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                {{ $page->getTranslation('title', $site->locale(), true) ?: $page->name }}
            </h2>

            <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @include('site::partials.flash')

            @if ($page->getTranslation('content', $site->locale(), true))
                <div class="prose mb-8" data-aos="fade-up" data-aos-duration="1000">
                    {!! $page->getTranslation('content', $site->locale(), true) !!}
                </div>
            @endif

            {{-- Map plus the three contact channels --}}
            @if ($mapEmbed || $address || $emails->isNotEmpty() || $phones->isNotEmpty())
                <div class="card mb-8" data-aos="fade-up" data-aos-duration="1000">
                    @if ($mapEmbed)
                        <iframe id="google-map" class="h-[500px] w-full border-0" src="{{ $mapEmbed }}" allowfullscreen
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            title="@lang('nav.footer.address')"></iframe>
                    @endif

                    <div class="grid gap-4 p-10 md:grid-cols-3">
                        <div class="flex gap-4">
                            <i class="uil uil-location-pin-alt -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                            <div>
                                <h5 class="mb-1">@lang('nav.footer.address')</h5>
                                @if ($address)
                                    <address class="not-italic">
                                        @if ($address->map_url)
                                            <a class="text-brand hover:text-brand-soft" href="{{ $address->map_url }}"
                                                target="_blank" rel="noopener">
                                                {{ $address->getTranslation('address', $site->locale(), true) }}
                                            </a>
                                        @else
                                            {{ $address->getTranslation('address', $site->locale(), true) }}
                                        @endif
                                    </address>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <i class="uil uil-phone-volume -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                            <div>
                                <h5 class="mb-1">@lang('forms.contact.phone')</h5>
                                @foreach ($phones as $phone)
                                    <p class="my-1">
                                        {{-- An E164 number reads backwards inside an RTL
                                             paragraph, so the number itself is forced LTR. --}}
                                        <a class="text-brand hover:text-brand-soft" dir="ltr"
                                            href="{{ $phone->type === 'whatsapp' ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $phone->value) : 'tel:'.$phone->value }}">
                                            {{ $phone->value }}
                                            @if ($phone->type === 'whatsapp')
                                                <i class="uil uil-whatsapp text-[#25D366]" aria-hidden="true"></i>
                                            @endif
                                        </a>
                                    </p>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <i class="uil uil-envelope -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                            <div>
                                <h5 class="mb-1">@lang('forms.contact.email')</h5>
                                @foreach ($emails as $email)
                                    <p class="my-1">
                                        <a class="text-brand hover:text-brand-soft"
                                            href="mailto:{{ $email->value }}">{{ $email->value }}</a>
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- data-sisf ONLY: FormRenderer emits its own `.sisf` root, and
                 nesting one inside another doubles the padding. --}}
            @if ($presentation)
                <div data-sisf data-aos="fade-up" data-aos-duration="1000">
                    @include('site::forms.partials.renderer')
                </div>
            @endif
        </div>
    </section>
@endsection
