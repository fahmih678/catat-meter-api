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
            case 'Rumah Tangga Kecil':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 10.00,
                        'amount' => 1500.00,
                        'description' => 'Blok I: Pemakaian 0-10 m³ (Tarif sosial)'
                    ],
                    [
                        'meter_min' => 10.01,
                        'meter_max' => 20.00,
                        'amount' => 2500.00,
                        'description' => 'Blok II: Pemakaian 11-20 m³'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 999999.99,
                        'amount' => 3500.00,
                        'description' => 'Blok III: Pemakaian >20 m³'
                    ]
                ];

            case 'Rumah Tangga Sedang':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 10.00,
                        'amount' => 2000.00,
                        'description' => 'Blok I: Pemakaian 0-10 m³'
                    ],
                    [
                        'meter_min' => 10.01,
                        'meter_max' => 20.00,
                        'amount' => 3000.00,
                        'description' => 'Blok II: Pemakaian 11-20 m³'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 30.00,
                        'amount' => 4000.00,
                        'description' => 'Blok III: Pemakaian 21-30 m³'
                    ],
                    [
                        'meter_min' => 30.01,
                        'meter_max' => 999999.99,
                        'amount' => 5000.00,
                        'description' => 'Blok IV: Pemakaian >30 m³'
                    ]
                ];

            case 'Rumah Tangga Besar':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 10.00,
                        'amount' => 2500.00,
                        'description' => 'Blok I: Pemakaian 0-10 m³'
                    ],
                    [
                        'meter_min' => 10.01,
                        'meter_max' => 20.00,
                        'amount' => 3500.00,
                        'description' => 'Blok II: Pemakaian 11-20 m³'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 30.00,
                        'amount' => 4500.00,
                        'description' => 'Blok III: Pemakaian 21-30 m³'
                    ],
                    [
                        'meter_min' => 30.01,
                        'meter_max' => 50.00,
                        'amount' => 5500.00,
                        'description' => 'Blok IV: Pemakaian 31-50 m³'
                    ],
                    [
                        'meter_min' => 50.01,
                        'meter_max' => 999999.99,
                        'amount' => 6500.00,
                        'description' => 'Blok V: Pemakaian >50 m³'
                    ]
                ];

            case 'Niaga Kecil':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 20.00,
                        'amount' => 4000.00,
                        'description' => 'Blok I: Pemakaian 0-20 m³'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 50.00,
                        'amount' => 5500.00,
                        'description' => 'Blok II: Pemakaian 21-50 m³'
                    ],
                    [
                        'meter_min' => 50.01,
                        'meter_max' => 999999.99,
                        'amount' => 7000.00,
                        'description' => 'Blok III: Pemakaian >50 m³'
                    ]
                ];

            case 'Niaga Menengah':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 30.00,
                        'amount' => 5000.00,
                        'description' => 'Blok I: Pemakaian 0-30 m³'
                    ],
                    [
                        'meter_min' => 30.01,
                        'meter_max' => 100.00,
                        'amount' => 7000.00,
                        'description' => 'Blok II: Pemakaian 31-100 m³'
                    ],
                    [
                        'meter_min' => 100.01,
                        'meter_max' => 999999.99,
                        'amount' => 9000.00,
                        'description' => 'Blok III: Pemakaian >100 m³'
                    ]
                ];

            case 'Niaga Besar':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 50.00,
                        'amount' => 6000.00,
                        'description' => 'Blok I: Pemakaian 0-50 m³'
                    ],
                    [
                        'meter_min' => 50.01,
                        'meter_max' => 200.00,
                        'amount' => 8500.00,
                        'description' => 'Blok II: Pemakaian 51-200 m³'
                    ],
                    [
                        'meter_min' => 200.01,
                        'meter_max' => 999999.99,
                        'amount' => 11000.00,
                        'description' => 'Blok III: Pemakaian >200 m³'
                    ]
                ];

            case 'Industri':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 100.00,
                        'amount' => 7500.00,
                        'description' => 'Blok I: Pemakaian 0-100 m³'
                    ],
                    [
                        'meter_min' => 100.01,
                        'meter_max' => 500.00,
                        'amount' => 10000.00,
                        'description' => 'Blok II: Pemakaian 101-500 m³'
                    ],
                    [
                        'meter_min' => 500.01,
                        'meter_max' => 999999.99,
                        'amount' => 12500.00,
                        'description' => 'Blok III: Pemakaian >500 m³'
                    ]
                ];

            case 'Sosial':
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 20.00,
                        'amount' => 1000.00,
                        'description' => 'Blok I: Pemakaian 0-20 m³ (Tarif sosial)'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 50.00,
                        'amount' => 2000.00,
                        'description' => 'Blok II: Pemakaian 21-50 m³'
                    ],
                    [
                        'meter_min' => 50.01,
                        'meter_max' => 999999.99,
                        'amount' => 3000.00,
                        'description' => 'Blok III: Pemakaian >50 m³'
                    ]
                ];

            default:
                // Default tariff structure
                return [
                    [
                        'meter_min' => 0.00,
                        'meter_max' => 20.00,
                        'amount' => 2500.00,
                        'description' => 'Blok I: Pemakaian 0-20 m³'
                    ],
                    [
                        'meter_min' => 20.01,
                        'meter_max' => 999999.99,
                        'amount' => 4000.00,
                        'description' => 'Blok II: Pemakaian >20 m³'
                    ]
                ];
        }
    }
}
