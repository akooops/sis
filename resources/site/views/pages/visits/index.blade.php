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

            @php($confirmed = $form && session('sisf_submitted') === $form->id)

            @if ($confirmed)
                {{-- Booked. The wizard is gone, not hidden: there is nothing left to
                     choose, and leaving three steps above a confirmation invites
                     somebody to walk back through them and book it again. --}}
                @include('site::partials.forms.embed', ['form' => $form, 'presentation' => null, 'notice' => null])
            @elseif ($presentation && $services->isNotEmpty())
                {{-- WHICH STEP THE PAGE OPENS ON IS DECIDED HERE, SERVER-SIDE.

                     The submit is a native POST, so everything the server has to say
                     arrives as a FRESH PAGE LOAD. A wizard that always started at
                     step one would throw a rejected visitor back to the card grid
                     with their answers, their visit and their time all still in
                     old() but nothing on screen showing it.

                     `$rejected` is spelled exactly the way FormPresenter spells
                     `returning`, so the step the page opens on and the values
                     site/visits.js mounts the renderer with cannot disagree. --}}
                @php($rejected = old('fields', []) !== [])
                @php($step = $rejected ? 3 : 1)

                @php($stepLabels = [
                    1 => __('visits.steps.service'),
                    2 => __('visits.steps.slot'),
                    3 => __('visits.steps.details'),
                ])

                <div
                    data-visits-root
                    data-step="{{ $step }}"
                    {{-- __SERVICE__ is replaced in the browser. Built with route() so
                         the feed inherits whatever locale prefix the page was
                         rendered under, rather than being pasted together in JS. --}}
                    data-slots-url="{{ route('web.site.visits.slots', ['service' => '__SERVICE__']) }}"
                    data-aos="fade-up"
                    data-aos-duration="1000"
                >
                    {{-- On phones the stylesheet shows only the active step, so this
                         is a progress indicator there and a strip of three from the
                         large breakpoint up. --}}
                    <ol class="steps mb-6" data-visit-steps>
                        @foreach ($stepLabels as $number => $label)
                            <li class="step flex-1 {{ $number === $step ? 'is-active' : ($number < $step ? 'is-done' : '') }}"
                                data-visit-step-marker="{{ $number }}"
                                aria-current="{{ $number === $step ? 'step' : 'false' }}">
                                <span class="step-number">{{ $number }}</span>
                                <span>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>

                    {{-- ONE STEP AT A TIME. `hidden` rather than a class, so a step
                         that is not showing is out of the accessibility tree and out
                         of the tab order too — not merely invisible. --}}
                    <section data-visit-step="1" @if ($step !== 1) hidden @endif>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
                            @foreach ($services as $service)
                                @php($serviceTitle = $service->getTranslation('title', $site->locale(), true) ?: $service->name)

                                <div class="card" data-visit-service="{{ $service->id }}"
                                    data-visit-slug="{{ $service->slug }}"
                                    data-visit-max="{{ $service->maxVisitors() }}"
                                    data-visit-title="{{ $serviceTitle }}">
                                    <figure class="overlay h-[350px]">
                                        <img class="h-full w-full object-cover"
                                            src="{{ $service->thumbnail_url ?: asset('assets/site/images/placeholder.png') }}"
                                            alt="{{ $serviceTitle }}">
                                    </figure>

                                    <div class="card-body p-4">
                                        <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                            {{ $serviceTitle }}
                                        </h2>

                                        <p class="line-clamp-3">
                                            {{ $service->getTranslation('description', $site->locale(), true) }}
                                        </p>

                                        @if ($service->getTranslation('content', $site->locale(), true))
                                            <button type="button" class="mt-2 self-start text-brand underline"
                                                data-toggle="dialog" data-target="#visit-dialog-{{ $service->id }}">
                                                @lang('visits.read_more')
                                            </button>
                                        @endif

                                        <ul class="post-meta mt-2">
                                            <li>
                                                <i class="uil uil-clock" aria-hidden="true"></i>
                                                <span>@lang('visits.service.duration', ['minutes' => $service->duration_minutes])</span>
                                            </li>
                                        </ul>

                                        {{-- The party-size counter. Its value becomes
                                             the CEILING of the students group on the
                                             booking form — visits.js seeds that many
                                             blank rows and the submit-time rule caps
                                             it. --}}
                                        <div class="counter my-2 w-full">
                                            <span class="flex h-full items-center px-3">
                                                <i class="uil uil-user" aria-hidden="true"></i>
                                            </span>

                                            <div class="flex flex-1">
                                                <input type="number" min="1" max="{{ $service->maxVisitors() }}"
                                                    readonly class="counter-input" value="1" data-visit-count
                                                    aria-label="@lang('visits.fields.visitors')">

                                                <div class="flex flex-col border-s border-line">
                                                    <button type="button" class="counter-btn" data-visit-step-up
                                                        aria-label="+">&#9650;</button>
                                                    <button type="button" class="counter-btn" data-visit-step-down
                                                        aria-label="-">&#9660;</button>
                                                </div>

                                                <button type="button" class="btn flex-1 rounded-s-none px-3 text-sm"
                                                    data-visit-select>
                                                    @lang('visits.select')
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($service->getTranslation('content', $site->locale(), true))
                                    {{-- The Read-more popup, with the service's OWN
                                         stylesheet and custom CSS around its body —
                                         the same pair a form and a page carry, scoped
                                         the same way. --}}
                                    <dialog id="visit-dialog-{{ $service->id }}" class="dialog">
                                        <button type="button" class="btn-close absolute end-4 top-4 z-10"
                                            data-dismiss="dialog" aria-label="Close"></button>

                                        <figure>
                                            <img class="w-full object-cover"
                                                src="{{ $service->thumbnail_url ?: asset('assets/site/images/placeholder.png') }}"
                                                alt="{{ $serviceTitle }}">
                                        </figure>

                                        <div class="px-6 py-4">
                                            <h3>{{ $serviceTitle }}</h3>
                                            <p class="text-xl font-medium text-ink">
                                                {{ $service->getTranslation('description', $site->locale(), true) }}
                                            </p>
                                            <hr class="mb-4 mt-2 border-line">

                                            @if ($service->css_url)
                                                <link rel="stylesheet" href="{{ $service->css_url }}">
                                            @endif

                                            {{-- {!! !!} and the same guarantee
                                                 site::partials.forms.renderer
                                                 documents: SanitisesCustomCss strips
                                                 `</style` and `<script` on the way
                                                 into the column, and <style> is
                                                 RAWTEXT, so nothing left in the value
                                                 can open a tag here. Do not
                                                 interpolate this column anywhere
                                                 else. --}}
                                            @if ($service->custom_css)
                                                <style>{!! $service->custom_css !!}</style>
                                            @endif

                                            <div id="visit-content-{{ $service->id }}" class="prose mb-6">
                                                {!! $service->getTranslation('content', $site->locale(), true) !!}
                                            </div>
                                        </div>
                                    </dialog>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <section data-visit-step="2" @if ($step !== 2) hidden @endif>
                        <div class="card">
                            <div class="card-header p-2 sm:p-8">
                                <button type="button" class="btn btn-sm mb-2" data-visit-back="1"
                                    aria-label="{{ $stepLabels[1] }}">
                                    <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                                    @lang('visits.back')
                                </button>

                                <h2 class="mb-0 text-3xl font-black uppercase text-brand" data-visit-heading>
                                    {{ $chosenService?->getTranslation('title', $site->locale(), true) ?: $chosenService?->name }}
                                </h2>
                                <small class="text-muted" data-visit-meta>
                                    @if ($chosenService)
                                        @lang('visits.fields.visitors'): {{ $chosenVisitors }}
                                    @endif
                                </small>
                            </div>

                            <div class="card-body p-2 sm:p-8">
                                {{-- The calendar mounts here, and draws its own legend
                                     and its own refusal message above itself. --}}
                                <div data-visit-calendar></div>
                            </div>
                        </div>
                    </section>

                    <section data-visit-step="3" @if ($step !== 3) hidden @endif>
                        <div class="card">
                            <div class="card-header p-2 sm:p-8">
                                <button type="button" class="btn btn-sm mb-2" data-visit-back="2"
                                    aria-label="{{ $stepLabels[2] }}">
                                    <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                                    @lang('visits.back')
                                </button>

                                <h2 class="mb-0 text-3xl font-black uppercase text-brand" data-visit-heading>
                                    {{ $chosenService?->getTranslation('title', $site->locale(), true) ?: $chosenService?->name }}
                                </h2>
                                {{-- RENDERED BY BLADE for a returning visitor, because
                                     a rejected submit is a fresh page load and only
                                     the server still knows what they picked — old()
                                     has it, the browser does not. visits.js rewrites
                                     this the moment they choose anything else. --}}
                                <small class="text-muted" data-visit-meta>
                                    @if ($chosenSlot)
                                        {{ $chosenSlot->starts_at->translatedFormat('l j F Y, H:i') }}
                                        &middot; @lang('visits.fields.visitors'): {{ $chosenVisitors }}
                                    @endif
                                </small>
                            </div>

                            <div class="card-body p-2 sm:p-8">
                                @include('site::partials.forms.embed', [
                                    'form' => $form,
                                    'presentation' => $presentation,
                                    'notice' => $notice,
                                    'chrome' => false,
                                    // OFF: visits.js owns this mount. See the note in
                                    // site::partials.forms.embed.
                                    'marker' => false,
                                ])
                            </div>
                        </div>
                    </section>

                    {{-- A JSON block, not data-* attributes: these are sentences, some
                         carry placeholders, and every one of them is translated. Same
                         reason the form ships its own payload this way. --}}
                    <script type="application/json" id="visits-config">
                        {!! json_encode([
                            'locale' => $site->locale(),
                            'direction' => $site->direction(),
                            'serviceField' => config('visits.service_field'),
                            'slotField' => config('visits.slot_field'),
                            'visitorsField' => config('visits.visitors_field'),
                            'studentsField' => array_key_first(config('visits.groups', [])) ?: 'students',
                            'labels' => [
                                'available' => __('visits.slots.available'),
                                'limited' => __('visits.slots.limited'),
                                'full' => __('visits.slots.full'),
                                'closed' => __('visits.slots.closed'),
                                // :count survives the round trip so the component can
                                // put the per-slot number into the translated
                                // sentence — the placeholder is per event, and only
                                // the browser knows which event it is drawing.
                                'remaining' => __('visits.slots.remaining', ['count' => ':count']),
                                'visitors' => __('visits.fields.visitors'),
                                'loadFailed' => __('visits.error'),
                            ],
                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
                    </script>
                </div>
            @elseif ($services->isEmpty())
                {{-- Published but with nothing to offer. Says so rather than rendering
                     an empty grid and a wizard that opens onto nothing. --}}
                <div class="alert alert-warning flex items-center gap-2" role="alert" data-aos="fade-up"
                    data-aos-duration="1000">
                    <i class="uil uil-info-circle" aria-hidden="true"></i>
                    <span class="flex-1">@lang('visits.slots.none')</span>
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
