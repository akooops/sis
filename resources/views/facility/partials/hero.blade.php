{{-- Params: $heroTitle (string), $heroImage (url) --}}
<section class="wrapper banners-section">
    <div class="swiper-container" data-margin="0" data-nav="false" data-dots="false" data-items="1">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide bg-overlay">
                    <div class="banner-img" style="background-image: url('{{ $heroImage }}')"></div>

                    <div class="container h-100">
                        <div class="row h-100 align-items-end px-8 px-lg-0 pb-16">
                            <div class="row px-0 px-lg-4">
                                <div class="col-12 col-lg-8 px-0">
                                    <h1 class="mb-0 animate__animated animate__slideInDown animate__delay-1s">
                                        {{ $heroTitle }}
                                    </h1>
                                </div>
                                <!--/.col -->
                            </div>
                            <!--/.row -->
                        </div>
                        <!--/.row -->
                    </div>
                    <!--/.container -->
                </div>
                <!--/.swiper-slide -->
            </div>
            <!--/.swiper-wrapper -->
        </div>
        <!-- /.swiper -->
    </div>
    <!-- /.swiper-container -->
</section>
<!-- /section -->
