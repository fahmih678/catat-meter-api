<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pams = Pam::all();

        // Create Admin PAM for each PAM
        foreach ($pams as $index => $pam) {
            $adminPam = User::create([
                'name' => "Admin {$pam->name}",
                'email' => "admin.{$pam->code}@example.com",
                'password' => Hash::make('password'),
                'phone' => '0812345678' . sprintf('%02d', $index + 10),
                'pam_id' => $pam->id,
            ]);
            $adminPam->assignRole('admin');
            $this->command->info("Admin PAM created: admin.{$pam->code}@example.com");

            // Create 2-3 Catat Meter users per PAM
            for ($i = 1; $i <= 2; $i++) {
                $catatMeter = User::create([
                    'name' => "Petugas Catat Meter {$i} - {$pam->name}",
                    'email' => "catat{$i}.{$pam->code}@example.com",
                    'password' => Hash::make('password'),
                    'phone' => '0813456789' . sprintf('%02d', ($index * 10) + $i),
                    'pam_id' => $pam->id,
                ]);
                $catatMeter->assignRole('catat_meter');
                $this->command->info("Catat Meter created: catat{$i}.{$pam->code}@example.com");
            }

            // Create 1 Pembayaran user per PAM
            $pembayaran = User::create([
                'name' => "Petugas Pembayaran - {$pam->name}",
                'email' => "loket.{$pam->code}@example.com",
                'password' => Hash::make('password'),
                'phone' => '0814567890' . sprintf('%02d', $index + 20),
                'pam_id' => $pam->id,
            ]);
            $pembayaran->assignRole('loket');
            $this->command->info("Pembayaran created: bayar.{$pam->code}@example.com");

            $customer = User::create([
                'name' => "customer",
                'email' => "customer@example.com",
                'password' => Hash::make('password'),
                'phone' => '',
                'pam_id' => $pam->id,
            ]);
            $customer->assignRole('customer');
            $this->command->info("Customer created: customer@example.com");
        }

        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('SuperAdmin: superadmin@example.com / password');
        $this->command->info('Admin PAM: admin.{PAM_CODE}@example.com / password');
        $this->command->info('Catat Meter: catat1.{PAM_CODE}@example.com / password');
        $this->command->info('Pembayaran: bayar.{PAM_CODE}@example.com / password');
        $this->command->info('');
        $this->command->info('Example: admin.PAMJAKPUR@example.com');
    }
}
