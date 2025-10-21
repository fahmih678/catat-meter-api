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
        if ($user->hasRole('superadmin')) {
            return $this->getSuperAdminDashboard();
        }

        if ($user->hasRole('admin')) {
            return $this->getPamAdminDashboard($user->pam_id);
        }

        if ($user->hasRole('catat_meter')) {
            return $this->getCatatMeterDashboard($user->pam_id);
        }

        if ($user->hasRole('pembayaran')) {
            return $this->getLoketDashboard($user->pam_id);
        }

        if ($user->hasRole('pelanggan')) {
            return $this->getPelangganDashboard($user->id);
        }

        return $this->getDefaultDashboard();
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
        $totalCustomers = Meter::where('pam_id', $pamId)->count();
        $totalReadingsThisMonth = MeterReading::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        $totalPaidOff = Bill::where('pam_id', $pamId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'paid')
            ->count();
        return [
            'stats' => [
                'total_customers' => $totalCustomers,
                'this_month_readings' => $totalReadingsThisMonth,
                'customer_paid_off' => $totalPaidOff,
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
