<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSubmissions\StoreContactSubmissionRequest;
use App\Models\ContactSubmission;
use App\Services\ERegistrationService;
use App\Services\NotificationService;

class ContactSubmissionsController extends Controller
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

    public function storeContactSubmission(StoreContactSubmissionRequest $request)
    {
        $contactSubmission = ContactSubmission::create($request->validated());

        $this->notificationService->createContactSubmissionNotification($contactSubmission);

        $this->eRegistrationService->sendContactSubmission($contactSubmission);

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('contact_submission_success_message'));
    }
}
