<?php

namespace Database\Seeders;

use App\Models\Meter;
use App\Models\Customer;
use App\Models\Pam;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class MeterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $customers = Customer::where('is_active', true)->get();
        $totalCreated = 0;

        foreach ($customers as $customer) {

            $serialNumber = $this->generateSerialNumber($customer->pam->code);

            // Installation date between 6 months to 5 years ago
            $installationDate = Carbon::now();

            // Last reading date between 1-60 days ago
            $lastReadingDate = Carbon::now()->subDays(rand(1, 60));

            Meter::create([
                'pam_id' => $customer->pam_id,
                'customer_id' => $customer->id,
                'meter_number' => $serialNumber,
                'is_active' => true,
                'installed_at' => $installationDate,
                'initial_installed_meter' => 0,
                'last_reading_at' => null,
                'notes' => $this->generateMeterNotes($faker, true),
            ]);
        }

        $this->command->info("Meter seeder completed successfully. Created {$totalCreated} meters for " . $customers->count() . " customers.");
    }

    private function generateSerialNumber(string $pamCode): string
    {
        // Format: PAM_CODE + YY + random 6 digits
        $year = Carbon::now()->format('y');
        $randomNumber = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        return $pamCode . $year . $randomNumber;
    }

    private function getRandomMeterSize(): string
    {
        $sizes = ['15mm', '20mm', '25mm', '30mm', '40mm', '50mm'];
        $weights = [40, 35, 15, 5, 3, 2]; // Probability weights

        $random = rand(1, 100);
        $cumulative = 0;

        for ($i = 0; $i < count($sizes); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $sizes[$i];
            }
        }

        return '20mm'; // Default fallback
    }

    private function generateMeterNotes($faker, bool $status): ?string
    {
        if ($status === false) {
            $inactiveReasons = [
                'Meteran rusak, perlu penggantian',
                'Meteran tidak terbaca dengan jelas',
                'Meteran bocor, sudah dilaporkan',
                'Meteran macet, pending perbaikan',
                'Meteran hilang, dalam proses investigasi'
            ];
            return $faker->randomElement($inactiveReasons);
        }

        // 30% of active meters have notes
        if (rand(1, 100) <= 30) {
            $activeNotes = [
                'Meteran dalam kondisi baik',
                'Pembacaan normal, tidak ada masalah',
                'Lokasi meteran mudah diakses',
                'Meteran terpasang dengan baik',
                'Kualitas air normal'
            ];
            return $faker->randomElement($activeNotes);
        }

        return null;
    }
}
