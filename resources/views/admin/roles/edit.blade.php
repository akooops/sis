@extends('admin.layouts.master')
@section('title') Roles @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Roles @endslot
@slot('title') Edit Role @endslot
@endcomponent
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

            <form method="POST" action="{{ route('roles.update', $role->id) }}">
                @method('patch')
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label" for="">Role Name</label>
                                    <input name="name" value="{{ $role->name }}" type="text" class="form-control" placeholder="Enter role name">
                                    @error('name')
                                        <p class="mx-2 my-2 text-danger">
                                            <strong>
                                                {{$message}}
                                            </strong>
                                        </p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="">Assign Permissions</label>
                                    <table class="table table-striped">
                                        <thead>
                                            <th scope="col" width="1%">
                                                <input id="checkAll" type="checkbox">
                                            </th>
                                            <th scope="col" width="20%">Name</th>
                                            <th scope="col" width="1%">Guard</th> 
                                        </thead>
                                        @foreach($permissions as $permission)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" 
                                                    name="permission[{{ $permission->name }}]"
                                                    value="{{ $permission->name }}"
                                                    class='permission'
                                                    {{ in_array($permission->name, $rolePermissions) 
                                                        ? 'checked'
                                                        : '' }}>
                                                </td>
                                                <td>{{ $permission->name }}</td>
                                                <td>{{ $permission->guard_name }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- end card -->

                        <div class="text-end mb-3">
                            <a href="{{ route('roles.index') }}" class="btn btn-primary w-sm">Back</a>
                            <button type="submit" class="btn btn-success w-sm">Submit</button>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->

            </form>
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>

<script type="text/javascript">
    $(document).ready(function() {
        $('[name="all_permission"]').on('click', function() {

            if($(this).is(':checked')) {
                $.each($('.permission'), function() {
                    $(this).prop('checked',true);
                });
            } else {
                $.each($('.permission'), function() {
                    $(this).prop('checked',false);
                });
            }
            
        });
    });

    
    $("#checkAll").click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
    });
</script>
@endsection
