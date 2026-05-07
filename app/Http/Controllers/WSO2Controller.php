<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WSO2Controller extends Controller
{
    /**
     * Redirect the user to the WSO2 authentication page.
     */
    public function redirectToProvider()
    {
        $state = Str::random(40);
        session(['state' => $state]);

        Log::info('Redirecting to WSO2 provider with state', ['state' => $state]);

        return Socialite::driver('wso2')
            ->stateless()
            ->with(['state' => $state, 'prompt' => 'login'])
            ->redirect();
    }

    /**
     * Handle the callback from WSO2 after authentication.
     */
   public function handleProviderCallback(Request $request)
{
    Log::info('Entering handleProviderCallback');

    $state = $request->input('state');
    Log::info('State from request', ['request_state' => $state, 'session_state' => session('state')]);

    if (strlen($state) > 0 && $state === session('state')) {
        try {
            // Retrieve user info from WSO2
            $socialiteUser = Socialite::driver('wso2')->stateless()->user();
            Log::info('Retrieved user from WSO2', (array) $socialiteUser);

            // Store the ID token in session for logout
            session(['id_token' => $socialiteUser->token]);

            $username = $socialiteUser->user['username'];
            Log::info('Attempting to retrieve user', ['username' => $username]);

            // CORRECTED: FirstOrCreate needs to check by username first
            $user = User::firstOrCreate(
                // Check if user exists by username
                ['username' => $socialiteUser->user['username']],
                // These values are only used if the user is created
                [
                    'name'       => $socialiteUser->name ?? $socialiteUser->user['name'],
                    'email'      => $socialiteUser->email,
                    'department' => $socialiteUser->user['department'] ?? $socialiteUser->attributes['department'] ?? null,
                    'password'   => bcrypt(Str::random(16)),
                    'role'       => 'Staff',
                ]
            );

            Log::info('User found/created', [
                'user_id' => $user->id,
                'username' => $user->username,
                'department' => $user->department,
                'was_created' => $user->wasRecentlyCreated ?? false
            ]);

            if ($user) {
                // Log the user in
                Auth::login($user);
                Log::info('User logged in successfully', ['username' => $user->username]);

                // Verify login
                if (Auth::check()) {
                    Log::info('User authentication confirmed');
                    return redirect()->intended('/admin');
                } else {
                    Log::error('Auth::login() called, but Auth::check() failed.');
                    return redirect('/login/wso2')->withErrors(['auth' => 'Authentication failed.']);
                }
            } else {
                Log::error('User not found/created in database', ['username' => $username]);
                return redirect('/login/wso2')->withErrors(['user' => 'User not found in our records.']);
            }
        } catch (\Exception $e) {
            Log::error('Exception during WSO2 callback handling', ['error' => $e->getMessage()]);
            return redirect('/login/wso2')->withErrors(['auth' => 'An error occurred during authentication.']);
        }
    } else {
        Log::error('Invalid state parameter', ['session_state' => session('state'), 'request_state' => $state]);
        return redirect('/login/wso2')->withErrors(['state' => 'Invalid state parameter']);
    }
}
    /**
     * Log the user out locally and redirect to WSO2 logout.
     */
    public function logout(Request $request)
    {
        Log::info('Logging out user');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $clientId = config('services.wso2.client_id');
        $redirectUri = config('services.wso2.post_logout_redirect_uri');
        $wso2LogoutUrl = config('services.wso2.logout_url');

        Log::info('Redirecting to WSO2 logout URL', ['client_id' => $clientId, 'redirect_uri' => $redirectUri]);

        return redirect("{$wso2LogoutUrl}?client_id={$clientId}&post_logout_redirect_uri={$redirectUri}");
    }
}
