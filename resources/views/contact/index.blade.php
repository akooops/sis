@extends('layouts.master')

@section('title')
@if(app()->getLocale() == 'en'){{$page->title}}@else{{$page->title_ar}}@endif
@endsection

@section('description')
@if(app()->getLocale() == 'en'){{$page->description}}@else{{$page->description_ar}}@endif
@endsection

@section('css')
<script src='https://www.google.com/recaptcha/api.js{{(app()->getLocale() == 'en') ? '' : '?hl=ar'}}'></script>
@endsection
@section('content')
<section class="wrapper bg-light">
  <div class="container">
    <div class="row pt-12 mb-14 mb-md-17">
      <h2 class="display-4 fs-26 mb-8 text-center text-lg-start text-gold">
          @lang('translations.contact_us_text'):
      </h2>      

      <form class="contact-form needs-validation" method="post" action="{{route('user.contact.store')}}">
        @method('post')
        @csrf
        <div class="messages"></div>
          <div class="mb-2">
            @include('layouts.messages')
          </div>

          <div class="row gx-4">
            <div class="col-md-6">
                <div class="form-floating mb-4">
                <input id="form_name" type="text" name="firstname" value="{{old('firstname')}}" class="form-control">
                <label for="form_name">
                  @lang('translations.firstname_text')
                </label>
                @error('firstname')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror
                </div>
            </div>
            <!-- /column -->
            <div class="col-md-6">
                <div class="form-floating mb-4">
                <input id="form_lastname" type="text" name="lastname" value="{{old('lastname')}}" class="form-control">
                <label for="form_lastname">
                  @lang('translations.lastname_text')
                </label>
                @error('lastname')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror                                    
              </div>
            </div>
            <!-- /column -->
            <div class="col-md-6">
                <div class="form-floating mb-4">
                <input id="form_email" type="text" name="email" value="{{old('email')}}" class="form-control">
                <label for="form_email">
                  @lang('translations.email_text')
                </label>

                @error('email')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror
              </div>
            </div>
            <!-- /column -->
            <div class="col-md-6">
                <div class="form-floating mb-4">
                <input id="form_email" type="text" name="phone" value="{{old('phone')}}" class="form-control">
                <label for="form_email">
                  @lang('translations.phone_text')
                </label>
                @error('phone')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror
              </div>
            </div>
            <!-- /column -->
            <div class="col-12">
                <div class="form-floating mb-4">
                <textarea id="form_message" name="message" class="form-control" style="height: 150px">{{old('message')}}</textarea>
                <label for="form_message">
                  @lang('translations.message_text')
                </label>
                @error('message')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror
              </div>
            </div>
            <!-- /column -->

              <div class="col-12">
                <div class="mb-4">
                <div class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_KEY') }}"></div>                <label for="form_message">
                @error('g-recaptcha-response')
                  <div class="invalid-feedback d-flex">
                    {{$message}}
                  </div>
                @enderror
              </div>
            </div>
            <!-- /column -->
            
            <div class="col-12 text-center">
              <input type="submit" class="btn btn-primary rounded-pill btn-send mb-3" value="@lang('translations.contact_send_msg_cta')">
            </div>
        </div>
        <!-- /.row -->
    </form>
    <!-- /form -->    
    </div>
    <!-- /row -->
  </div>
  <!-- /.container -->
</section>
<!-- /section -->
@endsection

@section('script')
@endsection
