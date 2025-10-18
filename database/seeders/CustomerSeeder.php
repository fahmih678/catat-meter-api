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
        $customers = [
            [1, 'Bp Sarbini', 'RT1', 'rumah tangga'],
            [2, 'Ibu Nanik', 'RT1', 'rumah tangga'],
            [3, 'Bp Agus', 'RT1', 'rumah tangga'],
            [4, 'Bp Sri Waluyo', 'RT1', 'rumah tangga'],
            [5, 'Bp Aziz', 'RT1', 'rumah tangga'],
            [6, 'Bp Yuli', 'RT1', 'rumah tangga'],
            [7, 'Bp Parminto', 'RT1', 'rumah tangga'],
            [8, 'Bp Wiyono', 'RT1', 'rumah tangga'],
            [9, 'Ibu Endang', 'RT1', 'rumah tangga'],
            [10, 'Bp Rohmadi', 'RT1', 'rumah tangga'],
            [11, 'Bp Dodi', 'RT1', 'rumah tangga'],
            [12, 'Bp Sutadi', 'RT1', 'rumah tangga'],
            [13, 'Ibu Lina', 'RT1', 'rumah tangga'],
            [14, 'Bp Suripno', 'RT1', 'rumah tangga'],
            [15, 'Ibu Syamsiyah', 'RT1', 'rumah tangga'],
            [16, 'Bp Darmaji', 'RT1', 'rumah tangga'],
            [17, 'Bp Aris', 'RT1', 'rumah tangga'],
            [18, 'Bp Sutrisno', 'RT1', 'rumah tangga'],
            [19, 'Bp Adi Makali', 'RT1', 'rumah tangga'],
            [20, 'Bp Eko Riyanto', 'RT1', 'rumah tangga'],
            [21, 'Bp Darsono', 'RT1', 'rumah tangga'],
            [22, 'Bp Munawir', 'RT2', 'rumah tangga'],
            [23, 'Bp Riswanto', 'RT2', 'rumah tangga'],
            [24, 'Bp Supriyadi', 'RT2', 'rumah tangga'],
            [25, 'Bp Zaenal A', 'RT2', 'rumah tangga'],
            [26, 'Bp Marsudi', 'RT2', 'rumah tangga'],
            [27, 'Bp Darmadi', 'RT2', 'rumah tangga'],
            [28, 'Bp Agus TL', 'RT2', 'rumah tangga'],
            [29, 'Bp Winarto', 'RT2', 'rumah tangga'],
            [30, 'Bp Giyat', 'RT2', 'rumah tangga'],
            [31, 'Bp Andi', 'RT2', 'rumah tangga'],
            [32, 'Ibu Ruqoyah', 'RT2', 'rumah tangga'],
            [33, 'Bp Sartono', 'RT2', 'rumah tangga'],
            [34, 'Ibu Sarmini', 'RT2', 'rumah tangga'],
            [35, 'Bp Zuliedi', 'RT2', 'rumah tangga'],
            [36, 'Bp Jatmiko', 'RT2', 'rumah tangga'],
            [37, 'Bp Khoirul', 'RT2', 'rumah tangga'],
            [38, 'Bp Gatot', 'RT2', 'rumah tangga'],
            [39, 'Bp Nur Edi', 'RT2', 'rumah tangga'],
            [40, 'Bp Wawan', 'RT2', 'rumah tangga'],
            [41, 'Bp Syaiful', 'RT2', 'rumah tangga'],
            [42, 'MDI Permata Bunda', 'RT2', 'rumah tangga'],
            [43, 'Ibu Darsih', 'RT2', 'rumah tangga'],
            [44, 'Bp Nur Sahid', 'RT2', 'rumah tangga'],
            [45, 'Bp Rian', 'RT2', 'rumah tangga'],
            [46, 'Bp Nur Fathoni', 'RT2', 'rumah tangga'],
            [47, 'Bp Rois', 'RT2', 'rumah tangga'],
            [48, 'Ibu Eni', 'RT2', 'rumah tangga'],
            [49, 'Bp Ali Ihsan', 'RT2', 'rumah tangga'],
            [50, 'Bp Haryanto', 'RT2', 'rumah tangga'],
            [51, 'Bp Sumardi', 'RT2', 'rumah tangga'],
            [52, 'Bp Dodi 2', 'RT2', 'rumah tangga'],
            [53, 'Bp Abdurrohman Ali', 'RT2', 'rumah tangga'],
            [54, 'Bp Ali Waluyo', 'RT2', 'rumah tangga'],
            [55, 'Bp Bayu', 'RT2', 'rumah tangga'],
            [56, 'Bp Mahmudi', 'RT2', 'rumah tangga'],
            [57, 'Bp Nurul Huda 2', 'RT2', 'rumah tangga'],
            [58, 'Bp Nur Rohmad', 'RT2', 'rumah tangga'],
            [59, 'Bp Ismail', 'RT2', 'rumah tangga'],
            [60, 'Bp Mukhtarhadi', 'RT2', 'rumah tangga'],
            [61, 'Bp Adbdul Azis', 'RT2', 'rumah tangga'],
            [62, 'Bp Zainudin H', 'RT2', 'rumah tangga'],
            [63, 'Bp Hendrat', 'RT2', 'rumah tangga'],
            [64, 'Bp Setiawan H', 'RT2', 'rumah tangga'],
            [65, 'Bp Tarno', 'RT2', 'rumah tangga'],
            [66, 'Bp Sugi', 'RT2', 'rumah tangga'],
            [67, 'Ibu Astrid', 'RT2', 'rumah tangga'],
            [68, 'Bp Nurul Huda 1', 'RT2', 'rumah tangga'],
            [69, 'Bp Arul', 'RT2', 'rumah tangga'],
            [70, 'Bp Harjanto', 'RT2', 'rumah tangga'],
            [71, 'Bp Wagiman', 'RT2', 'rumah tangga'],
            [72, 'Bp Slamet', 'RT2', 'rumah tangga'],
            [73, 'Bp Herjan', 'RT2', 'rumah tangga'],
            [74, 'Masjid At-Thohiriyah', 'RT2', 'sosial'],
            [75, 'Ibu Umi', 'RT2', 'rumah tangga'],
            [76, 'Bp Kasmun', 'RT2', 'rumah tangga'],
            [77, 'Bp Kasto', 'RT2', 'rumah tangga'],
            [78, 'Bp Salamun', 'RT2', 'rumah tangga'],
            [79, 'Bp Prastowo', 'RT2', 'rumah tangga'],
            [80, 'Bp Darmono', 'RT2', 'rumah tangga'],
            [81, 'Bp Songep', 'RT2', 'rumah tangga'],
            [82, 'Bp Sapto', 'RT2', 'rumah tangga'],
            [83, 'Bp Ari', 'RT2', 'rumah tangga'],
            [84, 'Bp Agung', 'RT2', 'rumah tangga'],
            [85, 'Bp Djali', 'RT2', 'rumah tangga'],
            [86, 'Bp Muh Nurdin', 'RT2', 'rumah tangga'],
            [87, 'Bp Darmanto', 'RT2', 'rumah tangga'],
            [88, 'Bp Sudarno', 'RT2', 'rumah tangga'],
            [89, 'Bp Zaenudin', 'RT2', 'rumah tangga'],
            [90, 'Bp Muqorobin', 'RT2', 'rumah tangga'],
            [91, 'Ibu Dwi', 'RT3', 'rumah tangga'],
            [92, 'Ibu Muryani', 'RT3', 'rumah tangga'],
            [93, 'Bp Parmanto', 'RT3', 'rumah tangga'],
            [94, 'BP Slamet', 'RT3', 'rumah tangga'],
            [95, 'Bp Anis', 'RT3', 'rumah tangga'],
            [96, 'Bp Mukhlis', 'RT3', 'rumah tangga'],
            [97, 'Bp Sumardi', 'RT3', 'rumah tangga'],
            [98, 'SDN Tuban 3', 'RT3', 'sosial'],
            [99, 'Bp Amin', 'RT3', 'rumah tangga'],
            [100, 'Bp Toni', 'RT3', 'rumah tangga'],
            [101, 'Bp Giyono', 'RT3', 'rumah tangga'],
            [102, 'Ibu Supartinah', 'RT3', 'rumah tangga'],
            [103, 'Bp Sri Widodo', 'RT3', 'rumah tangga'],
            [104, 'Bp Nur Fathoni 3', 'RT3', 'rumah tangga'],
            [105, 'Bp Nur Widayat', 'RT3', 'rumah tangga'],
            [106, 'Bp Ahmadi', 'RT3', 'rumah tangga'],
            [107, 'Bp Sugi', 'RT3', 'rumah tangga'],
            [108, 'Bp Alen', 'RT3', 'rumah tangga'],
            [109, 'Bp Nurul Huda', 'RT3', 'rumah tangga'],
            [110, 'Bp Fahrudin', 'RT3', 'rumah tangga'],
            [111, 'Bp Wakimin', 'RT3', 'rumah tangga'],
            [112, 'Bp Awan', 'RT3', 'rumah tangga'],
            [113, 'Bp Ngaliman', 'RT3', 'rumah tangga'],
            [114, 'Bp Tukimin', 'RT3', 'rumah tangga'],
            [115, 'Bp Irfan', 'RT3', 'rumah tangga'],
            [116, 'Bp Hargoriyadi', 'RT3', 'rumah tangga'],
            [117, 'Ibu Marni', 'RT3', 'rumah tangga'],
            [118, 'Bp Agung', 'RT3', 'rumah tangga'],
            [119, 'Bp Hendratno', 'RT3', 'rumah tangga'],
            [120, 'Bp Fahrudin 1', 'RT3', 'rumah tangga'],
            [121, 'Ayam', 'RT1', 'rumah tangga'],
            [122, 'Bp Ahmadi', 'RT1', 'rumah tangga'],
            [123, 'Ibu Hadi', 'RT1', 'rumah tangga'],
            [124, 'Bp Sumardi 2', 'RT2', 'rumah tangga'],
            [125, 'Bp Siswanto', 'RT3', 'rumah tangga'],
        ];

        // Ambil daftar tarif dan area
        $tariffGroup = TariffGroup::pluck('id', 'name')->toArray(); // ✅ pluck sudah key => value
        $areaList = Area::pluck('id', 'code')->toArray();            // ✅ pluck sudah key => value

        $this->command->info(print_r($areaList, true)); // tampilkan di console (bukan object)

        foreach ($customers as [$number, $name, $area, $group]) {
            Customer::create([
                'pam_id' => 1,
                'customer_number' => $number,
                'name' => $name,
                'area_id' => $areaList[$area],           // ✅ gunakan null-safe agar tidak error jika RT tidak ditemukan
                'tariff_group_id' => $tariffGroup[$group],
                'address' => 'Jl. Contoh Alamat No. ' . $number,
                'phone' => '0812-3456-78',
                'is_active' => true,
            ]);
        }
    }
}
