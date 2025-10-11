<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');

        // Create default user first
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'pam_id' => null, // Will be updated after PAMs are created
            'phone' => '081234567890',
        ]);
        $this->command->info('✅ Default user created');

        // Run seeders in correct order (respecting foreign key constraints)
        $this->call([
            PamSeeder::class,           // 1. PAMs first (no dependencies)
            AreaSeeder::class,          // 2. Areas (depends on PAMs)
            TariffGroupSeeder::class,   // 3. Tariff Groups (depends on PAMs)
            TariffTierSeeder::class,    // 4. Tariff Tiers (depends on PAMs and Tariff Groups)
            FixedFeeSeeder::class,      // 5. Fixed Fees (depends on PAMs and Tariff Groups)
            CustomerSeeder::class,      // 6. Customers (depends on PAMs, Areas, Tariff Groups)
            MeterSeeder::class,         // 7. Meters (depends on Customers)
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('- PAMs: Multiple regional water companies');
        $this->command->info('- Areas: 5 zones per PAM (Elite, Medium, Dense, Industrial, Commercial)');
        $this->command->info('- Tariff Groups: 8 categories per PAM (Household & Commercial)');
        $this->command->info('- Tariff Tiers: 3-5 progressive blocks per tariff group');
        $this->command->info('- Fixed Fees: 3 types per tariff group (Beban, Admin, Meteran)');
        $this->command->info('- Customers: 15-25 customers per PAM with realistic Indonesian data');
        $this->command->info('- Meters: 85% of active customers have meters installed');
        $this->command->info('');
        $this->command->info('🔗 You can now test all API endpoints with realistic data!');
    }
}
