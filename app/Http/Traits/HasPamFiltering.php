<?php

namespace App\Http\Traits;

use App\Helpers\RoleHelper;
use Illuminate\Http\JsonResponse;

trait HasPamFiltering
{
    /**
     * Check if user can access specific PAM data
     */
    protected function checkPamAccess(int $pamId): ?JsonResponse
    {
        if (!RoleHelper::canAccessPam($pamId)) {
            return $this->forbiddenResponse('Access denied. You can only access your own PAM data.');
        }

        return null;
    }

    /**
     * Get accessible PAM IDs for current user
     */
    protected function getAccessiblePamIds(): array
    {
        return RoleHelper::getAccessiblePamIds();
    }

    /**
     * Apply PAM filtering to query based on user role
     */
    protected function applyPamFilter($query, ?int $requestedPamId = null)
    {
        if (RoleHelper::isSuperAdmin()) {
            // SuperAdmin can access all PAMs
            if ($requestedPamId) {
                $query->where('pam_id', $requestedPamId);
            }
            return $query;
        }

        // Non-superadmin users are limited to their own PAM
        $userPamId = RoleHelper::getUserPamId();

        if (!$userPamId) {
            // If user has no PAM association, return empty result
            $query->whereRaw('1 = 0'); // Force empty result
            return $query;
        }

        if ($requestedPamId && $requestedPamId !== $userPamId) {
            // User trying to access different PAM - return empty result
            $query->whereRaw('1 = 0'); // Force empty result
            return $query;
        }

        // Apply user's PAM filter
        $query->where('pam_id', $userPamId);
        return $query;
    }

    /**
     * Validate PAM access for creation/update operations
     */
    protected function validatePamAccess(array $data): ?JsonResponse
    {
        $pamId = $data['pam_id'] ?? null;

        if (!$pamId) {
            if (!RoleHelper::isSuperAdmin()) {
                // Non-superadmin must have PAM association
                $userPamId = RoleHelper::getUserPamId();
                if (!$userPamId) {
                    return $this->forbiddenResponse('User is not associated with any PAM');
                }
                // Auto-assign user's PAM
                $data['pam_id'] = $userPamId;
            }
            return null;
        }

        // Check if user can access the specified PAM
        if (!RoleHelper::canAccessPam($pamId)) {
            return $this->forbiddenResponse('Access denied. You can only create/update data for your own PAM.');
        }

        return null;
    }

    /**
     * Get user's PAM ID or return error if not associated
     */
    protected function getUserPamOrFail(): array
    {
        $userPamId = RoleHelper::getUserPamId();

        if (!$userPamId && !RoleHelper::isSuperAdmin()) {
            return [
                'error' => $this->forbiddenResponse('User is not associated with any PAM'),
                'pam_id' => null
            ];
        }

        return [
            'error' => null,
            'pam_id' => $userPamId
        ];
    }

    /**
     * Check if user can access entity based on its PAM
     */
    protected function checkEntityPamAccess($entity): ?JsonResponse
    {
        if (!$entity) {
            return $this->notFoundResponse('Resource not found');
        }

        $pamId = $entity->pam_id ?? null;

        if ($pamId && !RoleHelper::canAccessPam($pamId)) {
            return $this->forbiddenResponse('Access denied. You can only access resources from your own PAM.');
        }

        return null;
    }

    /**
     * Get PAM-filtered pagination parameters
     */
    protected function getPamFilteredParams(array $filters = []): array
    {
        $userPamId = RoleHelper::getUserPamId();

        if (!RoleHelper::isSuperAdmin() && $userPamId) {
            $filters['pam_id'] = $userPamId;
        }

        return $filters;
    }
}
