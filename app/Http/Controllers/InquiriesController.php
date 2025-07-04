<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inquiries\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Services\NotificationService;

class InquiriesController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function storeInquiries(StoreInquiryRequest $request)
    {
        $inquiry = Inquiry::create($request->validated());

        // Create notification
        $this->notificationService->createInquiryNotification($inquiry);

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('inquiry_success_message'));
    }
}
