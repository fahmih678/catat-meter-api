<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeterReadingRequest;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeterReadingController extends Controller
{
    use HasPamFiltering;

    private MeterReadingService $meterReadingService;

    public function __construct(MeterReadingService $meterReadingService)
    {
        $this->meterReadingService = $meterReadingService;
    }
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

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get meter data
            $meter = Meter::select('id', 'initial_installed_meter')->where('customer_id', $request->customer_id)
                ->where('is_active', true)
                ->first();

            if (!$meter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Meter tidak ditemukan atau tidak aktif'
                ], 404);
            }

            // Get previous reading
            $previousReading = MeterReading::where('meter_id', $meter->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Handle image upload
            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $photoUrl = $this->handleImageUpload($request->file('photo'), $request->customer_id);

                if (!$photoUrl) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal mengupload foto meter'
                    ], 400);
                }
            }

            // Calculate volume usage
            $previousReadingValue = $previousReading ? $previousReading->current_reading : $meter->initial_installed_meter;
            $volumeUsage = max(0, $request->current_reading - $previousReadingValue);

            // Prepare data for creating meter reading
            $meterReadingData = [
                'pam_id' => $user->pam_id,
                'meter_id' => $meter->id,
                'registered_month_id' => $request->registered_month_id,
                'previous_reading' => $previousReadingValue,
                'current_reading' => $request->current_reading,
                'volume_usage' => $volumeUsage,
                'notes' => $request->notes,
                'photo_url' => $photoUrl,
                'reading_by' => $user->id,
                'reading_at' => Carbon::parse($request->reading_at)->format('Y-m-d'),
                'status' => 'draft' // Default status
            ];

            // Validate current reading is not less than previous
            if ($meterReadingData['current_reading'] < $meterReadingData['previous_reading']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pembacaan saat ini tidak boleh lebih kecil dari pembacaan sebelumnya'
                ], 422);
            }

            // Create meter reading record directly using Eloquent
            $record = MeterReading::create($meterReadingData);

            return response()->json([
                'status' => 'success',
                'message' => 'Meter reading berhasil disimpan',
                'data' => [
                    'id' => $record->id,
                    'current_reading' => $record->current_reading,
                    'volume_usage' => $record->volume_usage,
                    'photo_url' => $record->photo_url,
                    'reading_at' => $record->reading_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating meter reading: ' . $e->getMessage(), [
                'customer_id' => $request->customer_id ?? null,
                'user_id' => $user->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data meter reading: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle image upload for meter reading
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $customerId
     * @return string|null
     */
    private function handleImageUpload($file, int $customerId): ?string
    {
        try {
            // Validate file type and size
            if (!$file->isValid()) {
                return null;
            }

            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return null;
            }

            // Maximum file size: 5MB
            if ($file->getSize() > 5 * 1024 * 1024) {
                return null;
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = 'meter_reading_' . $customerId . '_' . time() . '_' . Str::random(10) . '.' . $extension;

            // Create directory path based on date and customer
            $directory = 'meter_readings/' . date('Y/m');

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            if ($path) {
                // Return public URL
                return Storage::url($path);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading meter reading image: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'original_filename' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}
