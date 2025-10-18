<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RegisteredMonth;
use App\Models\Pam;
use App\Models\User;
use Carbon\Carbon;

class RegisteredMonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Mulai membuat RegisteredMonth...');

        $pams = Pam::all();
        $users = User::whereNotNull('pam_id')->get();

        if ($pams->isEmpty()) {
            $this->command->warn('Tidak ada data PAM. Jalankan PamSeeder terlebih dahulu.');
            return;
        }

        // Create registered months for the last 12 months and next 3 months
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->addMonths(3)->startOfMonth();

        $registeredMonths = [];

        foreach ($pams as $pam) {
            $this->command->info("Membuat RegisteredMonth untuk PAM: {$pam->name}");

            // Get a user from this PAM for registered_by
            $pamUser = $users->where('pam_id', $pam->id)->first() ?? $users->first();

            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                // Generate realistic data based on period
                $customerCount = rand(50, 200); // Random customer count per PAM
                $avgUsagePerCustomer = rand(15, 35); // m³ per customer
                $totalUsage = $customerCount * $avgUsagePerCustomer;
                $avgBillPerCustomer = rand(50000, 150000); // IDR per customer
                $totalBills = $customerCount * $avgBillPerCustomer;

                // Status based on time period
                $status = $this->getStatusForPeriod($currentDate);

                $registeredMonths[] = [
                    'pam_id' => $pam->id,
                    'period' => $currentDate->format('Y-m-d'),
                    'total_customers' => $customerCount,
                    'total_usage' => 0,
                    'total_bills' => 0,
                    'status' => $status,
                    'registered_by' => $pamUser->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $currentDate->addMonth();
            }
        }

        // Insert all data
        RegisteredMonth::insert($registeredMonths);

        $totalMonths = count($registeredMonths);
        $this->command->info("✅ RegisteredMonth seeder selesai! Total data: {$totalMonths} registered months");

        // Show statistics
        $this->showStatistics();
    }

    /**
     * Determine status based on period
     */
    private function getStatusForPeriod(Carbon $date): string
    {
        $now = Carbon::now();

        if ($date->isFuture()) {
            return 'open'; // Future months are open for registration
        } elseif ($date->diffInMonths($now) <= 2) {
            return rand(0, 1) ? 'open' : 'closed'; // Recent months mixed
        } else {
            return 'closed'; // Older months are mostly closed
        }
    }

    /**
     * Show statistics after seeding
     */
    private function showStatistics(): void
    {
        $stats = RegisteredMonth::selectRaw('
            status,
            COUNT(*) as count,
            AVG(total_customers) as avg_customers,
            AVG(total_usage) as avg_usage,
            SUM(total_bills) as total_bills
        ')
            ->groupBy('status')
            ->get();

        $this->command->info("\n📊 Statistik RegisteredMonth:");
        $this->command->table(
            ['Status', 'Jumlah', 'Rata-rata Customer', 'Rata-rata Usage (m³)', 'Total Bills (IDR)'],
            $stats->map(function ($stat) {
                return [
                    $stat->status,
                    $stat->count,
                    round($stat->avg_customers),
                    round($stat->avg_usage, 2),
                    'Rp ' . number_format($stat->total_bills, 0, ',', '.'),
                ];
            })->toArray()
        );

        // Period range
        $firstPeriod = RegisteredMonth::min('period');
        $lastPeriod = RegisteredMonth::max('period');

        $this->command->info("\n📅 Periode yang dibuat:");
        $this->command->info("Dari: {$firstPeriod} sampai {$lastPeriod}");
    }
}
