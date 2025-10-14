<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\MeterReading;
use Carbon\Carbon;

class MeterReadingController extends Controller
{
    /**
     * Get customer and meter data for meter reading input
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function getMeterInputData(Request $request, int $customerId): JsonResponse
    {
        try {
            $user = $request->user();
            $pamId = $user->pam_id;

            // Get customer with meter and area data
            $customer = Customer::with(['area', 'meter' => function ($query) {
                $query->where('is_active', true);
            }])
                ->where('id', $customerId)
                ->where('pam_id', $pamId)
                ->where('is_active', true)
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer tidak ditemukan atau tidak sesuai dengan PAM Anda'
                ], 404);
            }

            if (!$customer->meter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer tidak memiliki meter aktif'
                ], 404);
            }

            // Get last meter reading
            $lastReading = MeterReading::where('meter_id', $customer->meter->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Format response
            $responseData = [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'number' => $customer->customer_number,
                'area_name' => $customer->area->name,
                'meter' => [
                    'id' => $customer->meter->id,
                    'number' => $customer->meter->meter_number,
                    'last_reading' => $lastReading ?
                        (float) $lastReading->current_reading :
                        (float) $customer->meter->initial_installed_meter,
                ],
                'pam_name' => $customer->pam->name,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching meter input data: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'pam_id' => $user->pam_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data meter input'
            ], 500);
        }
    }
}
