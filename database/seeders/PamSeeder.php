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
                'name' => 'Sumber Waras Tuban Kulon',
                'code' => 'SWTBK',
                'phone' => '021-5555-0001',
                'address' => 'Tuban Kulon, Kec. Tuban, Kabupaten Tuban, Jawa Timur 62315',
                'is_active' => true,
                'coordinate' => json_encode(['lat' => -6.2088, 'lng' => 106.8456]),
            ],
            [
                'name' => 'Sri Rejeki',
                'code' => 'SR',
                'phone' => '021-5555-0002',
                'address' => 'Tuban Kidul, Kec. Tuban, Kabupaten Tuban, Jawa Timur 62315',
                'is_active' => true,
                'coordinate' => json_encode(['lat' => -6.2297, 'lng' => 106.8185]),
            ],
            [
                'name' => 'Air Minum Legi',
                'code' => 'AML',
                'phone' => '021-5555-0003',
                'address' => 'Jl. Merdeka No. 90, Jakarta Barat, DKI Jakarta 11610',
                'is_active' => true,
                'coordinate' => json_encode(['lat' => -6.1889, 'lng' => 106.7378]),
            ],
        ];

        foreach ($pams as $pamData) {
            Pam::create($pamData);
        }

        $this->command->info('PAM seeder completed successfully. Created ' . count($pams) . ' PAMs.');
    }
}
