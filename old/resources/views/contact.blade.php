@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('contact'))

@section('content')

@php
    $googleMapEmbedUrl = getSetting('google_maps_embed_url');
    $googleMapUrl = getSetting('google_maps_url');

    $emails = json_decode(getSetting('emails')->value);
    $phones = json_decode(getSetting('phones')->value);
@endphp

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

        @include('partials.flash')

        {{-- Map plus the three contact channels --}}
        <div class="card mb-8" data-aos="fade-up" data-aos-duration="1000">
            @if ($googleMapUrl && $googleMapEmbedUrl)
                <iframe id="google-map" class="h-[500px] w-full border-0" src="{{ $googleMapEmbedUrl->value }}"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="{{ getLanguageKeyLocalTranslation('contact_page_address_title') }}"></iframe>
            @endif

            <div class="grid gap-4 p-10 md:grid-cols-3">
                <div class="flex gap-4">
                    <i class="uil uil-location-pin-alt -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                    <div>
                        <h5 class="mb-1">{{ getLanguageKeyLocalTranslation('contact_page_address_title') }}</h5>
                        @if ($googleMapUrl && !empty($googleMapUrl->value))
                            <address class="not-italic">
                                <a class="text-brand hover:text-brand-soft" href="{{ $googleMapUrl->value }}"
                                    target="_blank" rel="noopener">
                                    {{ getLanguageKeyLocalTranslation('footer_address_placeholder') }}
                                </a>
                            </address>
                        @else
                            {{ getLanguageKeyLocalTranslation('footer_address_placeholder') }}
                        @endif
                    </div>
                </div>

                <div class="flex gap-4">
                    <i class="uil uil-phone-volume -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                    <div>
                        <h5 class="mb-1">{{ getLanguageKeyLocalTranslation('contact_page_phone_title') }}</h5>
                        @if ($phones && is_array($phones))
                            @foreach ($phones as $phone)
                                <p class="my-1">
                                    <a class="text-brand hover:text-brand-soft" dir="ltr"
                                        href="https://wa.me/{{ preg_replace('/\D+/', '', $phone) }}">
                                        {{ $phone }}
                                        <i class="uil uil-whatsapp text-[#25D366]" aria-hidden="true"></i>
                                    </a>
                                </p>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="flex gap-4">
                    <i class="uil uil-envelope -mt-1 text-5xl text-brand" aria-hidden="true"></i>
                    <div>
                        <h5 class="mb-1">{{ getLanguageKeyLocalTranslation('contact_page_email_title') }}</h5>
                        @if ($emails && is_array($emails))
                            @foreach ($emails as $email)
                                <p class="my-1">
                                    <a class="text-brand hover:text-brand-soft" href="mailto:{{ $email }}">{{ $email }}</a>
                                </p>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('contact-submissions.store') }}" class="flex flex-col gap-4"
            data-aos="fade-up" data-aos-duration="1000">
            @csrf

            <div>
                <div class="input-group">
                    <span class="input-addon"><i class="uil uil-user" aria-hidden="true"></i></span>
                    <input name="name" type="text" required
                        class="input @error('name') is-invalid @enderror"
                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_name_input') }}"
                        value="{{ old('name') }}"
                        aria-label="{{ getLanguageKeyLocalTranslation('contact_page_name_input') }}">
                </div>
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div>
                <div class="input-group">
                    <span class="input-addon"><i class="uil uil-envelope" aria-hidden="true"></i></span>
                    <input name="email" type="email" required
                        class="input @error('email') is-invalid @enderror"
                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_email_input') }}"
                        value="{{ old('email') }}"
                        aria-label="{{ getLanguageKeyLocalTranslation('contact_page_email_input') }}">
                </div>
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div>
                <input name="phone_display" type="tel" data-phone-input="#phone-e164"
                    class="input @error('phone') is-invalid @enderror"
                    placeholder="{{ getLanguageKeyLocalTranslation('contact_page_phone_input') }}"
                    value="{{ old('phone_display') }}"
                    aria-label="{{ getLanguageKeyLocalTranslation('contact_page_phone_input') }}">
                <input type="hidden" name="phone" id="phone-e164" value="{{ old('phone') }}">
                @error('phone')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div>
                <div class="input-group">
                    <span class="input-addon"><i class="uil uil-subject" aria-hidden="true"></i></span>
                    <input name="subject" type="text" required
                        class="input @error('subject') is-invalid @enderror"
                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_subject_input') }}"
                        value="{{ old('subject') }}"
                        aria-label="{{ getLanguageKeyLocalTranslation('contact_page_subject_input') }}">
                </div>
                @error('subject')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div>
                <div class="input-group">
                    <span class="input-addon"><i class="uil uil-comment-message" aria-hidden="true"></i></span>
                    <textarea name="message" rows="8" required
                        class="input textarea @error('message') is-invalid @enderror"
                        placeholder="{{ getLanguageKeyLocalTranslation('contact_page_message_input') }}"
                        aria-label="{{ getLanguageKeyLocalTranslation('contact_page_message_input') }}">{{ old('message') }}</textarea>
                </div>
                @error('message')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div>
                <div class="g-recaptcha" data-sitekey="{{ getSetting('google_recaptcha_public_key')->value }}"
                    data-theme="light" data-size="normal"></div>
                @error('g-recaptcha-response')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="text-end">
                <button type="submit" class="btn">
                    {{ getLanguageKeyLocalTranslation('contact_page_submit_button') }}
                </button>
            </div>
        </form>
    </div>
</section>

@endsection

@section('script')
    <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
@endsection
