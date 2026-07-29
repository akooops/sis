<footer class="bg-dark text-inverse facility-footer">
    <div class="container py-13 py-md-15">
        <div class="row gy-6 gy-lg-0">
            <div class="col-md-4 col-lg-4">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{ transOrDefault($facility, 'title') }}
                    </h4>
                    @php $facilityTagline = transOrDefault($facility, 'tagline'); @endphp
                    @if($facilityTagline && !str_starts_with($facilityTagline, 'tagline.'))
                        <p class="mb-4">{{ $facilityTagline }}</p>
                    @endif

                    @if($facility->socials && is_array($facility->socials))
                        <nav class="nav social social-white">
                            @foreach ($facility->socials as $network => $link)
                                @if($link)
                                    <a href="{{ $link }}" target="_blank"><i class="uil uil-{{ $network }}"></i></a>
                                @endif
                            @endforeach
                        </nav>
                    @endif
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->

            <div class="col-md-4 col-lg-4">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{ getLanguageKeyLocalTranslation('facility_footer_contact_title') }}
                    </h4>

                    @if(transOrDefault($facility, 'address') && !str_starts_with(transOrDefault($facility, 'address'), 'address.'))
                        <address class="pe-xl-15 pe-xxl-17">
                            {{ transOrDefault($facility, 'address') }}
                        </address>
                    @endif

                    @if($facility->email)
                        <a href="mailto:{{ $facility->email }}" class="link-body">{{ $facility->email }}</a>
                        <br />
                    @endif

                    @if($facility->phone)
                        <a href="tel:{{ $facility->phone }}" class="link-body" dir="ltr">{{ $facility->phone }}</a>
                        <br />
                    @endif

                    @if($facility->whatsappLink)
                        <a href="{{ $facility->whatsappLink }}" target="_blank" class="link-body" dir="ltr">
                            {{ $facility->whatsapp }} <i class="uil uil-whatsapp" style="color: #25D366;"></i>
                        </a>
                    @endif
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->

            <div class="col-md-4 col-lg-4">
                <div class="widget">
                    <h4 class="widget-title text-white mb-3">
                        {{ getLanguageKeyLocalTranslation('facility_footer_links_title') }}
                    </h4>
                    <ul class="list-unstyled mb-0">
                        <li><a href="{{ facilityRoute('reserve') }}">{{ getLanguageKeyLocalTranslation('facility_nav_reserve') }}</a></li>
                        <li><a href="{{ facilityRoute('contact') }}">{{ getLanguageKeyLocalTranslation('facility_nav_contact') }}</a></li>
                        <li><a href="{{ facilityRoute('events') }}">{{ getLanguageKeyLocalTranslation('facility_nav_events') }}</a></li>
                        <li><a href="{{ route('index') }}">{{ getLanguageKeyLocalTranslation('facility_footer_main_site_link') }}</a></li>
                    </ul>
                </div>
                <!-- /.widget -->
            </div>
            <!-- /column -->
        </div>
        <!--/.row -->

        <hr class="mt-8 mb-6" />

        <p class="d-md-flex align-items-center justify-content-between mb-0">
            <span>© {{ date('Y') }} {{ transOrDefault($facility, 'title') }}. {{ getLanguageKeyLocalTranslation('facility_footer_rights') }}</span>
        </p>
    </div>
    <!-- /.container -->
</footer>
