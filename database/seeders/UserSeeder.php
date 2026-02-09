<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rumahsakitku.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'employee_id' => null,
                'is_active' => true,
                'last_login_at' => null,
                'last_login_ip' => null,
            ]
        );

        $admin->assignRole('super_admin');

        $demoUsers = [
            [
                'name' => 'Petugas Pendaftaran',
                'email' => 'pendaftaran@rumahsakitku.test',
                'role' => 'pendaftaran',
            ],
            [
                'name' => 'Dokter Umum',
                'email' => 'dokter.umum@rumahsakitku.test',
                'role' => 'dokter_umum',
            ],
            [
                'name' => 'Dokter Spesialis',
                'email' => 'dokter.spesialis@rumahsakitku.test',
                'role' => 'dokter_spesialis',
            ],
            [
                'name' => 'Perawat',
                'email' => 'perawat@rumahsakitku.test',
                'role' => 'perawat',
            ],
            [
                'name' => 'Kasir',
                'email' => 'kasir@rumahsakitku.test',
                'role' => 'kasir',
            ],
            [
                'name' => 'Petugas Farmasi',
                'email' => 'farmasi@rumahsakitku.test',
                'role' => 'farmasi',
            ],
            [
                'name' => 'Petugas Laboratorium',
                'email' => 'laboratorium@rumahsakitku.test',
                'role' => 'laboratorium',
            ],
            [
                'name' => 'Manajemen',
                'email' => 'manajemen@rumahsakitku.test',
                'role' => 'manajemen',
            ],
        ];

        foreach ($demoUsers as $demoUser) {
            $user = User::firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'employee_id' => null,
                    'is_active' => true,
                    'last_login_at' => null,
                    'last_login_ip' => null,
                ]
            );

            $user->assignRole($demoUser['role']);
        }
    }
}
