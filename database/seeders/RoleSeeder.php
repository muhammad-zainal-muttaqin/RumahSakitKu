<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Patient permissions
            'patients.view',
            'patients.create',
            'patients.edit',
            'patients.delete',

            // Visit permissions
            'visits.view',
            'visits.create',
            'visits.edit',
            'visits.delete',

            // Medical record permissions
            'medical-records.view',
            'medical-records.create',
            'medical-records.edit',
            'medical-records.delete',

            // Prescription permissions
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.edit',
            'prescriptions.delete',

            // Pharmacy permissions
            'pharmacy.view',
            'pharmacy.dispense',
            'pharmacy.manage',

            // Laboratory permissions
            'laboratory.view',
            'laboratory.create',
            'laboratory.edit',
            'laboratory.verify',

            // Billing permissions
            'billing.view',
            'billing.create',
            'billing.edit',
            'billing.pay',

            // BPJS permissions
            'bpjs.view',
            'bpjs.verify',
            'bpjs.sep',
            'bpjs.referral',

            // Master data permissions
            'master-data.view',
            'master-data.manage',

            // User management permissions
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Reports permissions
            'reports.view',
            'reports.export',

            // Settings permissions
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,
            'pendaftaran' => [
                'patients.view',
                'patients.create',
                'patients.edit',
                'visits.view',
                'visits.create',
                'visits.edit',
                'bpjs.view',
                'bpjs.verify',
                'bpjs.sep',
                'bpjs.referral',
            ],
            'dokter_umum' => [
                'patients.view',
                'visits.view',
                'medical-records.view',
                'medical-records.create',
                'medical-records.edit',
                'prescriptions.view',
                'prescriptions.create',
                'prescriptions.edit',
                'laboratory.view',
                'laboratory.create',
            ],
            'dokter_spesialis' => [
                'patients.view',
                'visits.view',
                'medical-records.view',
                'medical-records.create',
                'medical-records.edit',
                'prescriptions.view',
                'prescriptions.create',
                'prescriptions.edit',
                'laboratory.view',
                'laboratory.create',
            ],
            'perawat' => [
                'patients.view',
                'visits.view',
                'medical-records.view',
                'medical-records.edit',
            ],
            'kasir' => [
                'patients.view',
                'visits.view',
                'billing.view',
                'billing.create',
                'billing.edit',
                'billing.pay',
                'bpjs.view',
            ],
            'farmasi' => [
                'patients.view',
                'prescriptions.view',
                'pharmacy.view',
                'pharmacy.dispense',
                'pharmacy.manage',
            ],
            'laboratorium' => [
                'patients.view',
                'laboratory.view',
                'laboratory.create',
                'laboratory.edit',
                'laboratory.verify',
            ],
            'manajemen' => [
                'patients.view',
                'visits.view',
                'medical-records.view',
                'prescriptions.view',
                'pharmacy.view',
                'laboratory.view',
                'billing.view',
                'bpjs.view',
                'master-data.view',
                'master-data.manage',
                'users.view',
                'users.create',
                'users.edit',
                'reports.view',
                'reports.export',
                'settings.view',
                'settings.edit',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
