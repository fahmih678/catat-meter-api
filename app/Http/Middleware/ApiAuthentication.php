<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please login first.',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Check if token is still valid
        $token = $request->user()->currentAccessToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token. Please login again.',
                'code' => 'INVALID_TOKEN'
            ], 401);
        }

        // Check token expiration (if expires_at is set)
        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired. Please refresh your token or login again.',
                'code' => 'TOKEN_EXPIRED'
            ], 401);
        }

        // Add additional headers for API responses
        $response = $next($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('X-API-Version', 'v1');
            $response->headers->set('X-Request-ID', uniqid());
        }

        return $response;
    }
}
