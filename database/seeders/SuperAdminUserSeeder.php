<?php


namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create SuperAdmin
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'pam_id' => null, // SuperAdmin not tied to specific PAM
        ]);
        $superAdmin->assignRole('superadmin');
        $this->command->info('SuperAdmin created: superadmin@example.com');
    }
}
