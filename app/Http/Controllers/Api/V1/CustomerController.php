<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\RegisteredMonth;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Get list of customers that haven't been recorded in meter reading
     * for specific PAM and area in a registered month
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
                'registered_month_id' => 'required|integer|exists:registered_months,id',
                'area_id' => 'nullable|integer|exists:areas,id',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:10|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|in:customer_name,customer_number,area_name,meter_number',
                'sort_order' => 'nullable|in:asc,desc'
            ]);

            // Verify registered month belongs to user's PAM
            $registeredMonth = RegisteredMonth::where('id', $validated['registered_month_id'])
                ->where('pam_id', $pamId)
                ->first();

            if (!$registeredMonth) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registered month tidak ditemukan atau tidak sesuai dengan PAM Anda',
                ], 404);
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
                        ->where('meter_readings.registered_month_id', $validated['registered_month_id']);
                });

            // Apply area filter if provided
            if (!empty($validated['area_id'])) {
                $query->where('customers.area_id', $validated['area_id']);
            }

            // Apply search filter
            if (!empty($validated['search'])) {
                $search = trim($validated['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('customers.name', 'LIKE', "%{$search}%")
                        ->orWhere('customers.customer_number', 'LIKE', "%{$search}%")
                        ->orWhere('customers.address', 'LIKE', "%{$search}%")
                        ->orWhere('meters.meter_number', 'LIKE', "%{$search}%");
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
                default:
                    $query->orderBy('customers.name', 'asc');
            }

            // Add secondary sort for consistent ordering
            if ($sortBy !== 'customer_name') {
                $query->orderBy('customers.name', 'asc');
            }

            // Execute paginated query
            $customers = $query->paginate($perPage);

            // Format response for mobile UI
            $formattedData = $customers->getCollection()->map(function ($customer) use ($registeredMonth) {
                $periodDate = Carbon::createFromFormat('Y-m-d', $registeredMonth->period);
                
                return [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'number' => $customer->customer_number,
                    'address' => $customer->address,
                    'phone' => $customer->phone,
                    'area' => [
                        'id' => $customer->area_id,
                        'name' => $customer->area_name,
                    ],
                    'meter' => [
                        'id' => $customer->meter_id,
                        'number' => $customer->meter_number,
                        'initial_reading' => [
                            'value' => $customer->initial_installed_meter,
                            'formatted' => number_format($customer->initial_installed_meter, 0, ',', '.') . ' m³',
                        ],
                        'installed_at' => [
                            'datetime' => $customer->installed_at,
                            'formatted' => $customer->installed_at ? 
                                Carbon::parse($customer->installed_at)->copy()->locale('id')->isoFormat('DD MMMM YYYY') : 
                                null,
                        ],
                    ],
                    'tariff_group' => $customer->tariff_group_name,
                    'period' => [
                        'id' => $registeredMonth->id,
                        'month' => $periodDate->month,
                        'year' => $periodDate->year,
                        'formatted' => $periodDate->copy()->locale('id')->isoFormat('MMMM YYYY'),
                    ],
                    'status' => [
                        'value' => 'unrecorded',
                        'label' => 'Belum Tercatat',
                        'color' => '#FF5722', // Deep Orange
                    ],
                ];
            });

            // Get summary statistics
            $summary = $this->getUnrecordedSummary($pamId, $validated['registered_month_id'], $validated['area_id'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Data customer belum tercatat berhasil diambil',
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ],
                'summary' => $summary,
                'period' => [
                    'id' => $registeredMonth->id,
                    'formatted' => Carbon::createFromFormat('Y-m-d', $registeredMonth->period)
                        ->copy()->locale('id')->isoFormat('MMMM YYYY'),
                    'status' => $registeredMonth->status,
                ],
                'filters' => [
                    'registered_month_id' => $validated['registered_month_id'],
                    'area_id' => $validated['area_id'] ?? null,
                    'search' => $validated['search'] ?? null,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching unrecorded customer list: ' . $e->getMessage(), [
                'pam_id' => $user->pam_id ?? null,
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data customer belum tercatat',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
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
}
