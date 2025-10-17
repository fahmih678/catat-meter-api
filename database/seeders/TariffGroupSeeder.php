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
                'name' => 'rumah tangga',
                'description' => 'Untuk pelanggan rumah tangga'
            ],
            [
                'name' => 'sosial',
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
