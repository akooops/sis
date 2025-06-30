@extends('admin.layouts.master')
@section('title') Messages @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Messages @endslot
@slot('title') Messages @endslot
@endcomponent
<style>
    .limited-text {
        max-width: 300px; /* Adjust as needed */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

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
                            <h4 class="card-title mb-0">Manage Messages</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-start mb-2">
                                <div class="search-box flex-grow-1 mb-2">
                                    <form action="{{route('messages.index')}}">
                                        <input name="search" value="{{request()->get('search')}}" type="text" class="form-control w-50">
                                        <i class="ri-search-line search-icon"></i>
                                    </form>
                                </div>
                            </div>

                            <div id="to-be-destroyed-container">
                                <form action="{{route('messages.destroyMultiple')}}" method="POST">
                                    @csrf
                                    @method('post')
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
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Subject</th>
                                            <th scope="col">Message</th>
                                            <th scope="col" colspan="3" width="1%">Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($messages as $message)
                                        <tr>
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $message->id }}" oninput="checkRow()">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </th>
                                            <td>
                                                <a href="#" class="fw-semibold">#{{$message->id}}</a>
                                            </td> 
                                            <td>{{ $message->firstname }} {{ $message->lastname }}</td>
                                            <td>{{ $message->email }}</td>
                                            <td>
                                                <p class="limited-text">
                                                    {{$message->subject}}
                                                </p>
                                            </td>
                                            <td>
                                                <p class="limited-text">
                                                    {{$message->message}}
                                                </p>
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2">                                             
                                                    <div class="show">
                                                        <a href="{{ route('messages.show', $message->id) }}" class="btn btn-sm btn-info show-item-btn">Show</a>
                                                    </div>
                                                    <div class="remove">
                                                        {!! Form::open(['method' => 'DELETE','route' => ['messages.destroy', $message->id],'style'=>'display:inline']) !!}
                                                        {!! Form::submit('Delete', ['class' => 'btn btn-sm btn-danger remove-item-btn']) !!}
                                                        {!! Form::close() !!}
                                                    </div>
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
                            @component('admin.components.pagination', ['route' => 'messages.index', 'pagination' => $pagination])@endcomponent
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/old-admin/js/app.min.js') }}"></script>

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
