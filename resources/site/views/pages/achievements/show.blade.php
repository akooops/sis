@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $achievement->thumbnail_url,
        'title' => $achievement->getTranslation('title', $site->locale(), true) ?: $achievement->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <div class="grid gap-6 lg:grid-cols-3">

                <article class="card lg:col-span-2">
                    <div class="card-body">
                        @if ($achievement->category)
                            <span class="badge mb-2">
                                {{ $achievement->category->getTranslation('title', $site->locale(), true) ?: $achievement->category->name }}
                            </span>
                        @endif

                        <h1 class="text-3xl font-black uppercase leading-[35px] text-brand">
                            {{ $achievement->getTranslation('title', $site->locale(), true) ?: $achievement->name }}
                        </h1>

                        <hr class="mb-4 mt-2 border-line">

                        <div class="prose">
                            {!! $achievement->getTranslation('content', $site->locale(), true) !!}
                        </div>
                    </div>

                    <div class="card-footer">
                        <ul class="post-meta">
                            <li>
                                <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                <span>{{ $achievement->achieved_at?->translatedFormat('j M Y') }}</span>
                            </li>
                            @if ($doneBy = $achievement->getTranslation('done_by', $site->locale(), true))
                                <li>
                                    <i class="uil uil-user" aria-hidden="true"></i>
                                    <span>{{ $doneBy }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </article>

                <aside>
                    <h4 class="mb-3 text-3xl font-black uppercase leading-[35px] text-brand">@lang('site.achievements.same_year')</h4>

                    <ul>
                        @foreach ($sameYear as $item)
                            @php($url = route('web.site.achievements.show', ['slug' => $item->slug]))
                            @php($label = $item->getTranslation('title', $site->locale(), true) ?: $item->name)

                            <li class="mt-4 flex gap-3 first:mt-0">
                                <figure class="h-[85px] w-[70px] shrink-0 overflow-hidden rounded-md">
                                    <a href="{{ $url }}">
                                        <img class="h-full w-full object-cover" src="{{ $item->thumbnail_url }}" alt="{{ $label }}"
                                            loading="lazy">
                                    </a>
                                </figure>
                    
                                <div class="min-w-0">
                                    <h6 class="mb-2">
                                        <a class="text-heading hover:text-brand" href="{{ $url }}">{{ $label }}</a>
                                    </h6>
                    
                                    <ul class="post-meta">
                                        <li>
                                            <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                            <span>{{ $item->achieved_at?->translatedFormat('j M Y') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                    </ul>                  
                </aside>
            </div>
        </div>
    </section>
@endsection
