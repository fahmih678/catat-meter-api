<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeterRequest;
use App\Http\Traits\HasPamFiltering;
use App\Services\MeterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    use HasPamFiltering;
    private MeterService $meterService;

    public function __construct(MeterService $meterService)
    {
        $this->meterService = $meterService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'pam_id' => $request->get('pam_id'),
                'customer_id' => $request->get('customer_id'),
                'area_id' => $request->get('area_id'),
                'status' => $request->get('status'),
                'per_page' => $request->get('per_page', 15)
            ];

            // Apply PAM filtering using trait
            $filters = $this->getPamFilteredParams($filters);

            $meters = $this->meterService->getAllMeters($filters);
            return $this->successResponse($meters, 'Meters retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meters: ' . $e->getMessage());
        }
    }

    public function store(MeterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Validate PAM access for creation using trait
            $validationError = $this->validatePamAccess($data);
            if ($validationError) {
                return $validationError;
            }

            $meter = $this->meterService->createMeter($data);
            return $this->successResponse($meter, 'Meter created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create meter: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $meter = $this->meterService->getMeterById($id);

            if (!$meter) {
                return $this->notFoundResponse('Meter not found');
            }

            // Check PAM access permission using trait
            $accessError = $this->checkEntityPamAccess($meter);
            if ($accessError) {
                return $accessError;
            }

            return $this->successResponse($meter, 'Meter retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meter: ' . $e->getMessage());
        }
    }

    public function update(MeterRequest $request, int $id): JsonResponse
    {
        try {
            $meter = $this->meterService->getMeterById($id);

            if (!$meter) {
                return $this->notFoundResponse('Meter not found');
            }

            // Check PAM access permission using trait
            $accessError = $this->checkEntityPamAccess($meter);
            if ($accessError) {
                return $accessError;
            }

            $updatedMeter = $this->meterService->updateMeter($id, $request->validated());
            return $this->successResponse($updatedMeter, 'Meter updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update meter: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $meter = $this->meterService->getMeterById($id);

            if (!$meter) {
                return $this->notFoundResponse('Meter not found');
            }

            // Check PAM access permission using trait
            $accessError = $this->checkEntityPamAccess($meter);
            if ($accessError) {
                return $accessError;
            }

            $result = $this->meterService->deleteMeter($id);
            return $this->successResponse(null, 'Meter deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete meter: ' . $e->getMessage());
        }
    }

    public function activate(int $id): JsonResponse
    {
        try {
            $meter = $this->meterService->activateMeter($id);

            if (!$meter) {
                return $this->notFoundResponse('Meter not found');
            }

            return $this->successResponse($meter, 'Meter activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate meter: ' . $e->getMessage());
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $meter = $this->meterService->deactivateMeter($id);

            if (!$meter) {
                return $this->notFoundResponse('Meter not found');
            }

            return $this->successResponse($meter, 'Meter deactivated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to deactivate meter: ' . $e->getMessage());
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $result = $this->meterService->restore($id);

            if (!$result) {
                return $this->notFoundResponse('Meter not found or already active');
            }

            return $this->successResponse(null, 'Meter restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore meter: ' . $e->getMessage());
        }
    }

    public function byCustomer(int $customerId): JsonResponse
    {
        try {
            $meters = $this->meterService->getMetersByCustomer($customerId);
            return $this->successResponse($meters, 'Customer meters retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer meters: ' . $e->getMessage());
        }
    }

    public function byArea(int $areaId, Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'per_page' => $request->get('per_page', 15)
            ];

            $meters = $this->meterService->getMetersByArea($areaId, $filters);
            return $this->successResponse($meters, 'Area meters retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve area meters: ' . $e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');
            $query = $request->get('q', '');

            if (empty($pamId)) {
                return $this->errorResponse('PAM ID is required for search');
            }

            $filters = [
                'query' => $query,
                'customer_id' => $request->get('customer_id'),
                'area_id' => $request->get('area_id'),
                'status' => $request->get('status'),
                'per_page' => $request->get('per_page', 15)
            ];

            $meters = $this->meterService->searchMeters($pamId, $filters);
            return $this->successResponse($meters, 'Meters search completed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search meters: ' . $e->getMessage());
        }
    }

    public function statistics(int $id): JsonResponse
    {
        try {
            $stats = $this->meterService->getMeterStatistics($id);

            if (!$stats) {
                return $this->notFoundResponse('Meter not found');
            }

            return $this->successResponse($stats, 'Meter statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meter statistics: ' . $e->getMessage());
        }
    }
}
