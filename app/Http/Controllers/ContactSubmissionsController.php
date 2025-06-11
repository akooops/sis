<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSubmissions\StoreContactSubmissionRequest;
use App\Models\ContactSubmission;

class ContactSubmissionsController extends Controller
{
    public function storeContactSubmission(StoreContactSubmissionRequest $request)
    {
        $contactSubmission  = ContactSubmission::create($request->validated());

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('contact_submission_success_message'));
    }
}
