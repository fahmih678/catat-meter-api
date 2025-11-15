<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
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
     * Get meter reading list with filters and pagination
     *
     * PERFORMANCE NOTES:
     * - Recommended indexes: (pam_id, registered_month_id), (meter_id), (customer_id, area_id)
     * - Uses selective column loading to reduce memory usage
     * - Pagination prevents large dataset loading
     * - N+1 queries prevented with proper joins
     */
    public function meterReadingList(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pamId = $user->pam_id;

            // Validate query parameters
            $validated = $request->validate([
                'registered_month_id' => 'nullable|integer|exists:registered_months,id',
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:draft,pending,paid',
                'area_id' => 'nullable|integer|exists:areas,id',
                'per_page' => 'nullable|integer|min:10|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|in:customer_id,status',
                'sort_order' => 'nullable|in:asc,desc'
            ]);

            // Set defaults
            $perPage = $validated['per_page'] ?? 20;
            $sortBy = $validated['sort_by'] ?? 'customer_id';
            $sortOrder = $validated['sort_order'] ?? 'desc';

            // Build optimized query using indexes
            $query = MeterReading::query()
                ->select([
                    // Meter reading fields
                    'meter_readings.id',
                    'meter_readings.previous_reading',
                    'meter_readings.current_reading',
                    'meter_readings.volume_usage',
                    'meter_readings.photo_url',
                    'meter_readings.status',
                    'meter_readings.notes',
                    'meter_readings.reading_at',
                    // Customer fields
                    'customers.id as customer_id',
                    'customers.name as customer_name',
                    'customers.customer_number',
                    // Related fields
                    'meters.meter_number',
                    'areas.name as area_name',
                    'registered_months.period',
                    'users.name as reading_by_name',
                    // Bill fields
                    'bills.id as bill_id',
                    'bills.total_bill as bill_total',
                    'bills.due_date as bill_due_date'
                ])
                ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
                ->join('customers', 'meters.customer_id', '=', 'customers.id')
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('registered_months', 'meter_readings.registered_month_id', '=', 'registered_months.id')
                ->leftJoin('users', function ($join) use ($pamId) {
                    $join->on('meter_readings.reading_by', '=', 'users.id')
                        ->where('users.pam_id', '=', $pamId);
                })
                ->leftJoin('bills', function ($join) {
                    $join->on('meter_readings.id', '=', 'bills.meter_reading_id');
                })
                ->where('meter_readings.pam_id', $pamId);

            // Apply filters with index optimization
            if (!empty($validated['registered_month_id'])) {
                $query->where('meter_readings.registered_month_id', $validated['registered_month_id']);
            }

            if (!empty($validated['status'])) {
                $query->where('meter_readings.status', $validated['status']);
            }

            if (!empty($validated['area_id'])) {
                $query->where('customers.area_id', $validated['area_id']);
            }
            // Search functionality - optimized with indexes
            if (!empty($validated['search'])) {
                $search = trim($validated['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('customers.name', 'LIKE', "%{$search}%")
                        ->orWhere('customers.customer_number', 'LIKE', "%{$search}%")
                        ->orWhere('meters.meter_number', 'LIKE', "%{$search}%")
                        ->orWhere('customers.address', 'LIKE', "%{$search}%");
                });
            }
            // Apply sorting
            switch ($sortBy) {
                case 'customer_id':
                    $query->orderBy('customers.id', $sortOrder);
                    break;
                case 'status':
                    $query->orderBy('meter_readings.status', $sortOrder);
                    break;
                default:
                    $query->orderBy('meter_readings.reading_at', $sortOrder);
            }

            // Execute paginated query
            $meterReadings = $query->paginate($perPage);

            // Format response for mobile UI
            $formattedData = $meterReadings->getCollection()->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'period' => $reading->period,
                    'customer' => [
                        'id' => $reading->customer_id,
                        'name' => $reading->customer_name,
                        'number' => $reading->customer_number,
                        'area_name' => $reading->area_name,
                    ],
                    'meter_number' => $reading->meter_number,
                    'current_reading' => (float) $reading->current_reading,
                    'volume_usage' => (float) $reading->volume_usage,
                    'bill' => [
                        'id' => $reading->bill_id,
                        'total_bill' => $reading->bill_total,
                        'due_date' => $reading->bill_due_date,
                    ],
                    'notes' => $reading->notes,
                    'status' => $reading->status,
                    'reading_by' => $reading->reading_by_name,
                    'reading_at' => $reading->reading_at,
                ];
            });

            return $this->successResponse([
                'items' => $formattedData,
                'pagination' => [
                    'total' => $meterReadings->total(),
                    'has_more_pages' => $meterReadings->hasMorePages(),
                ]
            ], 'Data pencatatan meter berhasil diambil');
        } catch (\Exception $e) {
            Log::error('Error fetching meter reading list', [
                'pam_id' => $user->pam_id ?? null,
                'filters' => $request->all(),
                            ]);

            return $this->errorResponse('Terjadi kesalahan saat mengambil data pencatatan meter', 500, 'Internal server error');
        }
    }

    /**
     * Get customer and meter data for meter reading input
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function getMeterReadingForm(Request $request, int $customerId): JsonResponse
    {
        try {
            $user = $request->user();

            // Single optimized query with all required relationships
            $customer = Customer::with([
                'area:id,name',
                'pam:id,name',
                'meter' => function ($query) {
                    $query->select('id', 'customer_id', 'meter_number', 'initial_installed_meter')
                        ->where('is_active', true);
                },
                'meter.meterReadings' => function ($query) {
                    $query->select('id', 'meter_id', 'current_reading', 'photo_url', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                }
            ])
                ->select('id', 'name', 'customer_number', 'pam_id', 'area_id', 'is_active')
                ->where('id', $customerId)
                ->where('pam_id', $user->pam_id)
                ->where('is_active', true)
                ->first();

            // Validate customer exists
            if (!$customer) {
                return $this->errorResponse('Customer tidak ditemukan atau tidak sesuai dengan PAM Anda', 404);
            }

            // Validate meter exists
            if (!$customer->meter) {
                return $this->errorResponse('Customer tidak memiliki meter aktif', 404);
            }

            // Get last reading (from eager loaded relationship)
            $lastReading = $customer->meter->meterReadings->first();

            // Determine last reading value
            $lastReadingValue = $lastReading
                ? (float) $lastReading->current_reading
                : (float) $customer->meter->initial_installed_meter;

            // Build response data
            $responseData = [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'number' => $customer->customer_number,
                'area_name' => $customer->area->name,
                'pam_name' => $customer->pam->name,
                'meter' => [
                    'id' => $customer->meter->id,
                    'number' => $customer->meter->meter_number,
                    'last_reading' => $lastReadingValue,
                ],
                'photo_url' => $lastReading?->photo_url ?? null,
            ];

            return $this->successResponse($responseData, 'Data berhasil diambil');
        } catch (\Exception $e) {
            Log::error('Error fetching meter input data', [
                'customer_id' => $customerId,
                'pam_id' => $user->pam_id ?? null,
                            ]);

            return $this->errorResponse('Terjadi kesalahan saat mengambil data meter input');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer|exists:customers,id',
                'registered_month_id' => 'required|integer|exists:registered_months,id',
                'current_reading' => 'required|decimal:2|min:0',
                'notes' => 'nullable|string|max:1000',
                'reading_by' => 'nullable|integer|exists:users,id',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $user = $request->user();

            // Get customer data with PAM security and soft delete handling
            $customerData = Customer::select([
                'customers.id',
                'customers.pam_id',
                'meters.id as meter_id',
                'meters.initial_installed_meter',
                'meters.is_active as meter_active',
                // Subquery for existing reading (soft delete safe)
                DB::raw('(SELECT 1 FROM meter_readings
                              WHERE meter_id = meters.id
                              AND registered_month_id = ' . $request->registered_month_id . '
                              AND deleted_at IS NULL
                              LIMIT 1) as reading_exists'),
                // Subquery for previous reading (soft delete safe)
                DB::raw('(SELECT current_reading FROM meter_readings
                              WHERE meter_id = meters.id
                              AND deleted_at IS NULL
                              ORDER BY created_at DESC
                              LIMIT 1) as previous_reading')
            ])
                ->join('meters', 'customers.id', '=', 'meters.customer_id')
                ->where('customers.id', $request->customer_id)
                ->where('customers.pam_id', $user->pam_id) // SECURITY: PAM validation
                ->where('customers.is_active', true)
                ->where('meters.is_active', true)
                ->first();

            if (!$customerData) {
                return $this->notFoundResponse('Customer atau meter tidak ditemukan atau tidak sesuai dengan PAM Anda');
            }

            if (!$customerData->meter_active) {
                return $this->notFoundResponse('Meter tidak aktif untuk customer ini');
            }

            if ($customerData->reading_exists) {
                return $this->errorResponse('Customer sudah dilakukan pencatatan untuk bulan ini', 409);
            }

            // Determine previous reading value
            $previousReadingValue = $customerData->previous_reading ?? $customerData->initial_installed_meter;

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
            $volumeUsage = max(0, $request->current_reading - $previousReadingValue);

            // Prepare data for creating meter reading
            $meterReadingData = [
                'pam_id' => $user->pam_id,
                'meter_id' => $customerData->meter_id,
                'registered_month_id' => $request->registered_month_id,
                'previous_reading' => $previousReadingValue,
                'current_reading' => $request->current_reading,
                'volume_usage' => $volumeUsage,
                'notes' => $request->notes,
                'photo_url' => $photoUrl,
                'reading_by' => $user->id,
                'reading_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'draft' // Default status
            ];

            // Validate current reading is not less than previous
            if ($meterReadingData['current_reading'] < $meterReadingData['previous_reading']) {
                return $this->errorResponse('Pembacaan saat ini tidak boleh lebih kecil dari pembacaan sebelumnya', 422);
            }

            // Use transaction to prevent race conditions
            DB::beginTransaction();
            try {
                // Double-check for race condition before creating
                $finalCheck = MeterReading::where('meter_id', $customerData->meter_id)
                    ->where('registered_month_id', $request->registered_month_id)
                    ->lockForUpdate()
                    ->exists();

                if ($finalCheck) {
                    DB::rollBack();
                    return $this->errorResponse('Customer sudah dilakukan pencatatan untuk bulan ini', 409);
                }

                // Create meter reading record within transaction
                $record = MeterReading::create($meterReadingData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->createdResponse([
                'id' => $record->id,
                'current_reading' => $record->current_reading,
                'volume_usage' => $record->volume_usage,
                'photo_url' => $record->photo_url, // This will use the accessor to get full URL
                'reading_at' => $record->reading_at,
            ], 'Meter reading berhasil disimpan');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                // Duplicate entry
                return response()->json([
                    'status' => 'error',
                    'message' => 'Meter reading sudah terdaftar untuk bulan ini.'
                ], 409);
            }

            // Log error ke server (tanpa sensitive information)
            Log::error('Error creating meter reading', [
                'customer_id' => $request->customer_id ?? null,
                'user_id' => $user->id ?? null,
            ]);

            // Pesan yang dikirim ke client hanya yang aman & umum
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
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
            Log::error('Error uploading meter reading image', [
                'customer_id' => $customerId,
                'original_filename' => $file->getClientOriginalName(),
                            ]);

            return null;
        }
    }

    /**
     * Submit meter reading from draft to pending status and create billing
     *
     * @param Request $request
     * @param int $meterReadingId
     * @return JsonResponse
     */
    public function submitToPending(Request $request, int $meterReadingId): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate request data (optional fields only)
            $validated = $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            // Prepare request data for service
            $requestData = [
                'user_id' => $user->id,
                'pam_id' => $user->pam_id,
                'notes' => $validated['notes'] ?? null,
            ];
            // Submit meter reading (draft -> pending) and create billing
            $result = $this->meterReadingService->submitMeterReadingToPending($meterReadingId, $requestData);

            if (!$result) {
                return $this->notFoundResponse('Meter reading tidak ditemukan');
            }

            return $this->successResponse($result['data'], $result['message']);
        } catch (\Exception $e) {
            Log::error('Error submitting meter reading to pending', [
                'meter_reading_id' => $meterReadingId,
                'user_id' => $user->id ?? null,
                            ]);

            return $this->errorResponse('Terjadi kesalahan saat mengubah status meter reading', 500);
        }
    }

    public function destroy(Request $request, int $meterReadingId): JsonResponse
    {
        try {
            // Check if user can access billing features
            if (!RoleHelper::isAdminPam() && !RoleHelper::canAccessPam($request->user()->pam_id)) {
                return $this->forbiddenResponse('Akses ditolak. Anda tidak memiliki izin untuk mengakses fitur meter reading.');
            }

            $user = $request->user();

            // Delete meter reading using service
            $deleted = $this->meterReadingService->deleteRecord($meterReadingId);

            if (!$deleted) {
                return $this->notFoundResponse('Meter reading tidak ditemukan atau tidak berstatus draft');
            }

            return $this->deletedResponse('Meter reading berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting meter reading', [
                'meter_reading_id' => $meterReadingId,
                'user_id' => $user->id ?? null,
                            ]);

            return $this->errorResponse('Terjadi kesalahan saat menghapus meter reading', 500);
        }
    }
}
