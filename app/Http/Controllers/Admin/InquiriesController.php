<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Inquiries\StoreInquiryRequest;
use App\Http\Requests\VisitBookings\StoreVisitBookingRequest;
use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Program;
use App\Models\VisitBooking;
use App\Models\VisitService;
use App\Models\VisitTimeSlot;
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class InquiriesController extends Controller
{
    public function storeInquiries(StoreInquiryRequest $request)
    {
        $inquiry  = Inquiry::create($request->validated());

        return response()->json([
                'status' => 'success',
                'message' => 'Inquiry created successfully!',
        ], 200);
    }
}
