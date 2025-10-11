<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class RoleHelper
{
    /**
     * Check if current user is SuperAdmin
     */
    public static function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->hasRole('superadmin');
    }

    /**
     * Check if current user is Admin PAM
     */
    public static function isAdminPam(): bool
    {
        return Auth::check() && Auth::user()->hasRole('admin_pam');
    }

    /**
     * Check if current user is Catat Meter
     */
    public static function isCatatMeter(): bool
    {
        return Auth::check() && Auth::user()->hasRole('catat_meter');
    }

    /**
     * Check if current user is Pembayaran
     */
    public static function isPembayaran(): bool
    {
        return Auth::check() && Auth::user()->hasRole('pembayaran');
    }

    /**
     * Get current user's PAM ID
     */
    public static function getUserPamId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        return Auth::user()->pam_id;
    }

    /**
     * Check if user can access PAM data
     */
    public static function canAccessPam(int $pamId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // SuperAdmin can access all PAMs
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Other roles can only access their own PAM
        return $user->pam_id === $pamId;
    }

    /**
     * Get accessible PAM IDs for current user
     */
    public static function getAccessiblePamIds(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();

        // SuperAdmin can access all PAMs
        if ($user->hasRole('superadmin')) {
            return \App\Models\Pam::pluck('id')->toArray();
        }

        // Other roles can only access their own PAM
        return $user->pam_id ? [$user->pam_id] : [];
    }

    /**
     * Check if user has management permissions
     */
    public static function hasManagementAccess(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::user()->hasAnyRole(['superadmin', 'admin_pam']);
    }

    /**
     * Check if user can modify data
     */
    public static function canModifyData(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::user()->hasAnyRole(['superadmin', 'admin_pam', 'catat_meter']);
    }

    /**
     * Check if user can access billing features
     */
    public static function canAccessBilling(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::user()->hasAnyRole(['superadmin', 'admin_pam', 'pembayaran']);
    }

    /**
     * Get user role hierarchy level
     */
    public static function getRoleLevel(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        $user = Auth::user();

        if ($user->hasRole('superadmin')) return 4;
        if ($user->hasRole('admin_pam')) return 3;
        if ($user->hasRole('catat_meter')) return 2;
        if ($user->hasRole('pembayaran')) return 1;

        return 0;
    }

    /**
     * Get formatted user role info
     */
    public static function getUserRoleInfo(): array
    {
        if (!Auth::check()) {
            return [
                'is_authenticated' => false,
                'roles' => [],
                'permissions' => [],
                'pam_id' => null,
                'level' => 0
            ];
        }

        $user = Auth::user();

        return [
            'is_authenticated' => true,
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'pam_id' => $user->pam_id,
            'level' => self::getRoleLevel(),
            'is_superadmin' => self::isSuperAdmin(),
            'has_management_access' => self::hasManagementAccess(),
            'can_modify_data' => self::canModifyData(),
            'can_access_billing' => self::canAccessBilling()
        ];
    }
}
