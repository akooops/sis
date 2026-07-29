<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\StoreFacilityContactRequest;
use App\Models\ContactSubmission;
use App\Services\NotificationService;

class FacilityContactController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(StoreFacilityContactRequest $request)
    {
        $facility = $request->attributes->get('facility');

        $contactSubmission = ContactSubmission::create(array_merge(
            $request->validated(),
            [
                'facility_id' => $facility->id,
            ]
        ));

        $this->notificationService->createFacilityContactNotification($contactSubmission);

        return redirect()->back()
            ->with('success', getLanguageKeyLocalTranslation('facility_contact_success_message'));
    }
}
