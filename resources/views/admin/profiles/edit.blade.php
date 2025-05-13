@extends('admin.layouts.master')
@section('title') Profiles @endsection
@section('css')
@endsection
@section('content')
@component('admin.components.breadcrumb')
@slot('li_1') Profiles @endslot
@slot('title') Edit Profile @endslot
@endcomponent
<form method="POST" action="{{ route('profiles.update') }}" enctype="multipart/form-data">
    @method('post')
    @csrf
    <div class="row">
        <div class="col-xxl-3">
            <div class="card mt-2">
                <div class="card-body p-4">
                    <div class="text-center">
                        <div class="profile-user position-relative d-inline-block mx-auto  mb-4">
                            <img src="{{$user->image->fullpath}}" class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                <input id="profile-img-file-input" type="file" class="profile-img-file-input" name="file">
                                <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                    <span class="avatar-title rounded-circle bg-light text-body">
                                        <i class="ri-camera-fill"></i>
                                    </span>
                                </label>
                            </div>
                            @error('file')
                            <p class="mx-2 my-2 text-danger">
                                <strong>
                                    {{$message}}
                                </strong>
                            </p>
                            @enderror
                        </div>
                        <h5 class="fs-16 mb-1">{{$user->fullname}}</h5>
                        <p class="text-muted mb-0">
                            {{ $user->getRoleNames()[0] }}
                        </p>
                    </div>
                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-9">
            <div class="card mt-xxl-2">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                <i class="fas fa-home"></i> Personal Details
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="firstnameInput" class="form-label">First Name</label>
                                            <input name="firstname" class="form-control" id="firstnameInput" value="{{$user->firstname}}">
                                            @error('firstname')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="lastnameInput" class="form-label">Last Name</label>
                                            <input name="lastname" class="form-control" id="lastnameInput" value="{{$user->lastname}}">
                                            @error('lastname')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-12">
                                        <div class="mb-3 pb-2">
                                            <label for="exampleFormControlTextarea" class="form-label">Description</label>
                                            <textarea name="description" class="form-control" id="exampleFormControlTextarea" rows="3">{{$user->description}}</textarea>
                                            @error('description')
                                            <p class="mx-2 my-2 text-danger">
                                                <strong>
                                                    {{$message}}
                                                </strong>
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-lg-12">
                                        <div class="hstack gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-success">Update</button>
                                            <button type="button" class="btn btn-primary">Cancel</button>
                                        </div>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </form>
                        </div>
                        <!--end tab-pane-->
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
</form>

@endsection
@section('script')
<script>
    document.querySelector("#profile-foreground-img-file-input")&&document.querySelector("#profile-foreground-img-file-input").addEventListener("change",function(){var o=document.querySelector(".profile-wid-img"),e=document.querySelector(".profile-foreground-img-file-input").files[0],i=new FileReader;i.addEventListener("load",function(){o.src=i.result},!1),e&&i.readAsDataURL(e)}),document.querySelector("#profile-img-file-input")&&document.querySelector("#profile-img-file-input").addEventListener("change",function(){var o=document.querySelector(".user-profile-image"),e=document.querySelector(".profile-img-file-input").files[0],i=new FileReader;i.addEventListener("load",function(){o.src=i.result},!1),e&&i.readAsDataURL(e)});var count=2;function new_link(){count++;var o=document.createElement("div"),e='<div class="row"><div class="col-lg-12"><div class="mb-3"><label for="jobTitle1" class="form-label">Job Title</label><input type="text" class="form-control" id="jobTitle1" placeholder="Job title"></div></div><div class="col-lg-6"><div class="mb-3"><label for="companyName" class="form-label">Company Name</label><input type="text" class="form-control" id="companyName" placeholder="Company name"></div></div><div class="col-lg-6"><div class="mb-3"><label for="choices-single-default3" class="form-label">Experience Years</label><div class="row"><div class="col-lg-5"><select class="form-control" data-trigger name="choices-single-default3"> <option value="">Select years</option><option value="Choice 1">2001</option><option value="Choice 2">2002</option><option value="Choice 3">2003</option><option value="Choice 4">2004</option><option value="Choice 5">2005</option><option value="Choice 6">2006</option><option value="Choice 7">2007</option><option value="Choice 8">2008</option><option value="Choice 9">2009</option><option value="Choice 10">2010</option><option value="Choice 11">2011</option><option value="Choice 12">2012</option><option value="Choice 13">2013</option><option value="Choice 14">2014</option><option value="Choice 15">2015</option><option value="Choice 16">2016</option><option value="Choice 17">2017</option><option value="Choice 18">2018</option><option value="Choice 19">2019</option><option value="Choice 20">2020</option><option value="Choice 21">2021</option><option value="Choice 22">2022</option></select></div><div class="col-auto align-self-center">to</div><div class="col-lg-5"><select class="form-control" data-trigger  name="choices-single-default2"><option value="">Select years</option><option value="Choice 1">2001</option><option value="Choice 2">2002</option><option value="Choice 3">2003</option><option value="Choice 4">2004</option><option value="Choice 5">2005</option><option value="Choice 6">2006</option><option value="Choice 7">2007</option><option value="Choice 8">2008</option><option value="Choice 9">2009</option><option value="Choice 10">2010</option><option value="Choice 11">2011</option><option value="Choice 12">2012</option><option value="Choice 13">2013</option><option value="Choice 14">2014</option><option value="Choice 15">2015</option><option value="Choice 16">2016</option><option value="Choice 17">2017</option><option value="Choice 18">2018</option><option value="Choice 19">2019</option><option value="Choice 20">2020</option><option value="Choice 21">2021</option><option value="Choice 22">2022</option></select></div></div></div></div><div class="col-lg-12"><div class="mb-3"><label for="jobDescription" class="form-label">Job Description</label><textarea class="form-control" id="jobDescription" rows="3" placeholder="Enter description"></textarea></div></div><div class="hstack gap-2 justify-content-end"><a class="btn btn-success" href="javascript:deleteEl('+(o.id=count)+')">Delete</a></div></div>',e=(o.innerHTML=document.getElementById("newForm").innerHTML+e,document.getElementById("newlink").appendChild(o),document.querySelectorAll("[data-trigger]"));Array.from(e).forEach(function(o){new Choices(o,{placeholderValue:"This is a placeholder set in the config",searchPlaceholderValue:"This is a search placeholder",searchEnabled:!1})})}function deleteEl(o){o=(d=document).getElementById(o);d.getElementById("newlink").removeChild(o)}
</script>
<script src="{{ URL::asset('/assets/admin/js/app.min.js') }}"></script>
@endsection
