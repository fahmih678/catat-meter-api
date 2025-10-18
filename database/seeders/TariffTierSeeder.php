<?php

namespace Database\Seeders;

use App\Models\TariffTier;
use App\Models\TariffGroup;
use App\Models\Pam;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TariffTierSeeder extends Seeder
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
                $tiers = $this->getTariffTiersByGroup($tariffGroup->name);

                foreach ($tiers as $tier) {
                    TariffTier::create([
                        'pam_id' => $pam->id,
                        'tariff_group_id' => $tariffGroup->id,
                        'meter_min' => $tier['meter_min'],
                        'meter_max' => $tier['meter_max'],
                        'amount' => $tier['amount'],
                        'effective_from' => Carbon::now()->subYear(),
                        'effective_to' => null,
                        'description' => $tier['description'],
                        'is_active' => true,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command->info("TariffTier seeder completed successfully. Created {$totalCreated} tariff tiers for " . $pams->count() . " PAMs.");
    }

    /**
     * Get tariff tiers configuration based on tariff group
     */
    private function getTariffTiersByGroup(string $groupName): array
    {
        switch ($groupName) {
            case 'rumah tangga':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 999.00,
                        'amount' => 1000.00,
                        'description' => '0-999 m³ per bulan'
                    ],
                ];

            case 'sosial':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 10.00,
                        'amount' => 0.00,
                        'description' => '0-10 m³ per bulan'
                    ],
                    [
                        'meter_min' => 10.01,
                        'meter_max' => 999.00,
                        'amount' => 600.00,
                        'description' => '11-20 m³ per bulan'
                    ],
                ];
            default:
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 999.00,
                        'amount' => 1000.00,
                        'description' => '0-999 m³ per bulan'
                    ],
                ];
        }
    }
}
