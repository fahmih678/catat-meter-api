<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Pam;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pams = Pam::all();

        $areasTemplate = [
            ['name' => 'RT 01', 'code' => 'RT1', 'description' => 'Wilayah RT 01'],
            ['name' => 'RT 02', 'code' => 'RT2', 'description' => 'Wilayah RT 02'],
            ['name' => 'RT 03', 'code' => 'RT3', 'description' => 'Wilayah RT 03'],
        ];

        $totalCreated = 0;

        foreach ($pams as $pam) {
            foreach ($areasTemplate as $areaTemplate) {
                Area::create([
                    'pam_id' => $pam->id,
                    'name' => $areaTemplate['name'],
                    'code' => $areaTemplate['code'],
                    'description' => $areaTemplate['description'],
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Area seeder completed successfully. Created {$totalCreated} areas for " . $pams->count() . " PAMs.");
    }
}
