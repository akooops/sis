<div class="align-items-center justify-content-between d-flex">
    <div class="flex-shrink-0">
        <div class="text-muted">
            Showing from {{$pagination['from']}} to {{$pagination['to']}} of {{$pagination['total']}} results
        </div>
    </div>
    <ul class="pagination pagination-separated pagination-sm mb-0">
        <li class="page-item {{is_null($pagination["prevPage"]) ? 'disabled' : ''}}">
            <a href="{{ route($route, array_merge(['page' => $pagination["prevPage"]], request()->only('search', 'perPage'))) }}" 
                class="page-link">←</a>
        </li>
        
        @foreach($pagination["pages"] as $page)
        <li class="page-item {{($page == $pagination['currentPage']) ? 'active' : ''}}">
            <a href="{{ route($route, array_merge(['page' => $page], request()->only('search', 'perPage'))) }}" 
                class="page-link">
                {{$page}}
            </a>
        </li>
        @endforeach

        <li class="page-item {{is_null($pagination["nextPage"]) ? 'disabled' : ''}}">
            <a href="{{ route($route, array_merge(['page' => $pagination["nextPage"]], request()->only('search', 'perPage'))) }}" 
                class="page-link">→</a>
        </li>
    </ul>
</div>