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
use Illuminate\Support\Facades\Validator;

class RegisteredMonthController extends Controller
{
    use HasPamFiltering;

    public function monthList(Request $request, $year)
    {
        try {
            $validator = Validator::make(['year' => $year], [
                'year' => 'integer',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = $request->user();

            // OPTIMIZED: Single query with subquery for customer counts
            $registeredMonths = RegisteredMonth::select([
                'registered_months.id',
                'registered_months.period',
                'registered_months.total_customers',
                'registered_months.total_usage',
                'registered_months.total_bills',
                'registered_months.status',
                // Subquery to fix N+1 problem
                DB::raw('(SELECT COUNT(*) FROM meter_readings
                              WHERE pam_id = registered_months.pam_id
                              AND registered_month_id = registered_months.id
                              AND deleted_at IS NULL) as recorded_customers')
            ])
                ->where('registered_months.pam_id', $user->pam_id)
                ->whereYear('registered_months.period', $year)
                ->orderBy('registered_months.period', 'asc')
                ->get();

            // OPTIMIZED: Single query for available years using CTE/common table
            $registeredYears = RegisteredMonth::where('pam_id', $user->pam_id)
                ->selectRaw('DISTINCT YEAR(period) as year')
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();

            // Indonesian month names (extracted to helper)
            $monthNames = $this->getIndonesianMonthNames();

            // Transform data (now N+1 free!)
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
            return $this->errorResponse('Failed to retrieve month list: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
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
            return $this->errorResponse('Failed to create month: Database error occurred.', 500);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create month: ' . $e->getMessage(), 500);
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
