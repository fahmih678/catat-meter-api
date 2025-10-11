<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PamScopeMiddleware
{
    /**
     * Handle an incoming request.
     * This middleware adds PAM filtering for non-superadmin users
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login first.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        $user = Auth::user();

        // SuperAdmin can access all PAM data - no filtering needed
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // Check if user has PAM association
        if (!$user->pam_id) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with any PAM. Please contact administrator.',
                'error_code' => 'NO_PAM_ASSOCIATION'
            ], 403);
        }

        // Add PAM filter to request for data scoping
        $request->merge([
            'pam_filter' => $user->pam_id,
            'user_pam_id' => $user->pam_id,
            'is_pam_scoped' => true
        ]);

        // Add PAM info to request attributes for controllers
        $request->attributes->set('user_pam_id', $user->pam_id);
        $request->attributes->set('is_superadmin', false);
        $request->attributes->set('user_roles', $user->getRoleNames()->toArray());

        return $next($request);
    }
}
