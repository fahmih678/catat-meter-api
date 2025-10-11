<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
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

        // Check if user account is active
        if ($user->deleted_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Account has been deactivated. Please contact administrator.',
                'error_code' => 'ACCOUNT_DEACTIVATED'
            ], 403);
        }

        // SuperAdmin has all permissions
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        // Log unauthorized access attempt
        Log::warning('Unauthorized permission access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->getRoleNames()->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'required_permissions' => $permissions,
            'route' => $request->route()->getName() ?? $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Access denied. You do not have the required permission.',
            'error_code' => 'INSUFFICIENT_PERMISSION',
            'required_permissions' => $permissions,
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ], 403);
    }
}
