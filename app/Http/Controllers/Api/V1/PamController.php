<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Pam;
use App\Services\PamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PamController extends Controller
{
    use HasPamFiltering;
    protected PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    /**
     * Get PAM list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPams(): JsonResponse
    {
        try {
            // Apply PAM filtering based on user role with caching
            if (RoleHelper::isSuperAdmin()) {
                // SuperAdmin can see all PAMs (cached for 1 hour)
                $pams = Cache::remember('active_pams_all', 3600, function () {
                    return $this->pamService->getActiveOnly(['id', 'name']);
                });
            } else {
                // Other roles can only see their own PAM (cached for 30 minutes)
                $userPamId = RoleHelper::getUserPamId();
                if (!$userPamId) {
                    return $this->forbiddenResponse('User is not associated with any PAM');
                }
                $cacheKey = "pam_user_{$userPamId}";
                $pam = Cache::remember($cacheKey, 1800, function () use ($userPamId) {
                    return $this->pamService->findById($userPamId, ['id', 'name']);
                });
                $pams = $pam ? [$pam] : [];
            }

            return $this->successResponse($pams, 'PAMs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil data PAM', 500);
        }
    }
}
