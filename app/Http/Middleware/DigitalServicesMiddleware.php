<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DigitalServicesMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            if (!$request->expectsJson()) {
                return redirect()->route('login');
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!$user->isDigitalServices()) {
            if (!$request->expectsJson()) {
                abort(403, 'Access denied. Digital Services or Administrator role required.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Access denied. Digital Services role required.'
            ], 403);
        }

        return $next($request);
    }
}
