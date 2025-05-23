@extends('admin.layouts.master')
@section('title') Menu Order @endsection
@section('css')
<style>
.sortable-list {
    list-style: none;
    padding: 0;
    margin: 0;
    min-height: 20px; /* Important for empty lists */
}
.sortable-list ul {
    margin-left: 30px;
    list-style: none;
    padding: 0;
    min-height: 20px; /* Important for nesting */
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
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="mt-2">
                @include('admin.layouts.messages')
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Order Menu Items - {{ $menu->name }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Drag and drop items to reorder. Drop items onto other items to create submenus.
                            </div>
                            
                            <ul id="sortable" class="sortable-list">
                                @foreach($menuItems as $item)
                                    @include('admin.menu-items.partials.sortable-item', ['item' => $item])
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-3">
                    <a href="{{ route('admin.menu-items.index', ['menu' => $menu->id]) }}" class="btn btn-primary w-sm">Back</a>
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
    function initSortable() {
        // Get all nested sortable elements
        var nestedSortables = document.querySelectorAll('.sortable-list');
        
        // Loop through each nested sortable element
        for (var i = 0; i < nestedSortables.length; i++) {
            new Sortable(nestedSortables[i], {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                emptyInsertThreshold: 5,
                onEnd: function(evt) {
                    // Re-initialize sortables for any new nested lists
                    setTimeout(initSortable, 100);
                    updateOrder();
                }
            });
        }
    }

    function updateOrder() {
        const items = [];
        
        function processList(list, parentId = null) {
            const children = Array.from(list.children);
            children.forEach((el, index) => {
                if (el.dataset.id) {
                    const itemData = {
                        id: el.dataset.id,
                        order: index + 1,
                        menu_item_id: parentId
                    };
                    
                    items.push(itemData);

                    // Process child lists
                    const childList = el.querySelector('.sortable-list');
                    if (childList) {
                        processList(childList, itemData.id);
                    }
                }
            });
        }

        const rootList = document.getElementById('sortable');
        processList(rootList);
        
        console.log('Items to update:', items);
        
        fetch('{{ route("admin.menu-items.order", $menu->id) }}', {
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

    // Initialize sortable
    initSortable();
});
</script>
@endsection
