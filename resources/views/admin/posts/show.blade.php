@extends('admin.layouts.master')
@section('title') Posts @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Posts @endslot
@slot('title') Show Post @endslot
@endcomponent
<div class="row">
    <div class="col">

        <div class="h-100">
            <div class="row mb-3 pb-1">
                <div class="col-12">
                    <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                        <div class="flex-grow-1">
                            <h4 class="fs-16 mb-1">Good morning , {{Auth::user()->fullname}}</h4>
                        </div>
                    </div><!-- end card header -->
                </div>
                <!--end col-->
            </div>
            <!--end row-->

            <div class="row">                                
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Post Inforamtion</h4>
                        </div>
                        <div class="card-body">
                            <div>
                                <figure class="figure">
                                    <img src="{{$post->image->fullpath}}" alt="" class="rounded avatar-xl" style="object-fit: cover">
                                </figure>
                            </div>
                            
                            <div class="mb-3">
                                <h4 class="fs-15">Post Title</h4>
                                {{$post->title}}
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Post Category</h4>
                                @if(is_null($post->category))
                                    <span class="badge bg-danger">Deleted Category</span>
                                @else
                                    <span class="badge bg-primary">{{$post->category->name}}</span>
                                @endif 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Post Overview</h4>
                                @if(is_null($post->overview))
                                    <span class="badge bg-danger">No Overview</span>
                                @else
                                    {{$post->overview}}
                                @endif 
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Post Reading Time</h4>
                                <span class="badge bg-primary">{{$post->reading_time}} min</span>
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Post Schedule</h4>
                                {{($post->published != NULL) ? $post->published : 'Not Scheduled' }}
                            </div>  
                            
                            <div class="mb-3">
                                <h4 class="fs-15">Created By</h4>
                                @if(is_null($post->user))
                                    <span class="badge bg-primary">Deleted User</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $post->user->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $post->user->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>

                            <div class="mb-3">
                                <h4 class="fs-15">Updated By</h4>
                                @if(is_null($post->updatedBy))
                                    <span class="badge bg-primary">Not Updated</span>
                                @else
                                    <div class="d-flex align-items-center">            
                                        <div class="flex-shrink-0">
                                            <img src="{{ $post->updatedBy->image->fullpath }}" alt="" class="avatar-xs rounded-circle">
                                        </div>            
                                        <div class="flex-grow-1 ms-2 name">
                                            {{ $post->updatedBy->fullname }}
                                        </div>            
                                    </div>   
                                @endif  
                            </div>
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Post Content</h4>
                        </div>
                        <div class="card-body">
                            {!! $post->html !!}
                        </div>
                    </div>
                    <!-- end card -->

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Post Sources</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach($post->asources as $ref)
                                <li class="list-group-item">
                                    {{$ref}}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->

                <div class="text-end mb-3">
                    <a href="{{ route('posts.index') }}" class="btn btn-primary w-sm">Back</a>
                    <a href="{{ route('posts.edit', $post->id) }}" class="btn btn-success w-sm">Edit</a>
                </div>
            </div>
            <!-- end row -->
        </div> <!-- end .h-100-->

    </div> <!-- end col -->
</div>

@endsection
@section('script')
<script src="{{ URL::asset('assets/admin/js/app.min.js') }}"></script>
@endsection
