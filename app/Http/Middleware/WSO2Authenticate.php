<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WSO2Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is already authenticated
        if (Auth::check()) {
            Log::info('User already authenticated', ['user_id' => Auth::id()]);
            return $next($request);
        }

        // Get WSO2 user info from session
        $wso2User = session('wso2_user');
        
        if (!$wso2User || !isset($wso2User['id'])) {
            Log::info('No WSO2 user in session, redirecting to login');
            return redirect()->route('auth.wso2.login');
        }

        Log::info('WSO2 user found in session', ['wso2_id' => $wso2User['id']]);

        // Find or create local user
        try {
            $user = User::updateOrCreate(
                ['wso2_id' => $wso2User['id']],
                [
                    'name' => $wso2User['name'],
                    'email' => $wso2User['email'],
                    'username' => $wso2User['username'],
                    'department' => $wso2User['department'] ?? null,
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'Staff',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Middleware database error:', ['message' => $e->getMessage()]);
            return redirect()->route('auth.wso2.login')->with('error', 'Database error occurred');
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('User is inactive in middleware', ['user_id' => $user->id]);
            Auth::logout();
            session()->forget(['wso2_user', 'wso2_tokens']);
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Login the user
        Auth::login($user);
        Log::info('User authenticated via middleware', ['user_id' => $user->id]);

        return $next($request);
    }
}