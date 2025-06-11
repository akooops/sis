<?php 

namespace App\Rules;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Closure;

class ReCaptcha implements ValidationRule

{
    public function __construct()
    {

    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = Setting::where('key', 'google_recaptcha_secret_key')->first();

        if (!$secretKey) {
            $fail(__('validation.custom.recaptcha.not_configured'));
            return;
        }

        if (empty($value)) {
            $fail(__('validation.custom.recaptcha.required'));
            return;
        }

        try {
            $response = Http::asForm()->post("https://www.google.com/recaptcha/api/siteverify", [
                'secret' => $secretKey->value,
                'response' => $value
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if (!($result['success'] ?? false)) {
                    $fail(__('validation.custom.recaptcha.failed'));
                    return;
                }
            } else {
                $fail(__('validation.custom.recaptcha.connection_failed'));
                return;
            }
        } catch (\Exception $e) {
            $fail(__('validation.custom.recaptcha.connection_failed'));
            return;
        }
    }
}