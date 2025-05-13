@extends('layouts.master')

@section('title', 'Nous contacter')
@section('description', "IEC, fondé en 2020, encourage à sortir de sa zone de confort et à abandonner les excuses. Pendant deux ans, le club a organisé divers événements pour les étudiants en génie industriel et les personnes cherchant à améliorer leur parcours académique et professionnel, favorisant ainsi la croissance personnelle et l'excellence.")
@section('canonical', route('contact'))

@section('css')
@endsection

@section('content')
<section class="wrapper bg-secondary">
    <div class="container py-12 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <h1 class="display-1 mb-3">
                    Nous Contacter
                </h1>
                <p class="fs-16 text-gray px-2">
                    Explorez notre collection d'articles variés pour découvrir des perspectives riches et informatives sur divers sujets. Contactez-nous pour en savoir plus.
                </p>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper">
    <div class="container">
        <div class="row pt-12 mb-14 mb-md-17">
            <div class="row">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <h2 class="fs-16 text-uppercase text-line text-primary mb-3">Nous Contacter</h2>
                </div>
                <!-- /column -->
            </div>
            <!-- /.row -->

            <form method="post" action="{{route('contact.store')}}">
                @method('post')
                @csrf
                <div class="messages"></div>
                <div class="mb-2">
                    @include('layouts.messages')
                </div>
                <div class="row gx-4">
                    <div class="col-md-6">
                        <div class="form-floating mb-4">
                            <input id="form_name" type="text" name="firstname" value="{{old('firstname')}}" class="form-control bg-secondary">
                            <label for="form_name">
                                Nom
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
                            <input id="form_lastname" type="text" name="lastname" value="{{old('lastname')}}" class="form-control bg-secondary">
                            <label for="form_lastname">
                                Prénom
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
                            <input id="form_email" type="text" name="email" value="{{old('email')}}" class="form-control bg-secondary">
                            <label for="form_email">
                                Email
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
                            <input id="form_email" type="text" name="subject" value="{{old('subject')}}" class="form-control bg-secondary">
                            <label for="form_email">
                                Objet
                            </label>
                            @error('subject')
                            <div class="invalid-feedback d-flex">
                                {{$message}}
                            </div>
                            @enderror
                        </div>
                    </div>
                    <!-- /column -->
                    <div class="col-12">
                        <div class="form-floating mb-4">
                            <textarea id="form_message" name="message" class="form-control bg-secondary" style="height: 150px">{{old('message')}}</textarea>
                            <label for="form_message">
                                Votre message
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
                        <div>
                            <div class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_KEY') }}"></div>
                            <label for="form_message">
                            @error('g-recaptcha-response')
                            <div class="invalid-feedback d-flex">
                                {{$message}}
                            </div>
                            @enderror
                        </div>
                    </div>
                    <!-- /column -->
                    <div class="col-12">
                        <input type="submit" class="btn btn-md btn-primary rounded" value="Envoyer">
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
<script src='https://www.google.com/recaptcha/api.js?hl=fr'></script>
@endsection