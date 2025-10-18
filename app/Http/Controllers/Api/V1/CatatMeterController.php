<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\HasPamFiltering;
use App\Models\RegisteredMonth;
use App\Models\MeterReading;
use App\Models\Area;
use App\Models\Bill;
use App\Models\Customer;
use Carbon\Carbon;

class CatatMeterController extends Controller
{
    use HasPamFiltering;

    public function monthList(Request $request, int $year)
    {
        try {
            $user = $request->user();
            $user->load('pam'); // Ensure PAM relationship is loaded

            // Get registered months for the specified year
            $registeredMonths = RegisteredMonth::where('pam_id', $user->pam_id)
                ->whereYear('period', $year)
                ->orderBy('period', 'asc')
                ->get();
            $registeredYears = RegisteredMonth::where('pam_id', $user->pam_id)
                ->selectRaw('DISTINCT YEAR(period) as year')
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();

            // Transform data to match UI requirements
            $monthlyData = $registeredMonths->map(function ($month) {
                // Parse period (YYYY-MM-dd format)
                $periodDate = \Carbon\Carbon::createFromFormat('Y-m-d', $month->period);
                $recordedCustomers = MeterReading::where('pam_id', $month->pam_id)
                    ->where('registered_month_id', $month->id)->count();

                // Indonesian month names
                $monthNames = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember'
                ];

                return [
                    'id' => $month->id,
                    'month' => $periodDate->month,
                    'month_name' => $monthNames[$periodDate->month],
                    'year' => $periodDate->year,
                    'recorded_customers' => $recordedCustomers,
                    'total_customers' => $month->total_customers,
                    'total_usage' => round($month->total_usage, 1),
                    'total_bills' => $month->total_bills,
                    'status' => $month->status,
                    'has_data' => $month->total_customers > 0
                ];
            });

            $yearlyTotalBill = $registeredMonths->sum('total_bills');
            $yearlyTotalVolume = $registeredMonths->sum('total_usage');

            $response = [
                'year' => (int) $year,
                'available_years' => $registeredYears,
                'months_displayed' => $registeredMonths->count(),
                'monthly_data' => $monthlyData,
                'summary' => [
                    'reader' => [
                        'name' => $user->name,
                        'id' => $user->id
                    ],
                    'yearly_totals' => [
                        'total_bills' => $yearlyTotalBill,
                        'total_usage' => round($yearlyTotalVolume, 1),
                    ]
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Month list retrieved successfully',
                'data' => $response,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve month list: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createMonth(Request $request)
    {
        $user = $request->user();
        $totalCustomers = Customer::where(['pam_id' => $user->pam_id, 'is_active' => true])->count();
        // Validate request data
        $request->merge([
            'pam_id' => $user->pam_id,
            'total_customers' => $totalCustomers,
            'total_usage' => 0,
            'total_bills' => 0,
            'status' => 'open',
            'registered_by' => $user->id
        ]);


        $validateData = $request->validate([
            'pam_id' => 'exists:pams,id',
            'period' => 'required|date_format:Y-m-d|unique:registered_months,period,NULL,id,pam_id,' . $user->pam_id,
            'total_customers' => 'required|integer|min:0',
            'total_usage' => 'required|numeric|min:0',
            'total_bills' => 'required|numeric|min:0',
            'status' => 'required|in:open,closed',
            'registered_by' => 'required|exists:users,id',
        ]);
        $month = RegisteredMonth::create($validateData);
        return response()->json([
            'status' => 'success',
            'message' => 'Create month endpoint',
            'data' => $month,
        ], 200);
    }

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
                'sort_by' => 'nullable|in:customer_name,customer_number,area_name,meter_number,status',
                'sort_order' => 'nullable|in:asc,desc'
            ]);


            // Set defaults
            $perPage = $validated['per_page'] ?? 25;
            $sortBy = $validated['sort_by'] ?? 'customer_id';
            $sortOrder = $validated['sort_order'] ?? 'desc';

            // Build optimized query using indexes
            $query = MeterReading::query()
                ->select([
                    'meter_readings.id',
                    'meter_readings.meter_id',
                    'meter_readings.registered_month_id',
                    'meter_readings.previous_reading',
                    'meter_readings.current_reading',
                    'meter_readings.volume_usage',
                    'meter_readings.photo_url',
                    'meter_readings.status',
                    'meter_readings.notes',
                    'meter_readings.reading_by',
                    'meter_readings.reading_at',
                    'meter_readings.updated_at',
                    'meter_readings.created_at',
                    'customers.id as customer_id',
                    'customers.name as customer_name',
                    'customers.customer_number',
                    'customers.address as customer_address',
                    'customers.phone as customer_phone',
                    'meters.meter_number',
                    'areas.id as area_id',
                    'areas.name as area_name',
                    'registered_months.period'
                ])
                ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
                ->join('customers', 'meters.customer_id', '=', 'customers.id')
                ->join('areas', 'customers.area_id', '=', 'areas.id')
                ->join('registered_months', 'meter_readings.registered_month_id', '=', 'registered_months.id')
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
                case 'customer_name':
                    $query->orderBy('customers.name', $sortOrder);
                    break;
                case 'customer_number':
                    $query->orderBy('customers.customer_number', $sortOrder);
                    break;
                case 'area_id':
                    $query->orderBy('areas.id', $sortOrder);
                    break;
                case 'meter_number':
                    $query->orderBy('meters.meter_number', $sortOrder);
                    break;
                case 'status':
                    $query->orderBy('meter_readings.status', $sortOrder);
                    break;
                default:
                    $query->orderBy('meter_readings.updated_at', $sortOrder);
            }

