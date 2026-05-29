<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inquiries\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Services\ERegistrationService;
use App\Services\NotificationService;

class InquiriesController extends Controller
{
    protected $notificationService;
    protected $eRegistrationService;

    public function __construct(
        NotificationService $notificationService,
        ERegistrationService $eRegistrationService
    ) {
        $this->notificationService = $notificationService;
        $this->eRegistrationService = $eRegistrationService;
    }

    public function storeInquiries(StoreInquiryRequest $request)
    {
        $inquiry = Inquiry::create($request->validated());

        $this->notificationService->createInquiryNotification($inquiry);

        $this->eRegistrationService->sendInquiry($inquiry);

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('inquiry_success_message'));
    }
}
