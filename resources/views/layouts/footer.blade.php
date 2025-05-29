@php
    $primaryMenuSetting = getSetting('footer_primary_menu');
    $primaryMenu = null;

    if($primaryMenuSetting && $primaryMenuSetting->type == "menu"){
        $primaryMenu = getMenu($primaryMenuSetting->value);
    }

    $secondaryMenuSetting = getSetting('footer_secondary_menu');
    $secondaryMenu = null;

    if($secondaryMenuSetting && $secondaryMenuSetting->type == "menu"){
        $secondaryMenu = getMenu($secondaryMenuSetting->value);
    }
@endphp

<footer class="bg-primary text-inverse">
    <div class="container pt-13 pt-md-15 pb-7">
        <div class="row gy-6 gy-lg-0">
            <div class="col-lg-4">
                <div class="widget">
                    <a href="{{route('index')}}">
                        <img class="logo-canvas" src="{{ URL::asset('assets/img/logo.png')}}" alt="" />
                    </a>
                    <h3 class="h2 my-3 text-white">Join the Community</h3>
                    <p class="lead mb-5">Let's make something great together. We are trusted by over 5000+ clients. Join them by using our services and grow your business.</p>
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->

            <div class="col-md-4 col-lg-2 offset-lg-2">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">Need Help?</h4>
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
                    <h4 class="widget-title text-white mb-3">Learn More</h4>
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
                    <h4 class="widget-title text-white mb-3">Get in Touch</h4>
                    <address>Moonshine St. 14/05 Light City, London, United Kingdom</address>
                    <a href="mailto:first.last@email.com">info@email.com</a><br /> 00 (123) 456 78 90
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->
        </div>
        <!--/.row -->
        <hr class="mt-13 mt-md-15 mb-7" />
        <div class="d-md-flex align-items-center justify-content-between">
            <p class="mb-2 mb-lg-0">© 2025 Saudi international schools. All rights reserved.</p>
            <nav class="nav social social-white text-md-end">
                <a href="#"><i class="uil uil-twitter"></i></a>
                <a href="#"><i class="uil uil-facebook-f"></i></a>
                <a href="#"><i class="uil uil-dribbble"></i></a>
                <a href="#"><i class="uil uil-instagram"></i></a>
                <a href="#"><i class="uil uil-youtube"></i></a>
            </nav>
            <!-- /.social -->
        </div>
        <!-- /div -->
    </div>
    <!-- /.container -->
</footer>