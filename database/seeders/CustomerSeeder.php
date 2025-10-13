<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pam;
use App\Models\Area;
use App\Models\TariffGroup;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Indonesian locale
        $pams = Pam::where('is_active', true)->get();
        $totalCreated = 0;

        foreach ($pams as $pam) {
            $areas = Area::where('pam_id', $pam->id)->get();
            $tariffGroups = TariffGroup::where('pam_id', $pam->id)->get();

            // Generate 15-25 customers per PAM
            $customerCount = rand(200, 250);

            for ($i = 1; $i <= $customerCount; $i++) {
                $area = $areas->random();
                $tariffGroup = $tariffGroups->random();

                // Generate customer number based on PAM code and sequence
                $customerNumber = $pam->code . sprintf('%04d', $i);

                // Generate realistic Indonesian names and addresses
                $name = $this->generateIndonesianName($faker);
                $address = $this->generateIndonesianAddress($faker, $area->name);
                $phone = $this->generateIndonesianPhone($faker);

                // 90% customers are active, 10% inactive
                $status = (rand(1, 100) <= 90) ? true : false;

                Customer::create([
                    'pam_id' => $pam->id,
                    'area_id' => $area->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'customer_number' => $customerNumber,
                    'name' => $name,
                    'address' => $address,
                    'phone' => $phone,
                    'is_active' => $status,
                ]);

                $totalCreated++;
            }
        }

        $this->command->info("Customer seeder completed successfully. Created {$totalCreated} customers for " . $pams->count() . " PAMs.");
    }

    private function generateIndonesianName($faker): string
    {
        $firstNames = [
            'Andi',
            'Budi',
            'Citra',
            'Dewi',
            'Eko',
            'Fitri',
            'Gita',
            'Hadi',
            'Indra',
            'Joko',
            'Kartika',
            'Lestari',
            'Maya',
            'Novi',
            'Omar',
            'Putri',
            'Qori',
            'Rini',
            'Sari',
            'Tuti',
            'Usman',
            'Vina',
            'Wawan',
            'Yanti',
            'Zaki',
            'Agus',
            'Bambang',
            'Dian',
            'Erni',
            'Fajar'
        ];

        $lastNames = [
            'Wijaya',
            'Santoso',
            'Pratama',
            'Sari',
            'Utama',
            'Kusuma',
            'Permana',
            'Lestari',
            'Mahendra',
            'Setiawan',
            'Nurjaya',
            'Purnama',
            'Handayani',
            'Ramadhani',
            'Susanto',
            'Kartika',
            'Saputra',
            'Anggraini',
            'Firmansyah',
            'Wulandari'
        ];

        return $faker->randomElement($firstNames) . ' ' . $faker->randomElement($lastNames);
    }

    private function generateIndonesianAddress($faker, $areaName): string
    {
        $streets = [
            'Jl. Merdeka',
            'Jl. Sudirman',
            'Jl. Thamrin',
            'Jl. Gatot Subroto',
            'Jl. Diponegoro',
            'Jl. Ahmad Yani',
            'Jl. Juanda',
            'Jl. Imam Bonjol',
            'Jl. Hayam Wuruk',
            'Jl. Gajah Mada',
            'Jl. Veteran',
            'Jl. Proklamasi',
            'Jl. Pemuda',
            'Jl. Pahlawan',
            'Jl. Kartini'
        ];

        $number = rand(1, 999);
        $rt = rand(1, 20);
        $rw = rand(1, 15);

        return $faker->randomElement($streets) . " No. {$number}, RT {$rt}/RW {$rw}, {$areaName}";
    }

    private function generateIndonesianPhone($faker): ?string
    {
        // 80% of customers have phone numbers
        if (rand(1, 100) <= 80) {
            $prefixes = ['0811', '0812', '0813', '0821', '0822', '0823', '0831', '0832', '0833', '0851', '0852', '0853'];
            $prefix = $faker->randomElement($prefixes);
            $number = $faker->numerify('########');
            return $prefix . $number;
        }

        return null;
    }
}
