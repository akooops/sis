<?php

namespace App\Services;

use App\Models\ContactSubmission;
use App\Models\Inquiry;
use App\Models\VisitBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ERegistrationService
{
    /**
     * Map of submission types to their E-Registration API endpoint paths.
     */
    protected array $endpointMap = [
        'contact_submission' => 'contact-submissions',
        'inquiry' => 'inquiries',
        'visit_booking' => 'visit-bookings',
    ];

    /**
     * Send a contact submission copy to E-Registration.
     */
    public function sendContactSubmission(ContactSubmission $contactSubmission): bool
    {
        return $this->send('contact_submission', $contactSubmission->toArray());
    }

    /**
     * Send an inquiry copy to E-Registration.
     */
    public function sendInquiry(Inquiry $inquiry): bool
    {
        return $this->send('inquiry', $inquiry->toArray());
    }

    /**
     * Send a visit booking copy to E-Registration.
     */
    public function sendVisitBooking(VisitBooking $visitBooking): bool
    {
        $visitBooking->loadMissing(['visitService', 'visitTimeSlot']);

        return $this->send('visit_booking', $visitBooking->toArray());
    }

    /**
     * Send a copy of a record to the E-Registration API.
     *
     * Failures are logged but never thrown so the originating request
     * to the website is not impacted by remote API issues.
     */
    public function send(string $type, array $data): bool
    {
        $apiUrl = getSetting('eregistration_api_url')?->value;
        $publicKey = getSetting('eregistration_public_api_key')?->value;
        $secretKey = getSetting('eregistration_secret_api_key')?->value;

        if (empty($apiUrl)) {
            Log::warning('ERegistrationService: E-Registration API URL is not configured. Skipping.', [
                'type' => $type,
            ]);
            return false;
        }

        $path = $this->endpointMap[$type] ?? $type;
        $fullUrl = rtrim($apiUrl, '/') . '/' . $path;

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $publicKey ?? '',
                'X-API-Secret' => $secretKey ?? '',
            ])
                ->timeout(10)
                ->connectTimeout(5)
                ->post($fullUrl, $data);

            if (!$response->successful()) {
                Log::error('ERegistrationService: failed to send submission.', [
                    'type' => $type,
                    'url' => $fullUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('ERegistrationService: exception while sending submission.', [
                'type' => $type,
                'url' => $fullUrl,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
