<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeterReadingRequest;
use App\Http\Traits\HasPamFiltering;
use App\Services\MeterReadingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    use HasPamFiltering;
    private MeterReadingService $meterReadingService;

    public function __construct(MeterReadingService $meterReadingService)
    {
        $this->meterReadingService = $meterReadingService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'pam_id' => $request->get('pam_id'),
                'meter_id' => $request->get('meter_id'),
                'period' => $request->get('period'),
                'reading_date_from' => $request->get('reading_date_from'),
                'reading_date_to' => $request->get('reading_date_to'),
                'per_page' => $request->get('per_page', 15)
            ];

            // Apply PAM filtering using trait
            $filters = $this->getPamFilteredParams($filters);

            $records = $this->meterReadingService->getAllRecords($filters);
            return $this->successResponse($records, 'Meter records retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meter records: ' . $e->getMessage());
        }
    }

    public function store(MeterReadingRequest $request): JsonResponse
    {
        try {
            $record = $this->meterReadingService->createRecord($request->validated());
            return $this->successResponse($record, 'Meter record created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create meter record: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $record = $this->meterReadingService->getRecordById($id);

            if (!$record) {
                return $this->notFoundResponse('Meter record not found');
            }

            // Check PAM access permission using trait
            $accessError = $this->checkEntityPamAccess($record);
            if ($accessError) {
                return $accessError;
            }

            return $this->successResponse($record, 'Meter record retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meter record: ' . $e->getMessage());
        }
    }

    public function update(MeterReadingRequest $request, int $id): JsonResponse
    {
        try {
            $record = $this->meterReadingService->updateRecord($id, $request->validated());

            if (!$record) {
                return $this->notFoundResponse('Meter record not found');
            }

            return $this->successResponse($record, 'Meter record updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update meter record: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->meterReadingService->deleteRecord($id);

            if (!$result) {
                return $this->notFoundResponse('Meter record not found');
            }

            return $this->successResponse(null, 'Meter record deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete meter record: ' . $e->getMessage());
        }
    }

    public function byMeter(int $meterId, Request $request): JsonResponse
    {
        try {
            $filters = [
                'period' => $request->get('period'),
                'reading_date_from' => $request->get('reading_date_from'),
                'reading_date_to' => $request->get('reading_date_to'),
                'per_page' => $request->get('per_page', 15)
            ];

            $records = $this->meterReadingService->getRecordsByMeter($meterId, $filters);
            return $this->successResponse($records, 'Meter records retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve meter records: ' . $e->getMessage());
        }
    }

    public function byPeriod(string $period, Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');

            if (empty($pamId)) {
                return $this->errorResponse('PAM ID is required');
            }

            $filters = [
                'area_id' => $request->get('area_id'),
                'customer_id' => $request->get('customer_id'),
                'per_page' => $request->get('per_page', 15)
            ];

            $records = $this->meterReadingService->getRecordsByPeriod($pamId, $period, $filters);
            return $this->successResponse($records, 'Period records retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve period records: ' . $e->getMessage());
        }
    }

    public function bulkCreate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'records' => 'required|array|min:1',
                'records.*.meter_id' => 'required|exists:meters,id',
                'records.*.period' => 'required|string',
                'records.*.reading_date' => 'required|date',
                'records.*.current_reading' => 'required|numeric|min:0',
                'records.*.notes' => 'nullable|string'
            ]);

            $results = $this->meterReadingService->bulkCreateRecords($request->records);
            return $this->successResponse($results, 'Bulk meter records created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create bulk records: ' . $e->getMessage());
        }
    }

    public function usage(int $meterId, Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly');
            $months = (int) $request->get('months', 12);

            $usage = $this->meterReadingService->getUsageData($meterId, $period, $months);

            if (!$usage) {
                return $this->notFoundResponse('Meter not found');
            }

            return $this->successResponse($usage, 'Usage data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve usage data: ' . $e->getMessage());
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');

            if (empty($pamId)) {
                return $this->errorResponse('PAM ID is required');
            }

            $period = $request->get('period');
            $stats = $this->meterReadingService->getReadingStatistics($pamId, $period);

            return $this->successResponse($stats, 'Reading statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }

    public function missingReadings(Request $request): JsonResponse
    {
        try {
            $pamId = $request->get('pam_id');
            $period = $request->get('period');

            if (empty($pamId) || empty($period)) {
                return $this->errorResponse('PAM ID and period are required');
            }

            $missing = $this->meterReadingService->getMissingReadings($pamId, $period);
            return $this->successResponse($missing, 'Missing readings retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve missing readings: ' . $e->getMessage());
        }
    }
}
