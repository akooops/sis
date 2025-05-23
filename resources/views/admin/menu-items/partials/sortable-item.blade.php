<li class="list-group-item" data-id="{{ $item->id }}">
    <div class="d-flex align-items-center">
        <i class="ri-drag-move-2-line handle"></i>
        {{ $item->name }}
        <small class="text-muted ms-2">(ID: {{ $item->id }})</small>
    </div>
    
    <!-- Always create a nested list, even if empty -->
    <ul class="sortable-list">
        @if($item->children->count() > 0)
            @foreach($item->children as $child)
                @include('admin.menu-items.partials.sortable-item', ['item' => $child])
            @endforeach
        @endif
    </ul>
</li>
