{{--
    @param string $current  Label for the current page
    @param array  $links    The trail before it: [['label' => …, 'url' => …], …].
                            Nothing is prepended automatically — listing pages
                            pass Home, detail pages pass their parent listing.
--}}
<section>
    <div class="container py-3 md:py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                @foreach ($links ?? [] as $link)
                    <li>
                        <a class="uppercase" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    </li>
                @endforeach

                <li class="uppercase" aria-current="page">{{ $current }}</li>
            </ol>
        </nav>
    </div>
</section>
