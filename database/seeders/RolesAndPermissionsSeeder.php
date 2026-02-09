<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
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
            // Patient permissions
            ['name' => 'view_patient', 'group' => 'patient'],
            ['name' => 'create_patient', 'group' => 'patient'],
            ['name' => 'edit_patient', 'group' => 'patient'],
            ['name' => 'delete_patient', 'group' => 'patient'],

            // Visit permissions
            ['name' => 'view_visit', 'group' => 'visit'],
            ['name' => 'create_visit', 'group' => 'visit'],
            ['name' => 'edit_visit', 'group' => 'visit'],
            ['name' => 'delete_visit', 'group' => 'visit'],

            // Medical record permissions
            ['name' => 'view_medical_record', 'group' => 'medical_record'],
            ['name' => 'create_medical_record', 'group' => 'medical_record'],
            ['name' => 'edit_medical_record', 'group' => 'medical_record'],

            // Prescription permissions
            ['name' => 'view_prescription', 'group' => 'prescription'],
            ['name' => 'create_prescription', 'group' => 'prescription'],
            ['name' => 'edit_prescription', 'group' => 'prescription'],

            // Medicine permissions
            ['name' => 'view_medicine', 'group' => 'medicine'],
            ['name' => 'create_medicine', 'group' => 'medicine'],
            ['name' => 'edit_medicine', 'group' => 'medicine'],
            ['name' => 'delete_medicine', 'group' => 'medicine'],

            // Employee permissions
            ['name' => 'view_employee', 'group' => 'employee'],
            ['name' => 'create_employee', 'group' => 'employee'],
            ['name' => 'edit_employee', 'group' => 'employee'],
            ['name' => 'delete_employee', 'group' => 'employee'],

            // User management permissions
            ['name' => 'view_user', 'group' => 'user'],
            ['name' => 'create_user', 'group' => 'user'],
            ['name' => 'edit_user', 'group' => 'user'],
            ['name' => 'delete_user', 'group' => 'user'],

            // Role management permissions
            ['name' => 'view_role', 'group' => 'role'],
            ['name' => 'create_role', 'group' => 'role'],
            ['name' => 'edit_role', 'group' => 'role'],
            ['name' => 'delete_role', 'group' => 'role'],

            // Report permissions
            ['name' => 'view_reports', 'group' => 'reports'],
            ['name' => 'export_reports', 'group' => 'reports'],

            // Billing permissions
            ['name' => 'view_invoice', 'group' => 'billing'],
            ['name' => 'create_invoice', 'group' => 'billing'],
            ['name' => 'edit_invoice', 'group' => 'billing'],
            ['name' => 'process_payment', 'group' => 'billing'],

            // Settings permissions
            ['name' => 'view_settings', 'group' => 'settings'],
            ['name' => 'edit_settings', 'group' => 'settings'],

            // Audit permissions
            ['name' => 'view_audit_logs', 'group' => 'audit'],

            // Backup permissions
            ['name' => 'manage_backups', 'group' => 'backup'],

            // BPJS permissions
            ['name' => 'access_bpjs', 'group' => 'bpjs'],

            // Satu Sehat permissions
            ['name' => 'access_satu_sehat', 'group' => 'satu_sehat'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        // Create roles and assign permissions
        $roles = [
            'super_admin' => Permission::all()->pluck('name')->toArray(),
            'admin' => [
                'view_patient', 'create_patient', 'edit_patient',
                'view_visit', 'create_visit', 'edit_visit',
                'view_medical_record', 'create_medical_record', 'edit_medical_record',
                'view_prescription', 'create_prescription', 'edit_prescription',
                'view_medicine', 'create_medicine', 'edit_medicine',
                'view_employee', 'create_employee', 'edit_employee',
                'view_user', 'create_user', 'edit_user',
                'view_role',
                'view_reports', 'export_reports',
                'view_invoice', 'create_invoice', 'edit_invoice', 'process_payment',
                'view_settings', 'edit_settings',
                'view_audit_logs',
                'manage_backups',
                'access_bpjs',
                'access_satu_sehat',
            ],
            'dokter' => [
                'view_patient', 'create_patient', 'edit_patient',
                'view_visit', 'create_visit', 'edit_visit',
                'view_medical_record', 'create_medical_record', 'edit_medical_record',
                'view_prescription', 'create_prescription', 'edit_prescription',
                'access_bpjs',
                'access_satu_sehat',
            ],
            'perawat' => [
                'view_patient',
                'view_visit', 'create_visit', 'edit_visit',
                'view_medical_record', 'create_medical_record',
                'view_prescription',
            ],
            'apoteker' => [
                'view_patient',
                'view_prescription', 'edit_prescription',
                'view_medicine', 'create_medicine', 'edit_medicine',
            ],
            'kasir' => [
                'view_patient',
                'view_visit',
                'view_invoice', 'create_invoice', 'edit_invoice', 'process_payment',
            ],
            'front_office' => [
                'view_patient', 'create_patient', 'edit_patient',
                'view_visit', 'create_visit', 'edit_visit',
                'access_bpjs',
            ],
            'laboratorium' => [
                'view_patient',
                'view_visit',
                'view_medical_record',
            ],
            'radiologi' => [
                'view_patient',
                'view_visit',
                'view_medical_record',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
