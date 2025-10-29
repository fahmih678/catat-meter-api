<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Pam;
use App\Services\PamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PamController extends Controller
{
    use HasPamFiltering;
    protected PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    public function getPams(): JsonResponse
    {
        try {
            // Apply PAM filtering based on user role
            if (RoleHelper::isSuperAdmin()) {
                // SuperAdmin can see all PAMs
                $pams = $this->pamService->getActiveOnly(['id', 'name']);
            } else {
                // Other roles can only see their own PAM
                $userPamId = RoleHelper::getUserPamId();
                if (!$userPamId) {
                    return $this->forbiddenResponse('User is not associated with any PAM');
                }
                $pam = $this->pamService->findById($userPamId, ['id', 'name']);
                $pams = $pam ? [$pam] : [];
            }

            return $this->successResponse($pams, 'PAMs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PAMs: ' . $e->getMessage());
        }
    }
}
