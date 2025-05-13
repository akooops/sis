@extends('admin.layouts.master-without-nav')
@section('title')
Sign in
@endsection

@section('content')
<div class="auth-page-wrapper pt-5">
    <!-- auth page content -->
    <div class="auth-page-content mt-12">
        <div class="container">

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-4">

                        <div class="card-body p-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Welcome Back !</h5>
                                    <p class="text-muted">Sign in to continue to admin panel.</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <form action="{{ route('admin.auth.login') }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input name="email" type="email" class="form-control" id="email" placeholder="Enter email">
                                            @error('email')
                                                <span class="text-danger mt-2" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        

                                        <div class="mb-3">
                                            <label class="form-label" for="password-input">Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input name="password" type="password" class="form-control pe-5 password-input" placeholder="Enter password" id="password-input">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon material-shadow-none" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                @error('password')
                                                    <span class="text-danger mt-2" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" name="remember" type="checkbox" value="" id="remember">
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit">Sign In</button>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <div class="signin-other-title">
                                                <h5 class="fs-13 mb-4 title">Or sign in with</h5>
                                            </div>
                                            <div>
                                                <a type="button" href="{{route('admin.auth.azure')}}" class="btn btn-primary btn-icon waves-effect waves-light">
                                                    <i class="ri-microsoft-fill fs-16"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->

                </div>
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end auth page content -->
</div>
@endsection

@section('script')
<script src="{{ URL::asset('assets/admin/js/pages/password-addon.init.js') }}"></script>
@endsection
