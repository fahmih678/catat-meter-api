<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\RegisteredMonth;
use App\Models\User;
use Carbon\Carbon;

class MeterReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing data for foreign key relationships
        $meters = Meter::with(['customer.area', 'customer.pam'])->get();
        $registeredMonths = RegisteredMonth::all();
        $users = User::all();

        if ($meters->isEmpty() || $registeredMonths->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Tidak ada data Meter, RegisteredMonth, atau User. Jalankan seeder terkait terlebih dahulu.');
            return;
        }

        $this->command->info('Mulai membuat data MeterReading...');

        // Status options for meter readings
        $statuses = ['draft', 'pending', 'paid'];
        $statusWeights = [0.3, 0.5, 0.2]; // 30% draft, 50% pending, 20% paid

        $meterReadings = [];
        $batchSize = 100;

        // Create meter readings for each registered month
        foreach ($registeredMonths as $registeredMonth) {
            $this->command->info("Membuat data untuk periode: {$registeredMonth->period}");

            // Get meters for the same PAM as registered month
            $pamMeters = $meters->where('customer.pam_id', $registeredMonth->pam_id);

            if ($pamMeters->isEmpty()) {
                continue;
            }

            foreach ($pamMeters as $meter) {
                // 80% chance to have a reading for each meter in each month
                if (rand(1, 100) <= 80) {
                    // Get random user from the same PAM for reading_by
                    $pamUsers = $users->where('pam_id', $registeredMonth->pam_id);
                    $readingUser = $pamUsers->isNotEmpty() ? $pamUsers->random() : $users->random();

                    // Generate realistic meter readings
                    $previousReading = rand(100, 500); // Base reading
                    $monthlyUsage = $this->generateRealisticUsage($meter->customer->area->name ?? 'Unknown');
                    $currentReading = $previousReading + $monthlyUsage;

                    // Select status based on weights
                    $status = $this->getWeightedRandomStatus($statuses, $statusWeights);

                    // Generate reading date within the month
                    $periodDate = Carbon::createFromFormat('Y-m-d', $registeredMonth->period);
                    $readingDate = $periodDate->copy()->addDays(rand(1, 28));

                    $meterReadings[] = [
                        'pam_id' => $registeredMonth->pam_id,
                        'meter_id' => $meter->id,
                        'registered_month_id' => $registeredMonth->id,
                        'previous_reading' => $previousReading,
                        'current_reading' => $currentReading, // Always has value
                        'volume_usage' => $monthlyUsage, // Always has value
                        'photo_url' => null,
                        'status' => $status,
                        'notes' => $this->generateRandomNotes($status),
                        'reading_by' => $readingUser->id,
                        'reading_at' => $readingDate, // Always has value
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches to improve performance
                    if (count($meterReadings) >= $batchSize) {
                        MeterReading::insert($meterReadings);
                        $meterReadings = [];
                        $this->command->info("Batch " . (ceil(count($meterReadings) / $batchSize)) . " data berhasil diinsert");
                    }
                }
            }
        }

        // Insert remaining data
        if (!empty($meterReadings)) {
            MeterReading::insert($meterReadings);
        }

        $totalReadings = MeterReading::count();
        $this->command->info("✅ Seeder MeterReading selesai! Total data: {$totalReadings} meter readings");

        // Show statistics
        $this->showStatistics();
    }

    /**
     * Generate realistic water usage based on area type
     */
    private function generateRealisticUsage(string $areaName): float
    {
        // Different usage patterns based on area
        $baseUsage = match (true) {
            str_contains(strtolower($areaName), 'perumahan') => rand(8, 25),      // Residential
            str_contains(strtolower($areaName), 'komersial') => rand(20, 60),     // Commercial
            str_contains(strtolower($areaName), 'industri') => rand(50, 200),     // Industrial
            str_contains(strtolower($areaName), 'pusat') => rand(15, 40),         // City center
            default => rand(10, 30),                                              // Default residential
        };

        // Add some randomness (±20%)
        $variation = $baseUsage * 0.2;
        return round($baseUsage + (rand(-100, 100) / 100) * $variation, 2);
    }

    /**
     * Get random status based on weights
     */
    private function getWeightedRandomStatus(array $statuses, array $weights): string
    {
        $random = rand(1, 100) / 100;
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $statuses[$index];
            }
        }

        return $statuses[0]; // fallback
    }

    /**
     * Generate realistic notes based on status
     */
    private function generateRandomNotes(string $status): ?string
    {
        $notes = [
            'draft' => [
                'Meter belum terbaca',
                'Pelanggan tidak ada di tempat',
                'Akses meter terhalang',
                null,
            ],
            'pending' => [
                'Pembacaan normal',
                'Meter dalam kondisi baik',
                'Pencatatan selesai',
                'Perlu verifikasi usage tinggi',
                null,
            ],
            'paid' => [
                'Pembayaran telah diterima',
                'Tagihan lunas',
                'Pembacaan dan pembayaran selesai',
                null,
            ],
        ];

        $statusNotes = $notes[$status] ?? [null];
        return $statusNotes[array_rand($statusNotes)];
    }

    /**
     * Show statistics after seeding
     */
    private function showStatistics(): void
    {
        $stats = MeterReading::selectRaw('
            status,
            COUNT(*) as count,
            AVG(volume_usage) as avg_usage,
            SUM(volume_usage) as total_usage
        ')
            ->groupBy('status')
            ->get();

        $this->command->info("\n📊 Statistik MeterReading:");
        $this->command->table(
            ['Status', 'Jumlah', 'Rata-rata Usage (m³)', 'Total Usage (m³)'],
            $stats->map(function ($stat) {
                return [
                    $stat->status,
                    $stat->count,
                    $stat->avg_usage ? round($stat->avg_usage, 2) : '-',
                    $stat->total_usage ? round($stat->total_usage, 2) : '-',
                ];
            })->toArray()
        );

        // PAM statistics
        $pamStats = MeterReading::join('registered_months', 'meter_readings.registered_month_id', '=', 'registered_months.id')
            ->join('pams', 'meter_readings.pam_id', '=', 'pams.id')
            ->selectRaw('
                pams.name as pam_name,
                COUNT(*) as reading_count,
                SUM(volume_usage) as total_usage
            ')
            ->groupBy('pams.id', 'pams.name')
            ->get();

        $this->command->info("\n🏢 Statistik per PAM:");
        $this->command->table(
            ['PAM', 'Jumlah Reading', 'Total Usage (m³)'],
            $pamStats->map(function ($stat) {
                return [
                    $stat->pam_name,
                    $stat->reading_count,
                    $stat->total_usage ? round($stat->total_usage, 2) : '-',
                ];
            })->toArray()
        );
    }
}
