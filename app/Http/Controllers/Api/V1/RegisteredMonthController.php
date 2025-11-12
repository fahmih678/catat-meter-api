<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Http\Traits\HasPamFiltering;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\RegisteredMonth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisteredMonthController extends Controller
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
                    'month_name' => $monthNames[$periodDate->month],
                    'year' => $periodDate->year,
                    'recorded_customers' => $recordedCustomers,
                    'total_customers' => $month->total_customers,
                    'total_usage' => $month->total_usage,
                    'total_bills' => $month->total_bills,
                    'status' => $month->status,
                ];
            });

            $yearlyTotalBill = $registeredMonths->sum('total_bills');
            $yearlyTotalVolume = $registeredMonths->sum('total_usage');

            $data = [
                'year' => (int) $year,
                'available_years' => $registeredYears,
                'items' => $monthlyData,
            ];

            return $this->successResponse($data, 'Month list retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve month list' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $periodMonth = date('Y-m', strtotime($request->period));

            $exists = RegisteredMonth::where('pam_id', $user->pam_id)
                ->whereRaw('DATE_FORMAT(period, "%Y-%m") = ?', [$periodMonth])
                ->exists();

            if ($exists) {
                return $this->errorResponse('Month already exists', 400);
            }

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

            return $this->successResponse($month, 'Month created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create month ' . $e->getMessage(), 500);
        }
    }
}
