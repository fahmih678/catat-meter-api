<?php

namespace Database\Seeders;

use App\Models\Pam;
use Illuminate\Database\Seeder;

class PamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pams = [
            [
                'name' => 'PAM Jakarta Pusat',
                'code' => 'PAMJAKPUR',
                'phone' => '021-5555-0001',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10110',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.2088, 'lng' => 106.8456]),
            ],
            [
                'name' => 'PAM Jakarta Selatan',
                'code' => 'PAMJAKSEL',
                'phone' => '021-5555-0002',
                'address' => 'Jl. Senopati No. 45, Jakarta Selatan, DKI Jakarta 12110',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.2297, 'lng' => 106.8185]),
            ],
            [
                'name' => 'PAM Jakarta Barat',
                'code' => 'PAMJAKBAR',
                'phone' => '021-5555-0003',
                'address' => 'Jl. Puri Indah No. 78, Jakarta Barat, DKI Jakarta 11610',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.1889, 'lng' => 106.7378]),
            ],
            [
                'name' => 'PAM Jakarta Timur',
                'code' => 'PAMJAKTIM',
                'phone' => '021-5555-0004',
                'address' => 'Jl. Bekasi Raya No. 234, Jakarta Timur, DKI Jakarta 13220',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.2146, 'lng' => 106.9206]),
            ],
            [
                'name' => 'PAM Jakarta Utara',
                'code' => 'PAMJAKUT',
                'phone' => '021-5555-0005',
                'address' => 'Jl. Sunter Garden No. 567, Jakarta Utara, DKI Jakarta 14350',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.1388, 'lng' => 106.8650]),
            ],
            [
                'name' => 'PAM Tangerang',
                'code' => 'PAMTGR',
                'phone' => '021-5555-0006',
                'address' => 'Jl. Sudirman No. 89, Tangerang, Banten 15111',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.1783, 'lng' => 106.6319]),
            ],
            [
                'name' => 'PAM Bekasi',
                'code' => 'PAMBKS',
                'phone' => '021-5555-0007',
                'address' => 'Jl. Ahmad Yani No. 321, Bekasi, Jawa Barat 17142',
                'status' => 'active',
                'coordinate' => json_encode(['lat' => -6.2383, 'lng' => 106.9756]),
            ],
            [
                'name' => 'PAM Depok',
                'code' => 'PAMDPK',
                'phone' => '021-5555-0008',
                'address' => 'Jl. Margonda Raya No. 654, Depok, Jawa Barat 16424',
                'status' => 'inactive',
                'coordinate' => json_encode(['lat' => -6.4025, 'lng' => 106.7942]),
            ]
        ];

        foreach ($pams as $pamData) {
            Pam::create($pamData);
        }

        $this->command->info('PAM seeder completed successfully. Created ' . count($pams) . ' PAMs.');
    }
}
