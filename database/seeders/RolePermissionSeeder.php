<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // PAM Management
            'pam.view',
            'pam.create',
            'pam.edit',
            'pam.delete',
            'pam.restore',
            'pam.statistics',

            // User Management
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'user.assign-roles',

            // Customer Management
            'customer.view',
            'customer.create',
            'customer.edit',
            'customer.delete',
            'customer.restore',
            'customer.transfer',

            // Meter Management
            'meter.view',
            'meter.create',
            'meter.edit',
            'meter.delete',
            'meter.activate',
            'meter.deactivate',

            // Meter Reading
            'meter-record.view',
            'meter-record.create',
            'meter-record.edit',
            'meter-record.delete',
            'meter-record.approve',
            'meter-record.bulk-create',

            // Billing
            'bill.view',
            'bill.create',
            'bill.edit',
            'bill.delete',
            'bill.generate',
            'bill.mark-paid',

            // Reporting
            'report.view',
            'report.generate',
            'report.export',

            // System
            'system.health',
            'system.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $this->createSuperAdminRole();
        $this->createAdminPamRole();
        $this->createCatatMeterRole();
        $this->createPembayaranRole();

        $this->command->info('Roles and permissions created successfully.');
    }

    private function createSuperAdminRole(): void
    {
        $role = Role::create(['name' => 'superadmin']);

        // SuperAdmin gets all permissions
        $role->givePermissionTo(Permission::all());

        $this->command->info('SuperAdmin role created with all permissions.');
    }

    private function createAdminPamRole(): void
    {
        $role = Role::create(['name' => 'admin_pam']);

        // Admin PAM permissions - full access to their PAM's data
        $permissions = [
            // PAM Management (limited)
            'pam.view',
            'pam.edit',
            'pam.statistics',

            // User Management (for their PAM)
            'user.view',
            'user.create',
            'user.edit',
            'user.assign-roles',

            // Customer Management (full)
            'customer.view',
            'customer.create',
            'customer.edit',
            'customer.delete',
            'customer.restore',
            'customer.transfer',

            // Meter Management (full)
            'meter.view',
            'meter.create',
            'meter.edit',
            'meter.delete',
            'meter.activate',
            'meter.deactivate',

            // Meter Reading (view and approve)
            'meter-record.view',
            'meter-record.edit',
            'meter-record.approve',

            // Billing (full)
            'bill.view',
            'bill.create',
            'bill.edit',
            'bill.generate',
            'bill.mark-paid',

            // Reporting (full)
            'report.view',
            'report.generate',
            'report.export',

            // System (limited)
            'system.health',
        ];

        $role->givePermissionTo($permissions);

        $this->command->info('Admin PAM role created with management permissions.');
    }

    private function createCatatMeterRole(): void
    {
        $role = Role::create(['name' => 'catat_meter']);

        // Catat Meter permissions - focused on meter reading
        $permissions = [
            // Customer (view only)
            'customer.view',

            // Meter Management (view and basic edit)
            'meter.view',
            'meter.edit',

            // Meter Reading (full access)
            'meter-record.view',
            'meter-record.create',
            'meter-record.edit',
            'meter-record.bulk-create',

            // Basic reporting
            'report.view',

            // System health
            'system.health',
        ];

        $role->givePermissionTo($permissions);

        $this->command->info('Catat Meter role created with meter reading permissions.');
    }

    private function createPembayaranRole(): void
    {
        $role = Role::create(['name' => 'pembayaran']);

        // Pembayaran permissions - focused on billing and payments
        $permissions = [
            // Customer (view only)
            'customer.view',

            // Meter (view only)
            'meter.view',

            // Meter Records (view only)
            'meter-record.view',

            // Billing (focused on payments)
            'bill.view',
            'bill.edit',
            'bill.mark-paid',

            // Basic reporting
            'report.view',

            // System health
            'system.health',
        ];

        $role->givePermissionTo($permissions);

        $this->command->info('Pembayaran role created with billing permissions.');
    }
}
