<?php

namespace Database\Seeders;

use App\Models\FixedFee;
use App\Models\TariffGroup;
use App\Models\Pam;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FixedFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pams = Pam::all();
        $totalCreated = 0;

        foreach ($pams as $pam) {
            $tariffGroups = TariffGroup::where('pam_id', $pam->id)->get();

            foreach ($tariffGroups as $tariffGroup) {
                // Biaya Beban (fixed cost based on tariff group)
                $bebanFee = $this->getBebanFeeByTariff($tariffGroup->name);
                FixedFee::create([
                    'pam_id' => $pam->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'name' => 'Biaya Beban',
                    'amount' => $bebanFee,
                    'effective_from' => Carbon::now()->subYear(),
                    'effective_to' => null,
                    'description' => 'Biaya tetap bulanan untuk golongan ' . $tariffGroup->name,
                    'is_active' => true,
                ]);

                // Biaya Administrasi (same for all groups)
                FixedFee::create([
                    'pam_id' => $pam->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'name' => 'Biaya Administrasi',
                    'amount' => 5000.00,
                    'effective_from' => Carbon::now()->subYear(),
                    'effective_to' => null,
                    'description' => 'Biaya administrasi bulanan',
                    'is_active' => true,
                ]);

                // Biaya Meteran (based on tariff group)
                $meteranFee = $this->getMeteranFeeByTariff($tariffGroup->name);
                FixedFee::create([
                    'pam_id' => $pam->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'name' => 'Biaya Meteran',
                    'amount' => $meteranFee,
                    'effective_from' => Carbon::now()->subYear(),
                    'effective_to' => null,
                    'description' => 'Biaya pemeliharaan meteran untuk golongan ' . $tariffGroup->name,
                    'is_active' => true,
                ]);

                $totalCreated += 3;
            }
        }

        $this->command->info("FixedFee seeder completed successfully. Created {$totalCreated} fixed fees for " . $pams->count() . " PAMs.");
    }

    private function getBebanFeeByTariff(string $tariffName): float
    {
        $bebanFees = [
            'Rumah Tangga Kecil' => 15000.00,
            'Rumah Tangga Sedang' => 25000.00,
            'Rumah Tangga Besar' => 35000.00,
            'Niaga Kecil' => 50000.00,
            'Niaga Menengah' => 100000.00,
            'Niaga Besar' => 200000.00,
            'Industri' => 300000.00,
            'Sosial' => 20000.00,
        ];

        return $bebanFees[$tariffName] ?? 25000.00;
    }

    private function getMeteranFeeByTariff(string $tariffName): float
    {
        $meteranFees = [
            'Rumah Tangga Kecil' => 3000.00,
            'Rumah Tangga Sedang' => 4000.00,
            'Rumah Tangga Besar' => 5000.00,
            'Niaga Kecil' => 7500.00,
            'Niaga Menengah' => 10000.00,
            'Niaga Besar' => 15000.00,
            'Industri' => 20000.00,
            'Sosial' => 3000.00,
        ];

        return $meteranFees[$tariffName] ?? 5000.00;
    }
}