            // Add secondary sort for consistent ordering
            if ($sortBy !== 'updated_at') {
                $query->orderBy('meter_readings.updated_at', 'desc');
            }

            // Execute paginated query
            $meterReadings = $query->paginate($perPage);

            // Format response for mobile UI
            $formattedData = $meterReadings->getCollection()->map(function ($reading) {
                $periodDate = Carbon::createFromFormat('Y-m-d', $reading->period);
                $bill = Bill::where('meter_reading_id', $reading->id)->first();

                return [
                    'id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                    'meter_number' => $reading->meter_number,
                    'reading_by' => $reading->readingBy->name,
                    'customer' => [
                        'id' => $reading->customer_id,
                        'name' => $reading->customer_name,
                        'number' => $reading->customer_number,
                        'phone' => $reading->customer_phone ?? null,
                    ],
                    'area' => [
                        'id' => $reading->area_id,
                        'name' => $reading->area_name,
                    ],
                    'period' => [
                        'month' => $periodDate->month,
                        'year' => $periodDate->year,
                    ],
                    'readings' => [
                        'previous' => $reading->previous_reading,
                        'current' => $reading->current_reading,
                        'volume_usage' => $reading->volume_usage,
                        'bill_amount' => $bill ? $bill->total_bill : null,
                    ],
                    'status' => [
                        'value' => $reading->status,
                        'label' => $this->getStatusLabel($reading->status),
                        'color' => ""
                    ],
                    'notes' => $reading->notes,
                    'photo_url' => $reading->photo_url, // This will use the accessor to get full URL
                    'created_at' => $reading->created_at,
                ];
            });

            // filter data for dropdowns
            $areas = Area::select('id', 'name')->where('pam_id', $pamId)->get();

            // Get summary statistics
            $summary = $this->getMeterReadingSummary($pamId, $validated['registered_month_id'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Data pencatatan meter berhasil diambil',
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $meterReadings->currentPage(),
                    'per_page' => $meterReadings->perPage(),
                    'total' => $meterReadings->total(),
                    'last_page' => $meterReadings->lastPage(),
                    'from' => $meterReadings->firstItem(),
                    'to' => $meterReadings->lastItem(),
                ],
                'summary' => $summary,
                'filter_data' => [
                    'areas' => $areas,
                    'status' => ['draft', 'pending', 'paid'],
                ],
                'filters' => [
                    'registered_month_id' => $validated['registered_month_id'] ?? null,
                    'search' => $validated['search'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'area_id' => $validated['area_id'] ?? null,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching meter reading list: ' . $e->getMessage(), [
                'pam_id' => $user->pam_id ?? null,
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data pencatatan meter',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get status label in Indonesian
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Belum diterbitkan',
            'pending' => 'Menunggu',
            'paid' => 'Lunas',
            default => 'Tidak Diketahui'
        };
    }

    /**
     * Get meter reading summary statistics
     *
     * @param int $pamId
     * @param int|null $registeredMonthId
     * @return array
     */
    private function getMeterReadingSummary(int $pamId, ?int $registeredMonthId = null): array
    {
        $query = MeterReading::where('pam_id', $pamId);

        if ($registeredMonthId) {
            $query->where('registered_month_id', $registeredMonthId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending,
            COUNT(CASE WHEN status = ? THEN 1 END) as completed,
            COUNT(CASE WHEN status = ? THEN 1 END) as verified,
            COALESCE(SUM(volume_usage), 0) as total_volume
        ', ['pending', 'completed', 'verified'])->first();

        return [
            'total_readings' => (int) $summary->total,
            'status_counts' => [
                'pending' => [
                    'count' => (int) $summary->pending,
                    'percentage' => $summary->total > 0 ? round(($summary->pending / $summary->total) * 100, 1) : 0,
                ],
                'completed' => [
                    'count' => (int) $summary->completed,
                    'percentage' => $summary->total > 0 ? round(($summary->completed / $summary->total) * 100, 1) : 0,
                ],
                'verified' => [
                    'count' => (int) $summary->verified,
                    'percentage' => $summary->total > 0 ? round(($summary->verified / $summary->total) * 100, 1) : 0,
                ],
            ],
            'total_volume' => [
                'value' => (float) $summary->total_volume,
                'formatted' => number_format($summary->total_volume, 1, ',', '.') . ' m³'
            ],
        ];
    }
}
