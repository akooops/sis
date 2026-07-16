<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Auth\LoginData;
use App\Data\User\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\States\User\Verified;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Sign-in for the admin app — password or Azure SSO, both ending in the same
 * Sanctum session. These routes live under the admin prefix but deliberately
 * outside verify.auth: gating them behind authentication would leave no way in.
 * Authenticating is not admission — every path here ends at $user->canLogin(),
 * true only for an Approved account. The Azure endpoints redirect a browser
 * rather than returning the JSON envelope.
 */
class AuthController extends Controller
{
    /**
     * Password login. Gated on status: only an Approved account can sign in.
     */
    public function login(LoginData $data): JsonResponse
    {
        if (! Auth::validate(['email' => $data->email, 'password' => $data->password])) {
            return $this->respond(null, 'These credentials do not match our records.', 401);
        }

        $user = User::where('email', $data->email)->first();

        if (! $user->canLogin()) {
            return $this->respond(null, 'Your account is pending approval.', 403);
        }

        Auth::login($user, $data->remember);
        request()->session()->regenerate();

        return $this->respond(UserData::from($user), 'Logged in successfully');
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
     *  - unknown email  -> create a VERIFIED account (no roles, awaiting approval)
     * Azure proves who they are, not that they may come in: a user signs in only
     * once an admin has approved them.
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
                'status' => Verified::class,
            ]);
        } elseif (! $user->azure_ad_id) {
            $user->update(['azure_ad_id' => $azureUser->getId()]);
        }

        if (! $user->canLogin()) {
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
