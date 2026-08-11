@php($trail = $breadcrumbs ?? [])

@if ($trail !== [])
    <section>
        <div class="container py-3 md:py-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li>
                        <a class="uppercase" href="{{ route('web.site.home') }}">@lang('site.breadcrumbs.home')</a>
                    </li>

                    @foreach ($trail as $crumb)
                        @if ($loop->last)
                            <li class="uppercase" aria-current="page">{{ $crumb['label'] }}</li>
                        @elseif ($crumb['url'] ?? null)
                            <li><a class="uppercase" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                        @else
                            <li class="uppercase">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </section>
@endif
