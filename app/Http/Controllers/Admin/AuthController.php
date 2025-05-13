<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
    
    public function redirectToAzure()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function handleAzureCallback(Request $request)
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
            
            $user = User::where('email', $azureUser->getEmail())->first();
            
            if (!$user) {
                $user = User::create([
                    'azure_ad_id' => $azureUser->getId(),
                    'firstname' => $azureUser->user['givenName'] ?? $azureUser->getName(),
                    'lastname' => $azureUser->user['surname'] ?? '',
                    'username' => $azureUser->getNickname() ?? strstr($azureUser->getEmail(), '@', true),
                    'email' => $azureUser->getEmail(),
                    'phone' => $azureUser->user['mobilePhone'] ?? null,
                    'email_verified_at' => now(),
                    'password' => Str::random(16)
                ]);

                // Assign default roles for new users
                $defaultRoles = Role::where('is_default', true)->get();

                foreach ($defaultRoles as $role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }

                // Download and attach avatar if available
                if (!empty($azureUser->getAvatar())) {
                    try {
                        // Download the avatar image
                        $avatarResponse = Http::get($azureUser->getAvatar());
                        
                        if ($avatarResponse->successful()) {
                            // Create a temporary file
                            $tempFile = tempnam(sys_get_temp_dir(), 'avatar_');
                            file_put_contents($tempFile, $avatarResponse->body());
                            
                            // Create an UploadedFile instance
                            $fileName = 'avatar_' . $user->id . '.jpg';
                            $uploadedFile = new \Illuminate\Http\UploadedFile(
                                $tempFile,
                                $fileName,
                                'image/jpeg',
                                null,
                                true
                            );
                            
                            // Upload and attach the new avatar
                            $this->fileService->upload($uploadedFile, 'App\\Models\\User',$user);
                        }
                    } catch (\Exception $e) {
                        Log::channel('auth')->error('Error downloading avatar: ' . $e->getMessage());
                    }
                }
            }

            // Log the user in using Laravel's built-in authentication
            Auth::login($user);
    
            return redirect()->route('admin.dashboard');
        } catch (\Exception $e) {
            Log::channel('auth')->error('Error in handleAzureCallback: ' . $e->getMessage());

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Authentication failed. Please try again.']);
        }
    }

    public function logout(Request $request)
    {
        $isAzureUser = Auth::user() ? !empty(Auth::user()->azure_ad_id) : false;
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($isAzureUser) {
            $azureLogoutUrl = Socialite::driver('azure')->getLogoutUrl(route('admin.login'));
            return redirect($azureLogoutUrl);
        }
        
        return redirect()->route('admin.login');
    }
}
