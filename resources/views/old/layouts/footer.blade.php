@php
    $primaryMenu = getMenu('footer_primary_menu');
    $secondaryMenu = getMenu('footer_secondary_menu');
    
    $facebookUrl = getSetting('social_facebook_url');
    $instagramUrl = getSetting('social_instagram_url');
    $twitterUrl = getSetting('social_twitter_url');
    $youtubeUrl = getSetting('social_youtube_url');
@endphp

<footer class="bg-primary text-inverse">
    <div class="container pt-13 pt-md-15 pb-7">
        <div class="row gy-6 gy-lg-0">
            <div class="col-lg-4">
                <div class="widget">
                    <a href="{{route('index')}}">
                        <img class="logo-canvas" src="{{ URL::asset('assets/img/logo.png')}}" alt="" />
                    </a>
                    <h3 class="h2 my-3 text-white">
                        {{getLanguageKeyLocalTranslation('footer_main_title')}}
                    </h3>
                    <p class="lead mb-5">
                        {{getLanguageKeyLocalTranslation('footer_main_subtitle')}}
                    </p>
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->

            <div class="col-md-4 col-lg-2 offset-lg-2">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{getLanguageKeyLocalTranslation('footer_first_menu_title')}}
                    </h4>
                    <ul class="footer-menu list-unstyled mb-0">
                        @if($primaryMenu)
                            @foreach ($primaryMenu->items as $primaryMenuItem)
                                <li>
                                    <a
                                        href="{{
                                            $primaryMenuItem->page 
                                                ? route('page', ['slug' => $primaryMenuItem->page->slug]) 
                                                : $primaryMenuItem->url
                                            }}">
                                        {{$primaryMenuItem->getLocalTranslation('title')}}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->
            <div class="col-md-4 col-lg-2">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{getLanguageKeyLocalTranslation('footer_second_menu_title')}}
                    </h4>
                    <ul class="footer-menu list-unstyled mb-0">
                        @if($secondaryMenu)
                            @foreach ($secondaryMenu->items as $secondaryMenuItem)
                                <li>
                                    <a
                                        href="{{
                                            $secondaryMenuItem->page 
                                                ? route('page', ['slug' => $secondaryMenuItem->page->slug]) 
                                                : $secondaryMenuItem->url
                                            }}">
                                        {{$secondaryMenuItem->getLocalTranslation('title')}}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->
            <div class="col-md-4 col-lg-2">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{getLanguageKeyLocalTranslation('footer_get_in_touch_title')}}
                    </h4>
                    <address>
                        {{getLanguageKeyLocalTranslation('get_in_touch_address')}}
                    </address>
                    <a href="mailto:{{getLanguageKeyLocalTranslation('get_in_touch_email')}}">
                        {{getLanguageKeyLocalTranslation('get_in_touch_email')}}
                    </a>
                    <br /> {{getLanguageKeyLocalTranslation('get_in_touch_phone')}}
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->
        </div>
        <!--/.row -->
        <hr class="mt-13 mt-md-15 mb-7" />
        <div class="d-md-flex align-items-center justify-content-between">
            <p class="mb-2 mb-lg-0">
                {{getLanguageKeyLocalTranslation('footer_all_rights_reserved')}}
            </p>

            <nav class="nav social social-white text-md-end">
                @if($facebookUrl)
                    <a href="{{$facebookUrl->value}}">
                        <i class="uil uil-facebook-f"></i>
                    </a>
                @endif

                @if($instagramUrl)
                    <a href="{{$instagramUrl->value}}">
                        <i class="uil uil-instagram"></i>
                    </a>
                @endif

                @if($twitterUrl)
                    <a href="{{$twitterUrl->value}}">
                        <i class="uil uil-twitter"></i>
                    </a>
                @endif

                @if($youtubeUrl)
                    <a href="{{$youtubeUrl->value}}">
                        <i class="uil uil-youtube"></i>
                    </a>
                @endif
            </nav>
            <!-- /.social -->
        </div>
        <!-- /div -->
    </div>
    <!-- /.container -->
</footer>