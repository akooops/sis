<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSubmissions\StoreContactSubmissionRequest;
use App\Models\ContactSubmission;
use App\Services\NotificationService;

class ContactSubmissionsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function storeContactSubmission(StoreContactSubmissionRequest $request)
    {
        $contactSubmission = ContactSubmission::create($request->validated());

        // Create notification
        $this->notificationService->createContactSubmissionNotification($contactSubmission);

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('contact_submission_success_message'));
    }
}
