<?php

namespace Database\Seeders;

use App\Models\TariffGroup;
use App\Models\Pam;
use Illuminate\Database\Seeder;

class TariffGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pams = Pam::all();

        $tariffGroupsTemplate = [
            [
                'name' => 'Rumah Tangga Kecil',
                'description' => 'Untuk pelanggan rumah tangga dengan pemakaian rendah (0-10 m³)'
            ],
            [
                'name' => 'Rumah Tangga Sedang',
                'description' => 'Untuk pelanggan rumah tangga dengan pemakaian sedang (11-20 m³)'
            ],
            [
                'name' => 'Rumah Tangga Besar',
                'description' => 'Untuk pelanggan rumah tangga dengan pemakaian tinggi (>20 m³)'
            ],
            [
                'name' => 'Niaga Kecil',
                'description' => 'Untuk usaha niaga skala kecil seperti warung, toko kecil'
            ],
            [
                'name' => 'Niaga Menengah',
                'description' => 'Untuk usaha niaga skala menengah seperti restoran, hotel kecil'
            ],
            [
                'name' => 'Niaga Besar',
                'description' => 'Untuk usaha niaga skala besar seperti mall, hotel besar'
            ],
            [
                'name' => 'Industri',
                'description' => 'Untuk kebutuhan industri dan pabrik'
            ],
            [
                'name' => 'Sosial',
                'description' => 'Untuk fasilitas sosial seperti sekolah, rumah sakit, masjid'
            ]
        ];

        $totalCreated = 0;

        foreach ($pams as $pam) {
            foreach ($tariffGroupsTemplate as $tariffTemplate) {
                TariffGroup::create([
                    'pam_id' => $pam->id,
                    'name' => $tariffTemplate['name'],
                    'description' => $tariffTemplate['description'],
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("TariffGroup seeder completed successfully. Created {$totalCreated} tariff groups for " . $pams->count() . " PAMs.");
    }
}
