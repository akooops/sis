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
                @lang('facilities.reserve.title', ['venue' => $facilityTitle])
            </h2>

            <hr class="mb-6 mt-2 border-line" data-aos="fade-up" data-aos-duration="1000">

            @include('site::partials.ui.flash')

            @php($confirmed = $form && session('sisf_submitted') === $form->id)

            @if ($confirmed)
                {{-- Booked. The wizard is gone, not hidden: there is nothing left
                     to choose, and leaving two steps above a confirmation invites
                     somebody to walk back through them and book it again. --}}
                @include('site::partials.forms.embed', ['form' => $form, 'presentation' => null, 'notice' => null])
            @elseif ($presentation)
                {{-- WHICH STEP THE PAGE OPENS ON IS DECIDED HERE, SERVER-SIDE.

                     The submit is a native POST, so everything the server has to say
                     arrives as a FRESH PAGE LOAD. A wizard that always started on
                     the calendar would throw a rejected visitor back to step one
                     with their answers and their chosen time still in old() but
                     nothing on screen showing it.

                     `$rejected` is spelled exactly the way FormPresenter spells
                     `returning`, so the step the page opens on and the values
                     site/facilities.js mounts the renderer with cannot disagree. --}}
                @php($rejected = old('fields', []) !== [])
                @php($step = $rejected ? 2 : 1)

                @php($stepLabels = [
                    1 => __('facilities.steps.slot'),
                    2 => __('facilities.steps.details'),
                ])

                <div
                    data-facility-reserve
                    data-step="{{ $step }}"
                    {{-- Built with route() so the feed inherits whatever locale
                         prefix the page was rendered under, rather than being
                         pasted together in JS. --}}
                    data-slots-url="{{ route('web.site.facilities.slots', ['slug' => $facility->slug]) }}"
                    data-aos="fade-up"
                    data-aos-duration="1000"
                >
                    {{-- On phones the stylesheet shows only the active step, so this
                         is a progress indicator there and a strip of two from the
                         large breakpoint up. --}}
                    <ol class="steps mb-6">
                        @foreach ($stepLabels as $number => $label)
                            <li class="step flex-1 {{ $number === $step ? 'is-active' : ($number < $step ? 'is-done' : '') }}"
                                data-facility-step-marker="{{ $number }}"
                                aria-current="{{ $number === $step ? 'step' : 'false' }}">
                                <span class="step-number">{{ $number }}</span>
                                <span>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>

                    {{-- ONE STEP AT A TIME. `hidden` rather than a class, so a step
                         that is not showing is out of the accessibility tree and out
                         of the tab order too — not merely invisible. --}}
                    <section data-facility-step="1" @if ($step !== 1) hidden @endif>
                        <div class="card">
                            <div class="card-body p-2 sm:p-8">
                                {{-- The calendar mounts here, and draws its own
                                     legend and its own refusal message above itself. --}}
                                <div data-facility-calendar></div>
                            </div>
                        </div>
                    </section>

                    <section data-facility-step="2" @if ($step !== 2) hidden @endif>
                        <div class="card">
                            <div class="card-header p-2 sm:p-8">
                                <button type="button" class="btn btn-sm mb-2" data-facility-back
                                    aria-label="{{ $stepLabels[1] }}">
                                    <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                                    @lang('facilities.back')
                                </button>

                                <h2 class="mb-0 text-3xl font-black uppercase text-brand">{{ $facilityTitle }}</h2>
                                {{-- RENDERED BY BLADE for a returning visitor,
                                     because a rejected submit is a fresh page load
                                     and only the server still knows which time they
                                     picked — old() has it, the browser does not.
                                     site/facilities.js rewrites this the moment they
                                     choose anything else. --}}
                                <small class="text-muted" data-facility-meta>
                                    @if ($chosenSlot)
                                        @lang('facilities.confirm.selected'):
                                        {{ $chosenSlot->starts_at->translatedFormat('l j F Y, H:i') }}
                                    @endif
                                </small>
                            </div>

                            <div class="card-body p-2 sm:p-8">
                                @include('site::partials.forms.embed', [
                                    'form' => $form,
                                    'presentation' => $presentation,
                                    'notice' => $notice,
                                    'presets' => $presets,
                                    'chrome' => false,
                                    // OFF: facilities.js owns this mount, because the
                                    // chosen time is only known in the browser. See
                                    // the note in site::partials.forms.embed.
                                    'marker' => false,
                                ])
                            </div>
                        </div>
                    </section>

                    {{-- A JSON block, not data-* attributes: these are sentences,
                         some carry placeholders, and every one is translated. --}}
                    <script type="application/json" id="facilities-config">
                        {!! json_encode([
                            'locale' => $site->locale(),
                            'direction' => $site->direction(),
                            'slotField' => config('facilities.slot_field'),
                            'labels' => [
                                'available' => __('facilities.slots.available'),
                                'limited' => __('facilities.slots.limited'),
                                'full' => __('facilities.slots.full'),
                                'closed' => __('facilities.slots.closed'),
                                // :count survives the round trip so the component can
                                // put the per-slot number into the translated
                                // sentence — the placeholder is per event, and only
                                // the browser knows which event it is drawing.
                                'remaining' => __('facilities.slots.remaining', ['count' => ':count']),
                                'selected' => __('facilities.confirm.selected'),
                                'loadFailed' => __('facilities.error'),
                            ],
                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
                    </script>
                </div>
            @else
                {{-- Blocked, closed or unseeded: form-embed draws the reason. --}}
                @include('site::partials.forms.embed', [
                    'form' => $form,
                    'presentation' => null,
                    'notice' => $notice,
                ])
            @endif
        </div>
    </section>
@endsection
