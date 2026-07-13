<?php

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\LoginData;
use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Password login. Gated on approval (verified_at): a pending account can't
     * sign in until an admin approves it.
     */
    public function login(LoginData $data): JsonResponse
    {
        if (! Auth::validate(['email' => $data->email, 'password' => $data->password])) {
            return $this->respond(null, 'These credentials do not match our records.', 401);
        }

        $user = User::where('email', $data->email)->first();

        if (! $user->verified_at) {
            return $this->respond(null, 'Your account is pending approval.', 403);
        }

        Auth::login($user, $data->remember);
        request()->session()->regenerate();

        return $this->respond(UserData::from($user->load('roles')), 'Logged in successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->respond(null, 'Logged out successfully');
    }

    public function redirectToAzure(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    /**
     * Azure SSO callback. Never grants access on its own:
     *  - existing user  -> link azure_ad_id (so they can use password OR Azure)
     *  - unknown email  -> create a PENDING account (no roles, awaiting approval)
     * A user only signs in once an admin has approved them (verified_at set).
     */
    public function handleAzureCallback(Request $request): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (Exception $e) {
            Log::channel('auth')->error('Azure callback failed: '.$e->getMessage());

            return redirect('/')->with('error', 'Authentication failed. Please try again.');
        }

        $user = User::where('email', $azureUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'firstname' => $azureUser->user['givenName'] ?? $azureUser->getName() ?? '',
                'lastname' => $azureUser->user['surname'] ?? '',
                'username' => $this->uniqueUsername($azureUser->getEmail()),
                'email' => $azureUser->getEmail(),
                'phone' => $azureUser->user['mobilePhone'] ?? null,
                'password' => Str::random(32),
                'azure_ad_id' => $azureUser->getId(),
            ]);
        } elseif (! $user->azure_ad_id) {
            $user->update(['azure_ad_id' => $azureUser->getId()]);
        }

        if (! $user->verified_at) {
            return redirect('/')->with('error', 'Your account is pending approval.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }

    protected function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'user';
        $username = $base;

        while (User::where('username', $username)->exists()) {
            $username = $base.'-'.Str::lower(Str::random(4));
        }

        return $username;
    }
}
