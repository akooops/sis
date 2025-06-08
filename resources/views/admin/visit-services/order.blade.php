@extends('admin.layouts.master')
@section('title') Visit Service Order @endsection
@section('css')
<style>
.sortable-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sortable-list .list-group-item {
    cursor: move;
    margin: 5px 0;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 10px;
    border-radius: 5px;
    position: relative;
}
.handle {
    margin-right: 10px;
    cursor: move;
    color: #6c757d;
}
.sortable-ghost {
    opacity: 0.4;
}
.sortable-chosen {
    background-color: #e3f2fd;
}
.sortable-drag {
    background-color: #fff3cd;
}
</style>
@endsection

@section('content')
<div class="row">
    <div class="col">
        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good Morning, {{Auth::user()->fullname}}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Order Visit Services</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Drag and drop visit services to reorder.
                            </div>
                            
                            <ul id="sortable" class="sortable-list">
                                @foreach($visitServices as $visitService)
                                    <li class="list-group-item" data-id="{{ $visitService->id }}">
                                        <div class="d-flex align-items-center">
                                            <i class="ri-drag-move-2-line handle"></i>
                                            {{ $visitService->name }}
                                            <small class="text-muted ms-2">(ID: {{ $visitService->id }})</small>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-3">
                    <a href="{{ route('admin.visit-services.index') }}" class="btn btn-primary w-sm">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize single sortable list (no nesting)
    const sortableList = document.getElementById('sortable');
    
    new Sortable(sortableList, {
        animation: 150,
        handle: '.handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            updateOrder();
        }
    });

    function updateOrder() {
        const items = [];
        const children = Array.from(sortableList.children);
        
        children.forEach((el, index) => {
            if (el.dataset.id) {
                items.push({
                    id: el.dataset.id,
                    order: index + 1
                });
            }
        });
        
        console.log('Items to update:', items);
        
        fetch('{{ route("admin.visit-services.order") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ order: items })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                console.log('Order updated successfully');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});
</script>
@endsection
