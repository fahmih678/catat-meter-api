<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Customer;
use App\Models\RegisteredMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class RegisteredMonthController extends Controller
{
    use HasPamFiltering;

    public function monthList(Request $request, $year): JsonResponse
    {
        try {
            $validator = Validator::make(['year' => $year], [
                'year' => 'integer',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = $request->user();

            // Get registered months with customer counts using subquery
            $registeredMonths = RegisteredMonth::select([
                'registered_months.id',
                'registered_months.period',
                'registered_months.total_customers',
                'registered_months.total_usage',
                'registered_months.total_bills',
                'registered_months.status',
                // Subquery to get recorded customer count
                DB::raw('(SELECT COUNT(*) FROM meter_readings
                              WHERE pam_id = registered_months.pam_id
                              AND registered_month_id = registered_months.id
                              AND deleted_at IS NULL) as recorded_customers')
            ])
                ->where('registered_months.pam_id', $user->pam_id)
                ->whereYear('registered_months.period', $year)
                ->orderBy('registered_months.period', 'asc')
                ->get();

            // Get available years for filtering with caching
            $cacheKey = "registered_years_pam_{$user->pam_id}";
            $registeredYears = Cache::remember($cacheKey, 3600, function () use ($user) {
                return RegisteredMonth::where('pam_id', $user->pam_id)
                    ->selectRaw('DISTINCT YEAR(period) as year')
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->toArray();
            });

            // Indonesian month names (extracted to helper)
            $monthNames = $this->getIndonesianMonthNames();

            // Transform data for response
            $monthlyData = $registeredMonths->map(function ($month) use ($monthNames) {
                $periodDate = \Carbon\Carbon::createFromFormat('Y-m-d', $month->period);

                return [
                    'id' => $month->id,
                    'month_name' => $monthNames[$periodDate->month] ?? 'Unknown',
                    'year' => $periodDate->year,
                    'recorded_customers' => (int) $month->recorded_customers,
                    'total_customers' => $month->total_customers,
                    'total_usage' => $month->total_usage,
                    'total_bills' => $month->total_bills,
                    'status' => $month->status,
                ];
            });

            $data = [
                'year' => (int) $year,
                'available_years' => $registeredYears,
                'items' => $monthlyData,
            ];
            return $this->successResponse($data, 'Month list retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error in month list', [
                'error_type' => get_class($e),
                'year' => $year ?? null,
                'pam_id' => $user->pam_id ?? null,
            ]);
            return $this->errorResponse('Terjadi kesalahan saat mengambil daftar bulan', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validateData = $request->validate([
                'pam_id' => 'required|integer|exists:pams,id',
                'period' => 'required|date_format:Y-m-d',
            ]);

            // Security: Multi-tenant access check
            if (!RoleHelper::isSuperAdmin() && RoleHelper::getUserPamId() !== $validateData['pam_id']) {
                return $this->forbiddenResponse('You are not allowed to create month for this PAM.');
            }

            // Parse period once for efficiency
            $periodDate = Carbon::parse($validateData['period']);

            // Use transaction to prevent race conditions
            DB::beginTransaction();
            try {
                // Optimized duplicate check using Carbon methods
                $exists = RegisteredMonth::where('pam_id', $validateData['pam_id'])
                    ->whereYear('period', $periodDate->year)
                    ->whereMonth('period', $periodDate->month)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return $this->errorResponse('Failed to create month. The period has already been taken', 409);
                }

                // Get total customers with proper index usage
                $totalCustomers = Customer::where('pam_id', $validateData['pam_id'])
                    ->where('is_active', true)
                    ->count();

                // Prepare complete data for insertion
                $monthData = [
                    'pam_id' => $validateData['pam_id'],
                    'period' => $validateData['period'],
                    'total_customers' => $totalCustomers,
                    'total_usage' => 0,
                    'total_bills' => 0,
                    'status' => 'open',
                    'registered_by' => $user->id
                ];

                // Create within transaction
                $month = RegisteredMonth::create($monthData);

                // Invalidate cache for this PAM
                Cache::forget("registered_years_pam_{$validateData['pam_id']}");

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->createdResponse($month, 'Month created successfully');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle specific database errors
            if ($e->errorInfo[1] === 1062) {
                return $this->errorResponse('Failed to create month. The period already exists.', 409);
            }
            return $this->errorResponse('Terjadi kesalahan database saat membuat bulan', 500);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat membuat bulan registrasi', 500);
        }
    }

    public function getAvailableMonthsReport(Request $request): JsonResponse
    {
        try {
            // Get user's PAM ID from middleware
            $userPamId = $request->attributes->get('user_pam_id');
            $isSuperAdmin = $request->attributes->get('is_superadmin', false);
            // Get available registered months for user's PAM with enhanced data
            $availableMonthsQuery = RegisteredMonth::select(
                'id',
                'period',
                'status',
                'total_payment',
                'total_paid_customers'
            )
                ->orderBy('period', 'desc');

            // Apply PAM filtering (non-superadmin only)
            if (!$isSuperAdmin && $userPamId) {
                $availableMonthsQuery->where('pam_id', $userPamId);
            }
            $availableMonths = $availableMonthsQuery->get()
                ->map(function ($month) {
                    try {
                        return [
                            'id' => $month->id,
                            'period' => Carbon::parse($month->period)->translatedFormat('F Y'),
                            'status' => $month->status,
                            'total_payment' => (float) $month->total_payment,
                            'total_paid_customers' => (int) $month->total_paid_customers,
                        ];
                    } catch (\Exception) {
                        return [
                            'id' => $month->id,
                            'period' => $month->period,
                            'status' => $month->status,
                            'total_payment' => (float) $month->total_payment,
                            'total_paid_customers' => (int) $month->total_paid_customers,
                        ];
                    }
                });

            return $this->successResponse($availableMonths, 'Available registered months retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat mengambil daftar bulan aktif', 500);
        }
    }

    /**
     * Get Indonesian month names
     *
     * @return array
     */
    private function getIndonesianMonthNames(): array
    {
        return [
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
    }
}
