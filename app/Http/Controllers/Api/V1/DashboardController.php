<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Bill;
use App\Models\Customer;
use App\Http\Traits\HasPamFiltering;
use App\Models\User;
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
            'user' => [
                'name' => $user->name,
                'roles' => $user->roles->pluck('name'),
            ],
            'pam' => [
                'code' => $user->pam ? $user->pam->code : null,
                'name' => $user->pam ? $user->pam->name : null,
                'logo_url' => $user->pam ? $user->pam->logo_url : null,
                'is_active' => $user->pam ? $user->pam->is_active : null,
                'coordinate' => $user->pam ? $user->pam->coordinate : null,
            ],

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
        $dashboard = ['stats' => []];

        if ($user->hasRole('superadmin')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getSuperAdminDashboard());
        }

        if ($user->hasRole('admin')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getPamAdminDashboard($user->pam_id));
        }

        if ($user->hasRole('catat_meter')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getCatatMeterDashboard($user->pam_id));
        }

        if ($user->hasRole('pembayaran')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getLoketDashboard($user->pam_id));
        }

        if ($user->hasRole('pelanggan')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getPelangganDashboard($user->id));
        }

        // Kalau tidak ada satupun role cocok
        if (empty($dashboard['stats'])) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getDefaultDashboard());
        }

        return $dashboard;
    }

    private function getSuperAdminDashboard(): array
    {
        $totalMeters = Meter::count();
        $totalCustomers = Customer::count();

        return [
            'stats' => [
                'total_meters' => $totalMeters,
                'total_customers' => $totalCustomers,
            ],
        ];
    }

    private function getPamAdminDashboard($pamId): array
    {
        $totalUsers = User::where('pam_id', $pamId)->count();
        $totalActiveMeters = Meter::where('pam_id', $pamId)->where('is_active', true)->count();
        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_active_meters' => $totalActiveMeters,
            ],
        ];
    }

    /**
     * Catat Meter dashboard - Reading focused
     */
    private function getCatatMeterDashboard($pamId): array
    {
        $totalActiveMeters = Meter::where('pam_id', $pamId)->where('is_active', true)->count();
        $totalReadingsThisMonth = MeterReading::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        return [
            'stats' => [
                'total_active_meters' => $totalActiveMeters,
                'this_month_readings' => $totalReadingsThisMonth,
            ],
        ];
    }

    private function getLoketDashboard($pamId): array
    {
        $totalPendingPayments = MeterReading::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'pending')
            ->count();
        $totalPaidOff = Bill::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'paid')
            ->count();
        $allPendingPaymentsInPam = MeterReading::where('pam_id', $pamId)
            ->where('status', '!=', 'paid')
            ->whereHas('registeredMonth', function ($query) {
                $query->where('period', '<', now()->startOfMonth());
            })
            ->count();

        // Implement Payment specific dashboard data retrieval
        return [
            'stats' => [
                'customer_pending_payment' => $totalPendingPayments,
                'customer_paid_off' => $totalPaidOff,
                'customer_overdue' => $allPendingPaymentsInPam,
            ],
        ];
    }

    /**
     * Helper untuk menggabungkan dashboard stats
     */
    private function mergeDashboard(array $base, array $additional): array
    {
        if (isset($additional['stats'])) {
            $base['stats'] = array_merge($base['stats'], $additional['stats']);
        }
        return $base;
    }
}
