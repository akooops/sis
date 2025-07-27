@php
    $footerPrograms = getPrograms();

    $footerMenu = getMenu('footer_primary_menu');
    
    $emails = json_decode(getSetting('emails')->value);
    $phones = json_decode(getSetting('phones')->value);
    $address = getSetting('address');

    $facebookUrl = getSetting('social_facebook_url');
    $instagramUrl = getSetting('social_instagram_url');
    $twitterUrl = getSetting('social_twitter_url');
    $youtubeUrl = getSetting('social_youtube_url');
    $linkedinUrl = getSetting('social_linkedin_url');
    $snapchatUrl = getSetting('social_snapchat_url');
@endphp

<footer>
    <div class="container pt-12 pb-8">
        <div class="row">
            <div class="d-flex col-12 col-lg-2 mb-8 mb-lg-0 justify-content-center justify-content-lg-start">
                <div class="widget">
                    <a href="{{route('index')}}">
                        <img class="logo" src="{{ URL::asset('assets/img/logo.png')}}" alt="{{getLanguageKeyLocalTranslation('website_title')}}">
                    </a>
                </div>
            </div>
            <!-- /column -->

            <div class="col-lg-10">
                <div class="row">
                    <div class="col-lg-4 mb-8 mb-lg-0 text-center text-lg-start">
                        <div class="widget ps-0 ps-lg-8">
                            <ul class="list-unstyled mb-0">
                                @foreach ($footerPrograms as $footerProgram)
                                    <li class="program">
                                        <a href="{{route('program', ['slug' => $footerProgram->slug])}}">
                                            {{$footerProgram->getLocalTranslation('title')}}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <!-- /.widget -->
                    </div>
                    <!-- /column -->

                    <div class="col-lg-4 mb-8 mb-lg-0 text-center text-lg-start">
                        <div class="widget ps-0 ps-lg-8">
                            <h4 class="widget-title mb-3">
                                {{getLanguageKeyLocalTranslation('footer_get_in_touch_title')}}
                            </h4>
                            
                            <p>
                                @if($address)
                                    {{$address->value}}
                                    <br /> 
                                @endif
                                @if($emails && is_array($emails))
                                    @foreach ($emails as $email)
                                        <a href="mailto:{{$email}}">
                                            {{$email}}
                                        </a>
                                        @if(!$loop->last)<br />@endif
                                    @endforeach
                                @endif
                                @if($phones && is_array($phones))
                                    @if($emails && is_array($emails))<br />@endif
                                    @foreach ($phones as $phone)
                                        <a href="tel:{{$phone}}">
                                            {{$phone}}
                                        </a>
                                        @if(!$loop->last)<br />@endif
                                    @endforeach
                                @endif
                            </p>
                        </div>
                        <!-- /.widget -->
                    </div>
                    <!-- /column -->
                    
                    <div class="col-lg-2 mb-8 mb-lg-0 text-center text-lg-start">
                        <div class="widget ps-0 ps-lg-4">
                            <h4 class="widget-title mb-3">
                                {{getLanguageKeyLocalTranslation('footer_first_menu_title')}}
                            </h4>

                            <ul class="list-unstyled mb-0">
                                @if($footerMenu)
                                    @foreach ($footerMenu->items as $footerMenuItem)
                                        <li class="nav-item">
                                            <a
                                                href="{{$footerMenuItem->url}}">
                                                {{$footerMenuItem->getLocalTranslation('title')}}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                        <!-- /.widget -->
                    </div>
                    <!-- /column -->

                    <div class="col-lg-2 mb-8 mb-lg-0 text-center text-lg-start">
                        <div class="ps-0 ps-lg-4">
                            <h4 class="widget-title mb-3">
                                {{getLanguageKeyLocalTranslation('footer_nav_menu_title')}}
                            </h4>

                            <nav class="nav social justify-content-center justify-content-lg-start">
                                @if($facebookUrl && !empty($facebookUrl->value))
                                    <a href="{{$facebookUrl->value}}">
                                        <i class="uil uil-facebook-f"></i>
                                    </a>
                                @endif

                                @if($instagramUrl && !empty($instagramUrl->value))
                                    <a href="{{$instagramUrl->value}}">
                                        <i class="uil uil-instagram"></i>
                                    </a>
                                @endif

                                @if($twitterUrl && !empty($twitterUrl->value))
                                    <a href="{{$twitterUrl->value}}">
                                        <i class="uil uil-x-twitter"></i>
                                    </a>
                                @endif

                                @if($youtubeUrl && !empty($youtubeUrl->value))
                                    <a href="{{$youtubeUrl->value}}">
                                        <i class="uil uil-youtube"></i>
                                    </a>
                                @endif

                                @if($linkedinUrl && !empty($linkedinUrl->value))
                                    <a href="{{$linkedinUrl->value}}">
                                        <i class="uil uil-linkedin"></i>
                                    </a>
                                @endif

                                @if($snapchatUrl && !empty($snapchatUrl->value))
                                    <a href="{{$snapchatUrl->value}}">
                                        <i class="uil uil-snapchat-ghost"></i>
                                    </a>
                                @endif
                            </nav>
                            <!-- /.social -->
                        </div>
                        <!-- /.widget -->
                    </div>
                    <!-- /column -->
                </div>
                <!--/.row -->
            </div>
             <!-- /column -->
        </div>
        <!--/.row -->

        <hr class="my-8" />

        <div class="d-flex justify-content-center copyright py-0">
            <p class="mb-0 py-0 text-center">
                {{getLanguageKeyLocalTranslation('footer_all_rights_reserved')}}
            </p>
        </div>
        <!-- /div -->
    </div>
    <!-- /.container -->
</footer>