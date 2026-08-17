@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.menu', ['menu' => $page->menu])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand" data-aos="fade-up"
                data-aos-duration="1000">
                {{ $page->getTranslation('title', $site->locale(), true) ?: $page->name }}
            </h2>

            <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @include('site::partials.ui.flash')

            @if ($page->getTranslation('content', $site->locale(), true))
                @include('site::partials.content.content-styles', ['model' => $page])

                {{-- id="page-content" is the scope hook the admin's own CSS
                     targets. See site::partials.content.content-styles. --}}
                <div id="page-content" class="prose mb-8" data-aos="fade-up" data-aos-duration="1000">
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

            @include('site::partials.forms.embed')
        </div>
    </section>
@endsection
