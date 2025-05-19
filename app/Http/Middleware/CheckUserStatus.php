<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckUserStatus Middleware
 * 
 * This middleware enforces the business rule that inactive users cannot access
 * protected resources, even if they have a valid authentication token.
 * It runs after auth:sanctum middleware in the request lifecycle.
 */
class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * This method checks if the authenticated user is active, and if not,
     * aborts the request with a 403 Forbidden response. This provides an
     * additional layer of security beyond token validation, allowing for
     * immediate account deactivation regardless of token validity.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if no authenticated user (auth middleware will handle that)
        if (!$request->user()) {
            return $next($request);
        }
        
        // Check if the user is active
        if (!$request->user()->isActive()) {
            // If inactive, abort with forbidden response
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact an administrator.'
            ], 403);
        }
        
        // If user is active, proceed with the request
        return $next($request);
    }
}

// app/Http/Middleware/CheckUserStatus.php