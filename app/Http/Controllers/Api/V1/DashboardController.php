<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Meter;
use App\Models\MeterRecord;
use App\Models\Bill;
use App\Http\Traits\HasPamFiltering;
use Illuminate\Http\JsonResponse;


class DashboardController extends Controller
{
    use HasPamFiltering;

    /**
     * Show the dashboard based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->load(['roles', 'pam']);

        // Get dashboard data based on role
        $dashboardData = $this->getDashboardData($user);

        $data = [
            'user' => $user,
            'stats' => $dashboardData['stats'],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Dashboard data retrieved successfully',
            'data' => $data
        ], 200);
    }

    /**
     * Get dashboard data based on user role and PAM access
     */
    private function getDashboardData($user): array
    {
        if ($user->hasRole('catat_meter')) {
            return $this->getCatatMeterDashboard($user->pam_id);
        }

        return $this->getDefaultDashboard();
    }

    /**
     * Catat Meter dashboard - Reading focused
     */
    private function getCatatMeterDashboard($pamId): array
    {
        $totalActiveMeters = Meter::where('pam_id', $pamId)->where('status', 'active')->count();
        $totalReadingsThisMonth = MeterRecord::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        $totalPendingPayments = MeterRecord::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'pending')
            ->count();
        $totalPaidOff = Bill::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'paid')
            ->count();
        $allPendingPaymentsInPam = MeterRecord::where('pam_id', $pamId)
            ->whereYear('created_at', '<', now()->year)
            ->whereMonth('created_at', '<', now()->month)
            ->whereNot('status', 'paid')
            ->count();


        return [
            'stats' => [
                'assigned_meters' => $totalActiveMeters,
                'this_month_readings' => $totalReadingsThisMonth,
                // pembayaran
                'customer_pending_payment' => $totalPendingPayments,
                'customer_paid_off' => $totalPaidOff,
                'customer_overdue' => $allPendingPaymentsInPam,
            ],
        ];
    }

    /**
     * Default dashboard for other roles
     */
    private function getDefaultDashboard(): array
    {
        return [
            'stats' => [
                'welcome_message' => 'Selamat datang di sistem!',
            ],
            'charts' => [],
            'recent_activities' => [],
        ];
    }
}
