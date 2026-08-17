@if ($paginator->hasPages())
    @php
        $window = $window ?? 2;

        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // Clamp the window to the ends so the strip keeps a constant width
        // instead of shrinking on page 1.
        $start = max(1, min($current - $window, $last - ($window * 2)));
        $end = min($last, max($current + $window, ($window * 2) + 1));
    @endphp

    <nav class="pager" aria-label="@lang('common.pagination')">
        <ul class="flex items-center gap-1">
            <li class="pager-item">
                @if ($paginator->onFirstPage())
                    <span class="pager-link is-disabled" aria-disabled="true">
                        <i class="uil uil-angle-left-b" aria-hidden="true"></i>
                        <span class="sr-only">@lang('common.previous')</span>
                    </span>
                @else
                    <a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="uil uil-angle-left-b" aria-hidden="true"></i>
                        <span class="sr-only">@lang('common.previous')</span>
                    </a>
                @endif
            </li>

            @if ($start > 1)
                <li class="pager-item"><a class="pager-link" href="{{ $paginator->url(1) }}">1</a></li>
                @if ($start > 2)
                    <li class="pager-item"><span class="pager-link is-disabled">&hellip;</span></li>
                @endif
            @endif

            @foreach ($paginator->getUrlRange($start, $end) as $page => $url)
                <li class="pager-item">
                    @if ($page === $current)
                        <span class="pager-link is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            @if ($end < $last)
                @if ($end < $last - 1)
                    <li class="pager-item"><span class="pager-link is-disabled">&hellip;</span></li>
                @endif
                <li class="pager-item"><a class="pager-link" href="{{ $paginator->url($last) }}">{{ $last }}</a></li>
            @endif

            <li class="pager-item">
                @if ($paginator->hasMorePages())
                    <a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                        <span class="sr-only">@lang('common.next')</span>
                    </a>
                @else
                    <span class="pager-link is-disabled" aria-disabled="true">
                        <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                        <span class="sr-only">@lang('common.next')</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
