@extends('admin.layouts.master')
@section('title') Sponsors @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Sponsors @endslot
@slot('title') Sponsors @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning, {{Auth::user()->fullname}}</h4>
                        </div>
                        <div class="mt-3 mt-lg-0">
                            @canany(['sponsors.create'])
                            <div class="row g-3 mb-0 align-items-center">
                                <div class="col-auto">
                                    <a href="{{ route('sponsors.create') }}" class="btn btn-soft-success"><i
                                            class="ri-add-circle-line align-middle me-1"></i>
                                        Create Sponsor</a>
                                </div>
                                <!--end col-->
                            </div>
                            @endcanany
                            <!--end row-->
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
                            <h4 class="card-title mb-0">Manage Sponsors</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('sponsors.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            <div id="to-be-destroyed-container">
                                <form action="{{route('sponsors.destroyMultiple')}}" method="POST">
                                    @csrf
                                    @method('sponsor')
                                    <input type="hidden" name="to-be-destroyed-ids" id="to-be-destroyed-ids">

                                    <div class="my-4 d-flex align-items-center text-muted">
                                        Chosed  <span id="to-be-destroyed-count" class="text-body fw-semibold px-1">0</span> Results 
                                        <button type="submit" class="btn btn-link link-danger p-0 ms-3">Destroy Multiple</button>
                                    </div>

                                </form>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="all-checkbox">
                                                    <label class="form-check-label" for="responsivetableCheck"></label>
                                                </div>
                                            </th>
                                            <th scope="col">#</th>
                                            <th scope="col">Image</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">URL</th>
                                            <th scope="col">Created By</th>
                                            <th scope="col">Last Updated By</th>
                                            <th scope="col" colspan="3" width="1%">Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sponsors as $sponsor)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $sponsor->id }}" oninput="checkRow()">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </th>
                                            <td>
                                                <a href="#" class="fw-semibold">#{{$sponsor->id}}</a>
                                            </td>    
                                            
                                            <td>
                                                <img src="{{$sponsor->image->fullpath}}" alt="" class="rounded-circle avatar-md" style="object-fit: cover">
                                            </td>
                                            
                                            <td>{{ $sponsor->name }}</td>

                                            <td>                                         
                                                @if(is_null($sponsor->url))
                                                    <span class="badge bg-danger">No url</span>
                                                @else
                                                    <span class="badge bg-primary">
                                                        <a href="{{$sponsor->url}}" class="text-white">URL</a>
                                                    </span>
                                                @endif 
                                            </td>

                                            <td>
                                                @if(is_null($sponsor->user))
                                                    <span class="badge bg-primary">Deleted User</span>
                                                @else
                                                    <div class="d-flex align-items-center">            
                                                        <div class="flex-shrink-0">
                                                            <img src="{{ $sponsor->user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                                        </div>            
                                                        <div class="flex-grow-1 ms-2 name">
                                                            {{ $sponsor->user->fullname }}
                                                        </div>            
                                                    </div>   
                                                @endif                                          
                                            </td>
                                            
                                            <td>
                                                @if(is_null($sponsor->updatedBy))
                                                    <span class="badge bg-primary">Not Updated</span>
                                                @else
                                                    <div class="d-flex align-items-center">            
                                                        <div class="flex-shrink-0">
                                                            <img src="{{ $sponsor->updatedBy->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                                        </div>            
                                                        <div class="flex-grow-1 ms-2 name">
                                                            {{ $sponsor->updatedBy->fullname }}
                                                        </div>            
                                                    </div>   
                                                @endif                                          
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2">
                                                    @canany(['sponsors.show'])
                                                    <div class="show">
                                                        <a href="{{ route('sponsors.show', $sponsor->id) }}" class="btn btn-sm btn-info show-item-btn">Show</a>
                                                    </div>
                                                    @endcanany
                                                    
                                                    @canany(['sponsors.edit'])
                                                    <div class="edit">
                                                        <a href="{{ route('sponsors.edit', $sponsor->id) }}" class="btn btn-sm btn-success edit-item-btn">Edit</a>
                                                    </div>
                                                    @endcanany

                                                    @canany(['sponsors.destroy'])
                                                    <div class="remove">
                                                        {!! Form::open(['method' => 'DELETE','route' => ['sponsors.destroy', $sponsor->id],'style'=>'display:inline']) !!}
                                                        {!! Form::submit('Delete', ['class' => 'btn btn-sm btn-danger remove-item-btn']) !!}
                                                        {!! Form::close() !!}
                                                    </div>
                                                    @endcanany
                                                </div> 
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!-- end table -->
                            </div>
                            <!-- end table responsive -->
                        </div>

                        <div class="card-footer">
                            @component('admin.components.pagination', ['route' => 'sponsors.index', 'pagination' => $pagination])@endcomponent
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>

<script>
    $("#all-checkbox").click(function(){
        $('#table :checkbox').not(this).prop('checked', this.checked);

        checkRow();
    });

    function checkRow(){
        var checkedItems = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        (checkedItems.length > 0) ? $('#to-be-destroyed-container').show() : $('#to-be-destroyed-container').hide();

        $('#to-be-destroyed-count').text(checkedItems.length);
        $('#to-be-destroyed-ids').val(checkedItems.join(','));
    }

    checkRow();
</script>
@endsection
