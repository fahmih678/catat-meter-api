<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Area;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Meter;
use App\Models\RegisteredMonth;
use App\Models\TariffGroup;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
                    $q->where('customers.name', 'LIKE', "%{$search}%")
                        ->orWhere('customer_number', 'LIKE', "%{$search}%");
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
            $request->validate([
                'pam_id'       => 'required_without:user_pam_id|integer',
                'user_pam_id'  => 'required_without:pam_id|integer',
            ]);
            $pamId = $request->pam_id ?? $request->user_pam_id;
            if (!RoleHelper::canAccessPam($pamId)) {
                return $this->forbiddenResponse("You can't access this PAM");
            }

            $validated = $request->validate([
                'search' => 'nullable|string|max:150',
                'status' => 'nullable|in:active,inactive',
                'area_id' => 'nullable|integer|exists:areas,id',
                'per_page' => 'nullable|integer|min:10|max:100',
                'sort_by' => 'nullable|in:customer_id,created_at',
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
                    'areas.name as area_name',
                    'meters.meter_number',
                    'tariff_groups.name as tariff_group_name',
                ])
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('tariff_groups', 'customers.tariff_group_id', '=', 'tariff_groups.id')
                ->join('meters', 'customers.id', '=', 'meters.customer_id')
                ->where('meters.is_active', true)
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
                        ->orWhere('customers.customer_number', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting
            switch ($sortBy) {
                case 'customer_id':
                    $query->orderBy('customers.id', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('customers.created_at', $sortOrder);
                    break;
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
                    "area_name" => $customer->area_name,
                    "tariff_group" => $customer->tariff_group_name
                ];
            });
            $areas = Area::select('id', 'name')->where('pam_id', $pamId)->get();
            return $this->successResponse([
                'pagination' => [
                    'total' => $customers->total(),
                    'has_more_pages' => $customers->hasMorePages()
                ],
                'areas' => $areas,
                'customers' => $formattedData
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving customer data');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pam_id'       => 'required_without:user_pam_id|integer',
                'user_pam_id'  => 'required_without:pam_id|integer',
            ]);

            $pamId = $request->pam_id ?? $request->user_pam_id;
            if (!RoleHelper::canAccessPam($pamId)) {
                return $this->forbiddenResponse("You can't access this PAM");
            }

            $request['pam_id'] = $pamId;
            $validatedCustomer = $request->validate([
                'pam_id' => 'required|integer|exists:pams,id',
                'area_id' => 'required|integer|exists:areas,id',
                'tariff_group_id' => 'required|integer|exists:tariff_groups,id',
                'user_id' => 'nullable|integer|exists:users,id',
                'customer_number' => 'required|string',
                'name' => 'required|string',
                'address' => 'nullable|string',
                'phone' => 'required|string',
            ]);

            $validatedMeter = $request->validate([
                "meter_number" => "required|integer",
                "initial_installed_meter" => "required|decimal:0,2",
                "notes" => "nullable|string"
            ]);

            $customer = Customer::create($validatedCustomer);
            Meter::create([
                'pam_id' => $pamId,
                'customer_id' => $customer->id,
                'meter_number' => $validatedMeter['meter_number'],
                'initial_installed_meter' => $validatedMeter['initial_installed_meter'],
                'notes' => $validatedMeter['notes'] ?? null,
            ]);

            return $this->successResponse(null, 'Customer created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (QueryException $e) {
            return $this->errorResponse('Duplicate entry');
        } catch (\Exception $e) {
            return $this->errorResponse('Error create customer');
        }
    }

    public function show(Request $request, $customerId): JsonResponse
    {
        try {
            $request['customer_id'] = $customerId;
            $request->validate([
                'customer_id' => 'required|integer|exists:customers,id',
                'pam_id'       => 'required_without:user_pam_id|integer',
                'user_pam_id'  => 'required_without:pam_id|integer',
            ]);

            $pamId = $request->pam_id ?? $request->user_pam_id;
            if (!RoleHelper::canAccessPam($pamId)) {
                return $this->forbiddenResponse("You can't access this PAM");
            }

            $customer = Customer::query()
                ->join('areas', 'areas.id', '=', 'customers.area_id')
                ->join('tariff_groups', 'tariff_groups.id', '=', 'customers.tariff_group_id')
                ->join('meters', 'meters.customer_id', '=', 'customers.id')
                ->leftJoin('users', 'users.id', '=', 'customers.user_id')
                ->select([
                    'customers.id',
                    'customers.customer_number',
                    'customers.name',
                    'customers.address',
                    'customers.phone',
                    'customers.is_active',
                    'areas.id as area_id',
                    'areas.name as area_name',
                    'tariff_groups.id as tariff_group_id',
                    'tariff_groups.name as tariff_group_name',
                    'meters.id as meter_id',
                    'meters.meter_number',
                    'meters.initial_installed_meter',
                    'users.id as user_id',
                    'users.name as user_name'
                ])
                ->where('customers.id', 9)
                ->where('customers.pam_id', $pamId)
                ->where('meters.is_active', true)
                ->firstOrFail();

            $areas = Area::select('id', 'name')->where('pam_id', $pamId)->get();
            $tariffGroups = TariffGroup::select('id', 'name')->where('pam_id', $pamId)->get();
            return $this->successResponse([
                'available_areas' => $areas,
                'available_tariff_groups' => $tariffGroups,
                'customer' => $customer,
            ], 'Success get customer detail');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Error get customer detail' . $e->getMessage());
        }
    }

    public function update(Request $request, $customerId): JsonResponse
    {
        try {
            $request['customer_id'] = $customerId;
            $validated = $request->validate([
                'customer_id' => 'required|integer|exists:customers,id',
                'pam_id'       => 'required_without:user_pam_id|integer',
                'user_pam_id'  => 'required_without:pam_id|integer',
            ]);

            $pamId = $request->pam_id ?? $request->user_pam_id;
            if (!RoleHelper::canAccessPam($pamId)) {
                return $this->forbiddenResponse("You can't access this PAM");
            }
            $customer = Customer::findOrFail($customerId);

            $validatedCustomer = $request->validate([
                "area_id" => "sometimes|integer|exists:areas,id",
                "tariff_group_id" => "sometimes|integer|exists:tariff_groups,id",
                "user_id" => "sometimes|nullable|integer|exists:users,id",
                "customer_number" => "sometimes|string",
                "name" => "sometimes|string",
                "address" => "sometimes|string",
                "phone" => "sometimes|string|min:10",
                "is_active" => "sometimes|boolean",
            ]);
            $validatedMeter = $request->validate([
                "new_meter_number" => "sometimes|integer",
                "new_initial_installed_meter" => "sometimes|decimal:0,2",
                "new_notes" => "sometimes|nullable|string"
            ]);

            $customer->update($validatedCustomer);

            if (isset($validatedMeter['new_meter_number'])) {
                // Soft delete old meter
                $oldMeter = Meter::where('customer_id', $customer->id)
                    ->where('is_active', true)
                    ->first();

                if ($oldMeter) {
                    $oldMeter->is_active = false;
                    $oldMeter->save();
                }

                // Create new meter
                Meter::create([
                    'pam_id' => $pamId,
                    'customer_id' => $customer->id,
                    'meter_number' => $validatedMeter['new_meter_number'],
                    'initial_installed_meter' => $validatedMeter['new_initial_installed_meter'],
                    'notes' => $validatedMeter['new_notes'] ?? null,
                ]);
            }
            return $this->successResponse(["updated_fields" => array_merge($validatedCustomer, $validatedMeter)], 'Success updated customer');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (QueryException $e) {
            return $this->errorResponse('Duplicate entry');
        } catch (\Exception $e) {
            return $this->errorResponse('Error updated customer' . $e->getMessage());
        }
    }
}
