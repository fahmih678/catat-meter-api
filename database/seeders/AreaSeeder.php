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
            ['name' => 'Zona A - Perumahan Elite', 'code' => 'ZNA', 'description' => 'Wilayah perumahan elite dan apartemen mewah'],
            ['name' => 'Zona B - Perumahan Menengah', 'code' => 'ZNB', 'description' => 'Wilayah perumahan menengah dan cluster'],
            ['name' => 'Zona C - Pemukiman Padat', 'code' => 'ZNC', 'description' => 'Wilayah pemukiman padat penduduk'],
            ['name' => 'Zona D - Industri', 'code' => 'ZND', 'description' => 'Wilayah industri dan pabrik'],
            ['name' => 'Zona E - Komersial', 'code' => 'ZNE', 'description' => 'Wilayah komersial, pertokoan, dan perkantoran'],
        ];

        $totalCreated = 0;

        foreach ($pams as $pam) {
            foreach ($areasTemplate as $areaTemplate) {
                Area::create([
                    'pam_id' => $pam->id,
                    'name' => $areaTemplate['name'],
                    'code' => $pam->code . '-' . $areaTemplate['code'],
                    'description' => $areaTemplate['description'],
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Area seeder completed successfully. Created {$totalCreated} areas for " . $pams->count() . " PAMs.");
    }
}
