<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inquiries\StoreInquiryRequest;
use App\Models\Inquiry;

class InquiriesController extends Controller
{
    public function storeInquiries(StoreInquiryRequest $request)
    {
        $inquiry  = Inquiry::create($request->validated());

        return redirect()->back()
                        ->with('success', getLanguageKeyLocalTranslation('inquiry_success_message'));
    }
}
