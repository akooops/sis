@php
    $privacyPage = getPageBySlug('privacy-policy');
@endphp

@if($privacyPage)
<a id="privacy-popup-button" href="#" class="d-none" data-bs-toggle="modal" data-bs-target="#privacy-popup">Cookie</a>

<div class="modal fade modal-bottom-center" id="privacy-popup" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body p-6">
                <div class="row">
                    <div class="col-md-12 col-lg-8 mb-4 mb-lg-0 my-auto align-items-center">
                        <h2 class="mb-2">
                            {{getLanguageKeyLocalTranslation('privacy_popup_title')}}
                        </h2>
                        <p class="mb-0">
                            {{getLanguageKeyLocalTranslation('privacy_popup_description')}} 
                            
                            <a href="{{route('page', ['slug' => $privacyPage->slug])}}">
                                {{getLanguageKeyLocalTranslation('privacy_popup_button_read_more')}}
                            </a>
                        </p> 
                    </div>
                    <!--/column -->
                    <div class="col-md-5 col-lg-4 text-lg-end my-auto">
                        <a href="#" class="btn btn-primary rounded-pill" data-bs-dismiss="modal" aria-label="Close">
                            {{getLanguageKeyLocalTranslation('privacy_popup_button')}}
                        </a>
                    </div>
                    <!--/column -->
                </div>
                <!--/.row -->
            </div>
            <!--/.modal-body -->
        </div>
        <!--/.modal-content -->
    </div>
    <!--/.modal-dialog -->
</div>
<!--/.modal -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hasAcceptedPrivacyPolicy = localStorage.getItem('privacy-policy-accepted');
        
        if (!hasAcceptedPrivacyPolicy) {
            setTimeout(function() {
                document.getElementById('privacy-popup-button').click();
            }, 5000);
        }
        
        document.addEventListener('click', function(e) {
            if (e.target && e.target.closest('[data-bs-dismiss="modal"]')) {
                localStorage.setItem('privacy-policy-accepted', 'true');
            }
        });
        
        const privacyModal = document.getElementById('privacy-popup');
        if (privacyModal) {
            privacyModal.addEventListener('hidden.bs.modal', function() {
                localStorage.setItem('privacy-policy-accepted', 'true');
            });
        }
    });
</script>

@endif