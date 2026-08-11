{{--
    @param array  $pagination  ['prevPage', 'nextPage', 'currentPage', 'pages']
    @param string $route       Route name to build page links from
    @param array  $params      Extra query parameters to carry across pages
--}}
@php
    $params = $params ?? [];
@endphp

@if (count($pagination['pages'] ?? []) > 1)
    <nav class="flex" aria-label="pagination">
        <ul class="pager">
            <li class="pager-item {{ is_null($pagination['prevPage']) ? 'is-disabled' : '' }}">
                <a class="pager-link" href="{{ route($route, array_merge($params, ['page' => $pagination['prevPage']])) }}"
                    aria-label="Previous">
                    <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
                </a>
            </li>

            @foreach ($pagination['pages'] as $pageNumber)
                <li class="pager-item">
                    <a class="pager-link {{ $pageNumber == $pagination['currentPage'] ? 'is-active' : '' }}"
                        href="{{ route($route, array_merge($params, ['page' => $pageNumber])) }}"
                        @if ($pageNumber == $pagination['currentPage']) aria-current="page" @endif>
                        {{ $pageNumber }}
                    </a>
                </li>
            @endforeach

            <li class="pager-item {{ is_null($pagination['nextPage']) ? 'is-disabled' : '' }}">
                <a class="pager-link" href="{{ route($route, array_merge($params, ['page' => $pagination['nextPage']])) }}"
                    aria-label="Next">
                    <i class="uil uil-arrow-right rtl:rotate-180" aria-hidden="true"></i>
                </a>
            </li>
        </ul>
    </nav>
@endif
