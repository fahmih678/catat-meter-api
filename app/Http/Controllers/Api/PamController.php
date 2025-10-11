<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PamRequest;
use App\Http\Traits\HasPamFiltering;
use App\Services\PamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PamController extends Controller
{
    use HasPamFiltering;
    protected PamService $pamService;

    public function __construct(PamService $pamService)
    {
        $this->pamService = $pamService;
    }

    public function index(): JsonResponse
    {
        try {
            // Apply PAM filtering based on user role
            if (RoleHelper::isSuperAdmin()) {
                // SuperAdmin can see all PAMs
                $pams = $this->pamService->getAll();
            } else {
                // Other roles can only see their own PAM
                $userPamId = RoleHelper::getUserPamId();
                if (!$userPamId) {
                    return $this->forbiddenResponse('User is not associated with any PAM');
                }
                $pam = $this->pamService->findById($userPamId);
                $pams = $pam ? [$pam] : [];
            }

            return $this->successResponse($pams, 'PAMs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PAMs: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            // Check PAM access permission using trait
            $accessError = $this->checkPamAccess($id);
            if ($accessError) {
                return $accessError;
            }

            $pam = $this->pamService->findById($id);

            if (!$pam) {
                return $this->notFoundResponse('PAM not found');
            }

            return $this->successResponse($pam, 'PAM retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PAM: ' . $e->getMessage());
        }
    }

    public function store(PamRequest $request): JsonResponse
    {
        try {
            $pam = $this->pamService->create($request->validated());
            return $this->createdResponse($pam, 'PAM created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create PAM: ' . $e->getMessage());
        }
    }

    public function update(PamRequest $request, int $id): JsonResponse
    {
        try {
            // Check PAM access permission using trait
            $accessError = $this->checkPamAccess($id);
            if ($accessError) {
                return $accessError;
            }

            $pam = $this->pamService->update($id, $request->validated());
            return $this->updatedResponse($pam, 'PAM updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update PAM: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->pamService->delete($id);
            return $this->deletedResponse('PAM deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete PAM: ' . $e->getMessage());
        }
    }

    public function active(): JsonResponse
    {
        try {
            $pams = $this->pamService->getActiveOnly();
            return $this->successResponse($pams, 'Active PAMs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve active PAMs: ' . $e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $name = $request->get('name', '');
            $pams = $this->pamService->searchByName($name);
            return $this->successResponse($pams, 'PAMs search completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search PAMs: ' . $e->getMessage());
        }
    }

    public function statistics(int $id): JsonResponse
    {
        try {
            // Check PAM access permission using trait
            $accessError = $this->checkPamAccess($id);
            if ($accessError) {
                return $accessError;
            }

            $statistics = $this->pamService->getStatistics($id);
            return $this->successResponse($statistics, 'PAM statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve PAM statistics: ' . $e->getMessage());
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $pam = $this->pamService->activatePam($id);
            return $this->updatedResponse($pam, 'PAM activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate PAM: ' . $e->getMessage());
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $pam = $this->pamService->deactivatePam($id);
            return $this->updatedResponse($pam, 'PAM deactivated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate PAM: ' . $e->getMessage());
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $result = $this->pamService->restore($id);

            if (!$result) {
                return $this->notFoundResponse('PAM not found or already active');
            }

            return $this->successResponse(null, 'PAM restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore PAM: ' . $e->getMessage());
        }
    }
}
