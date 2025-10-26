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

        if ($user->hasRole('customer')) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getPelangganDashboard($user->id));
        }

        // Kalau tidak ada satupun role cocok
        if (empty($dashboard['stats'])) {
            $dashboard = $this->mergeDashboard($dashboard, $this->getPelangganDashboard($user->id));
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
        $currentMonth = now()->startOfMonth();

        // Get bill statistics with single query using conditional aggregation
        $billStats = Bill::where('pam_id', $pamId)
            ->selectRaw('
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = "pending" AND due_date < ? THEN 1 END) as overdue_count,
                SUM(CASE WHEN status = "pending" THEN total_bill ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = "paid" THEN total_bill ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = "pending" AND due_date < ? THEN total_bill ELSE 0 END) as overdue_amount
            ', [$currentMonth, $currentMonth])
            ->first();

        return [
            'stats' => [
                'customer_pending_payment' => (int) $billStats->pending_count,
                'customer_paid_off' => (int) $billStats->paid_count,
                'customer_overdue' => (int) $billStats->overdue_count,
                'pending_amount' => (float) $billStats->pending_amount,
                'paid_amount' => (float) $billStats->paid_amount,
                'overdue_amount' => (float) $billStats->overdue_amount,
            ],
        ];
    }
    private function getPelangganDashboard($userId): array
    {
        // Single query with eager loading and aggregation
        $customers = Customer::where('user_id', $userId)
            ->where('is_active', true)
            ->with(['meters' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        // Get all customer IDs for efficient bill querying
        $customerIds = $customers->pluck('id');

        // Single query for all pending bills
        $totalBills = Bill::whereIn('customer_id', $customerIds)
            ->where('status', 'pending')
            ->sum('total_bill');

        // Calculate total usage from loaded meters (no additional queries)
        $totalUsage = $customers->sum(function ($customer) {
            return $customer->meters->sum('total_usage');
        });

        return [
            'stats' => [
                'total_customers' => $customers->count(),
                'current_usage' => (int) $totalUsage,
                'current_bill' => (float) $totalBills,
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
