{{-- Params: $pagination (IndexService::handlePagination array), $routeName (facility route name) --}}
@if(!empty($pagination["pages"]) && count($pagination["pages"]) > 1)
<nav class="d-flex justify-content-center mt-8" aria-label="pagination">
    <ul class="pagination">
        <li class="page-item {{ is_null($pagination["prevPage"]) ? 'disabled' : '' }}">
            <a class="page-link" href="{{ facilityRoute($routeName, array_merge(['page' => $pagination["prevPage"]], request()->only('search'))) }}" aria-label="Previous">
                <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
            </a>
        </li>

        @foreach($pagination["pages"] as $page)
            <li class="page-item">
                <a class="page-link {{ ($page == $pagination['currentPage']) ? 'active' : '' }}" href="{{ facilityRoute($routeName, array_merge(['page' => $page], request()->only('search'))) }}">
                    {{ $page }}
                </a>
            </li>
        @endforeach

        <li class="page-item {{ is_null($pagination["nextPage"]) ? 'disabled' : '' }}">
            <a class="page-link" href="{{ facilityRoute($routeName, array_merge(['page' => $pagination["nextPage"]], request()->only('search'))) }}" aria-label="Next">
                <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
            </a>
        </li>
    </ul>
    <!-- /.pagination -->
</nav>
@endif
