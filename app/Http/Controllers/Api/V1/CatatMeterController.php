<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\HasPamFiltering;
use App\Models\RegisteredMonth;
use App\Models\MeterReading;
use Carbon\Carbon;

class CatatMeterController extends Controller
{
    use HasPamFiltering;
    /**
     * Get list of months with recorded meter readings
     */
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
}
