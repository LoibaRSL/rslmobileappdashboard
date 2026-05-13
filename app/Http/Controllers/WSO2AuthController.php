<?php

namespace App\Http\Controllers;

use App\Services\WSO2Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WSO2AuthController extends Controller
{
    protected $wso2Service;

    public function __construct(WSO2Service $wso2Service)
    {
        $this->wso2Service = $wso2Service;
    }

    /**
     * Redirect to WSO2 for authentication
     */
    public function redirectToWSO2()
    {
        Log::info('Redirecting to WSO2 login');
        $authorizationUrl = $this->wso2Service->getAuthorizationUrl();
        return redirect()->away($authorizationUrl);
    }

    /**
     * Handle callback from WSO2
     */
    public function handleWSO2Callback(Request $request)
    {
        Log::info('=== WSO2 Callback Started ===');
        
        $code = $request->get('code');
        $sessionState = $request->get('session_state');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');

        Log::info('Callback parameters:', [
            'code_exists' => !is_null($code),
            'session_state' => $sessionState,
            'error' => $error
        ]);

        // Check for errors
        if ($error) {
            Log::error('WSO2 Callback Error', [
                'error' => $error,
                'description' => $errorDescription
            ]);
            return redirect()->route('login')->with('error', "WSO2 Error: {$errorDescription}");
        }

        // Validate code
        if (!$code) {
            Log::error('No authorization code received');
            return redirect()->route('login')->with('error', 'No authorization code received from WSO2');
        }

        // Get access token
        Log::info('Getting access token from WSO2');
        $tokens = $this->wso2Service->getToken($code);
        if (!$tokens) {
            Log::error('Failed to get access token');
            return redirect()->route('login')->with('error', 'Failed to get access token from WSO2');
        }
        Log::info('Access token received successfully');

        // Get user info
        Log::info('Getting user info from WSO2');
        $userInfo = $this->wso2Service->getUserInfo($tokens['access_token']);
        if (!$userInfo) {
            Log::error('Failed to get user info');
            return redirect()->route('login')->with('error', 'Failed to get user information from WSO2');
        }
        Log::info('User info received', ['user_sub' => $userInfo['sub'] ?? 'unknown']);

        // Extract user data
        $userData = $this->wso2Service->extractUserData($userInfo);
        
        if (!$userData['id']) {
            Log::error('Invalid user data - no ID');
            return redirect()->route('login')->with('error', 'Invalid user data received from WSO2');
        }
        
        Log::info('User data extracted', [
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email']
        ]);

        // Store tokens in session
        session([
            'wso2_tokens' => [
                'access_token' => $tokens['access_token'],
                'id_token' => $tokens['id_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null
            ],
            'wso2_user' => $userData
        ]);
        
        Log::info('Session data stored');

        // Find or create local user
        try {
            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'username' => $userData['username'],
                    'department' => $userData['department'] ?? null,
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'Staff',
                    'is_active' => true
                ]
            );
            Log::info('User created/found in database', ['user_id' => $user->id, 'is_active' => $user->is_active]);
        } catch (\Exception $e) {
            Log::error('Database error:', ['message' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Database error: ' . $e->getMessage());
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('User is inactive', ['user_id' => $user->id]);
            Auth::logout();
            session()->forget(['wso2_user', 'wso2_tokens']);
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Login the user
        Auth::login($user);
        Log::info('User logged in successfully', ['user_id' => $user->id]);

        // Regenerate session for security
        $request->session()->regenerate();
        Log::info('Session regenerated');

        // Store login timestamp
        $user->update(['last_login_at' => now()]);

        Log::info('=== WSO2 Callback Completed Successfully ===');
        
        // Redirect to dashboard
        return redirect()->intended('/dashboard')->with('success', "Welcome back, {$user->name}!");
    }

    /**
     * Logout from the application and WSO2
     */
    public function logout(Request $request)
    {
        Log::info('Logging out user');
        
        $idToken = session('wso2_tokens.id_token');
        
        // Clear local session
        session()->forget(['wso2_user', 'wso2_tokens']);
        Auth::logout();
        
        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to WSO2 logout URL if id_token exists
        if ($idToken) {
            $logoutUrl = $this->wso2Service->getLogoutUrl($idToken);
            Log::info('Redirecting to WSO2 logout');
            return redirect()->away($logoutUrl);
        }

        return redirect('/');
    }
}