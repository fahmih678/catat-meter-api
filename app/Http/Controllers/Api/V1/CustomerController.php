<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\RegisteredMonth;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    use HasPamFiltering;

    private CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Get unrecorded customer list
     *
     *  
     * @param Request $request
     * @return JsonResponse
     */
    public function unrecordedList(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pamId = $user->pam_id;

            // Validate query parameters
            $validated = $request->validate([
                'period' => 'required|integer|exists:registered_months,id',
                'area_id' => 'nullable|integer|exists:areas,id',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:10|max:100',
                'sort_by' => 'nullable|in:customer_name,customer_number,area_name,meter_number,created_at',
                'sort_order' => 'nullable|in:asc,desc'
            ]);

            // Verify registered month belongs to user's PAM
            $registeredMonth = RegisteredMonth::where('id', $validated['period'])
                ->where('pam_id', $pamId)
                ->first();

            if (!$registeredMonth) {
                return $this->notFoundResponse('Registered month not found');
            }

            // Set defaults
            $perPage = $validated['per_page'] ?? 25;
            $sortBy = $validated['sort_by'] ?? 'customer_name';
            $sortOrder = $validated['sort_order'] ?? 'asc';

            // Build query for customers with meters but no meter reading in the specified month
            $query = Customer::query()
                ->select([
                    'customers.id',
                    'customers.name as customer_name',
                    'customers.customer_number',
                    'customers.address',
                    'customers.phone',
                    'customers.is_active',
                    'areas.id as area_id',
                    'areas.name as area_name',
                    'meters.id as meter_id',
                    'meters.meter_number',
                    'meters.initial_installed_meter',
                    'meters.installed_at',
                    'tariff_groups.name as tariff_group_name'
                ])
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('tariff_groups', 'customers.tariff_group_id', '=', 'tariff_groups.id')
                ->join('meters', 'customers.id', '=', 'meters.customer_id')
                ->where('customers.pam_id', $pamId)
                ->where('customers.is_active', true)
                ->where('meters.is_active', true)
                ->whereNotExists(function ($subquery) use ($validated) {
                    $subquery->select('id')
                        ->from('meter_readings')
                        ->whereColumn('meter_readings.meter_id', 'meters.id')
                        ->where('meter_readings.registered_month_id', $validated['period']);
                });

            // Apply area filter if provided
            if (!empty($validated['area_id'])) {
                $query->where('customers.area_id', $validated['area_id']);
            }

            // Apply search filter with optimized LIKE queries
            if (!empty($validated['search'])) {
                $search = trim($validated['search']);
                $query->where(function ($q) use ($search) {
                    // Use more selective search patterns
                    $q->where('customers.name', 'LIKE', "%{$search}%")
                        ->orWhere('customer_number', 'LIKE', "%{$search}%");
                    // Avoid address search for performance unless specifically needed
                });
            }

            // Apply sorting
            switch ($sortBy) {
                case 'customer_name':
                    $query->orderBy('customers.name', $sortOrder);
                    break;
                case 'customer_number':
                    $query->orderBy('customers.customer_number', $sortOrder);
                    break;
                case 'area_name':
                    $query->orderBy('areas.name', $sortOrder);
                    break;
                case 'meter_number':
                    $query->orderBy('meters.meter_number', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('meter_readings.created_at', $sortOrder);
                    break;
                default:
                    $query->orderBy('customers.name', 'asc');
            }

            // Add secondary sort for consistent ordering
            if ($sortBy !== 'customer_name') {
                $query->orderBy('customers.name', 'asc');
            }

            // Execute paginated query
            $customers = $query->paginate($perPage);

            // Format response for frontend (simplified)
            $formattedData = $customers->getCollection()->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'name' => $customer->customer_name,
                    'number' => $customer->customer_number,
                    'area_name' => $customer->area_name,
                    'meter_number' => $customer->meter_number,
                ];
            });

            // Get summary statistics
            $periodDate = Carbon::parse($registeredMonth->period)->format('M Y');

            return $this->successResponse([
                'period' => $periodDate,
                'pagination' => [
                    'total' => $customers->total(),
                    'has_more_pages' => $customers->hasMorePages(),
                ],
                'items' => $formattedData,
            ], 'Pelanggan yang belum tercatat berhasil diambil');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Error fetching unrecorded customer list', [
                'error_type' => get_class($e),
                'pam_id' => $pamId ?? null,
                'filters' => $validated ?? [],
            ]);

            return $this->errorResponse('Terjadi kesalahan saat mengambil data pelanggan', 500);
        }
    }

    /**
     * Get summary statistics for unrecorded customers
     *
     * @param int $pamId
     * @param int $registeredMonthId
     * @param int|null $areaId
     * @return array
     */
    private function getUnrecordedSummary(int $pamId, int $registeredMonthId, ?int $areaId = null): array
    {
        // Total customers with active meters in PAM
        $totalWithMetersQuery = Customer::join('meters', 'customers.id', '=', 'meters.customer_id')
            ->where('customers.pam_id', $pamId)
            ->where('customers.is_active', true)
            ->where('meters.is_active', true);

        if ($areaId) {
            $totalWithMetersQuery->where('customers.area_id', $areaId);
        }

        $totalWithMeters = $totalWithMetersQuery->count();

        // Total already recorded in this month
        $recordedQuery = Customer::join('meters', 'customers.id', '=', 'meters.customer_id')
            ->join('meter_readings', 'meters.id', '=', 'meter_readings.meter_id')
            ->where('customers.pam_id', $pamId)
            ->where('customers.is_active', true)
            ->where('meters.is_active', true)
            ->where('meter_readings.registered_month_id', $registeredMonthId);

        if ($areaId) {
            $recordedQuery->where('customers.area_id', $areaId);
        }

        $recorded = $recordedQuery->count();
        $unrecorded = $totalWithMeters - $recorded;

        // Area breakdown
        $areaBreakdown = Customer::join('meters', 'customers.id', '=', 'meters.customer_id')
            ->join('areas', 'customers.area_id', '=', 'areas.id')
            ->where('customers.pam_id', $pamId)
            ->where('customers.is_active', true)
            ->where('meters.is_active', true)
            ->whereNotExists(function ($subquery) use ($registeredMonthId) {
                $subquery->select('id')
                    ->from('meter_readings')
                    ->whereColumn('meter_readings.meter_id', 'meters.id')
                    ->where('meter_readings.registered_month_id', $registeredMonthId);
            })
            ->when($areaId, function ($query) use ($areaId) {
                return $query->where('customers.area_id', $areaId);
            })
            ->selectRaw('areas.id, areas.name, COUNT(*) as unrecorded_count')
            ->groupBy('areas.id', 'areas.name')
            ->get();

        return [
            'total_with_meters' => $totalWithMeters,
            'recorded' => $recorded,
            'unrecorded' => $unrecorded,
            'completion_percentage' => $totalWithMeters > 0 ? round(($recorded / $totalWithMeters) * 100, 1) : 0,
            'area_breakdown' => $areaBreakdown->map(function ($area) {
                return [
                    'area_id' => $area->id,
                    'area_name' => $area->name,
                    'unrecorded_count' => (int) $area->unrecorded_count,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get bills by user ID
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getMyBills(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            // Validate query parameters
            $validated = $request->validate([
                'customer_id' => 'nullable|integer|exists:customers,id',
                'status' => 'nullable|in:pending,paid',
                'per_page' => 'nullable|integer|min:5|max:50',
            ]);

            $perPage = $validated['per_page'] ?? 10;

            // Build query for bills belonging to the user through customers
            $query = Bill::query()
                ->select([
                    'bills.id',
                    'bills.bill_number',
                    'bills.volume_usage',
                    'bills.total_bill',
                    'bills.status',
                    'bills.due_date',
                    'bills.payment_method',
                    'bills.paid_at',
                    'bills.issued_at',
                    'customers.name as customer_name',
                    'customers.customer_number',
                    'areas.name as area_name',
                    'meters.meter_number',
                    'meter_readings.previous_reading',
                    'meter_readings.current_reading',
                    'meter_readings.photo_url',
                    'registered_months.period as bill_period',
                ])
                ->join('customers', 'bills.customer_id', '=', 'customers.id')
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('meter_readings', 'bills.meter_reading_id', '=', 'meter_readings.id')
                ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
                ->join('registered_months', 'meter_readings.registered_month_id', '=', 'registered_months.id')
                ->where('customers.user_id', $user->id)
                ->where('customers.is_active', true)
                ->orderByDesc('bills.created_at');

            // Apply filters
            if (!empty($validated['customer_id'])) {
                $query->where('customers.id', $validated['customer_id']);
            }

            if (!empty($validated['status'])) {
                $query->where('bills.status', $validated['status']);
            }

            // Execute paginated query
            $bills = $query->paginate($perPage);

            // Format response data
            $formattedData = $bills->getCollection()->map(function ($bill) {
                return [
                    'bill_id' => $bill->id,
                    'customer_name' => $bill->customer_name,
                    'customer_number' => $bill->customer_number,
                    'meter_number' => $bill->meter_number,
                    'area' => $bill->area_name,
                    'periode' => Carbon::parse($bill->bill_period)->format('M Y'),
                    'due_date' => Carbon::parse($bill->due_date)->format('d M Y'),
                    'status' => $bill->status,
                    'previous_reading' => $bill->previous_reading,
                    'current_reading' => $bill->current_reading,
                    'volume_usage' => $bill->volume_usage,
                    'total_bill' => (float) $bill->total_bill,
                    'bill_number' => $bill->bill_number,
                    'payment_method' => $bill->payment_method ?: '-',
                    'paid_at' => $bill->paid_at ? Carbon::parse($bill->paid_at)->format('d M Y') : null,
                    'issued_at' => $bill->issued_at ? Carbon::parse($bill->issued_at)->format('d M Y') : null,
                    'photo' => $bill->photo_url,
                ];
            });

            $userCustomers = Customer::where('user_id', $user->id)
                ->where('is_active', true)
                ->get(['id', 'name', 'customer_number']);

            return $this->successResponse([
                'pagination' => [
                    'total' => $bills->total(),
                    'has_more_pages' => $bills->hasMorePages(),
                ],
                'customers' => $userCustomers,
                'items' => $formattedData,
            ], 'Tagihan berhasil diambil');
        } catch (\Exception $e) {
            Log::error('Error fetching bills by user', [
                'error_type' => get_class($e),
                'user_id' => $user->id ?? null,
                'filters' => $validated ?? [],
            ]);

            return $this->errorResponse('Terjadi kesalahan saat mengambil data tagihan', 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $pamId = $request['user_pam_id'] ?? $request['pam_id'];

            // Validate query parameters
            $validated = $request->validate([
                'pam_id' => 'nullable|integer|exists:pams,id',
                'search' => 'nullable|string|max:150',
                'status' => 'nullable|in:active,inactive',
                'area_id' => 'nullable|integer|exists:areas,id',
                'per_page' => 'nullable|integer|min:10|max:100',
                'sort_by' => 'nullable|in:customer_id,status,created_at',
                'sort_order' => 'nullable|in:asc,desc'
            ]);

            // Set defaults
            $perPage = $validated['per_page'] ?? 25;
            $sortBy = $validated['sort_by'] ?? 'customer_id';
            $sortOrder = $validated['sort_order'] ?? 'asc';

            $query = Customer::query()
                ->select([
                    'customers.id',
                    'customers.name',
                    'customers.customer_number',
                    'customers.address',
                    'customers.is_active',
                    'areas.code as area_code',
                    'meters.meter_number',
                    'tariff_groups.name as tariff_group_name',
                ])
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('tariff_groups', 'customers.tariff_group_id', '=', 'tariff_groups.id')
                ->join('meters', 'customers.id', '=', 'meters.customer_id')
                ->where('customers.pam_id', $pamId);

            if (!empty($validated['status'])) {
                $query->where('customers.is_active', $validated['status'] == 'active' ? true : false);
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
                    $query->orderBy('customers.is_active', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('customers.created_at', $sortOrder);
                default:
                    $query->orderBy('customers.id', $sortOrder);
            }

            $customers = $query->paginate($perPage);

            $formattedData = $customers->getCollection()->map(function ($customer) {
                return [
                    "id" => $customer->id,
                    "customer_name" => $customer->name,
                    "customer_number" => $customer->customer_number,
                    "address" => $customer->address,
                    "status" => $customer->is_active,
                    "meter_number" => $customer->meter_number,
                    "area_code" => $customer->area_code,
                    "tariff_group" => $customer->tariff_group_name
                ];
            });

            return $this->successResponse([
                'pagination' => [
                    'total' => $customers->total(),
                    'has_more_pages' => $customers->hasMorePages()
                ],
                'customers' => $formattedData
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Error Get Customers');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pam_id' => 'required|integer|exists:pams,id',
                'area_id' => 'required|integer|exists:areas,id',
                'tariff_group_id' => 'required|integer|exists:tariff_groups,id',
                'user_id' => 'nullable|integer|exists:users,id',
                'customer_number' => 'required|string',
                'name' => 'required|string',
                'address' => 'nullable|string',
                'phone' => 'required|string',
                'status' => 'required|in:active,inactive'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $this->successResponse('success');
    }
}
