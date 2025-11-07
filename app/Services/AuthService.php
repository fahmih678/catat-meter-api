<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Check if current user is superadmin
     */
    public static function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->hasRole('superadmin');
    }

    /**
     * Check if current user has specific role
     */
    public static function hasRole(string $role): bool
    {
        return Auth::check() && Auth::user()->hasRole($role);
    }

    /**
     * Check if current user has specific permission
     */
    public static function hasPermission(string $permission): bool
    {
        return Auth::check() && Auth::user()->hasPermissionTo($permission);
    }

    /**
     * Log successful login
     */
    public static function logLogin(string $message = 'Login successful'): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('User login: ' . $message, [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name'),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Log failed login attempt
     */
    public static function logFailedLogin(string $email, string $reason = 'Authentication failed'): void
    {
        Log::warning('Failed login attempt: ' . $reason, [
            'email' => $email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess(string $route = '', string $reason = 'Unauthorized access'): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::warning('Unauthorized access attempt: ' . $reason, [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name'),
                'route' => $route,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Force logout user with logging
     */
    public static function forceLogout(string $reason = 'Security policy violation'): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            Log::warning('Force logout user: ' . $reason, [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    /**
     * Get current user info for logging
     */
    public static function getCurrentUserInfo(): array
    {
        if (Auth::check()) {
            $user = Auth::user();
            return [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ];
        }

        return [];
    }

    /**
     * Check if user can access system (superadmin only)
     */
    public static function canAccessSystem(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $isSuperAdmin = self::isSuperAdmin();

        if (!$isSuperAdmin) {
            self::logUnauthorizedAccess(request()->path(), 'Non-superadmin user attempted to access system');
        }

        return $isSuperAdmin;
    }
}