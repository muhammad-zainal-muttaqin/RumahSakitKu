<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PolyclinicSeeder extends Seeder
{
    public function run(): void
    {
        $polikliniks = [
            [
                'code' => 'POL-001',
                'name' => 'Poliklinik Umum',
                'bpjs_code' => '001',
                'description' => 'Layanan pemeriksaan umum untuk pasien dewasa',
                'is_active' => true,
            ],
            [
                'code' => 'POL-002',
                'name' => 'Poliklinik Anak',
                'bpjs_code' => '002',
                'description' => 'Layanan kesehatan khusus untuk anak-anak',
                'is_active' => true,
            ],
            [
                'code' => 'POL-003',
                'name' => 'Poliklinik Penyakit Dalam',
                'bpjs_code' => '003',
                'description' => 'Spesialis penyakit dalam dan metabolik',
                'is_active' => true,
            ],
            [
                'code' => 'POL-004',
                'name' => 'Poliklinik Bedah',
                'bpjs_code' => '004',
                'description' => 'Spesialis bedah umum',
                'is_active' => true,
            ],
            [
                'code' => 'POL-005',
                'name' => 'Poliklinik Bedah Orthopedi',
                'bpjs_code' => '005',
                'description' => 'Spesialis tulang dan sendi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-006',
                'name' => 'Poliklinik Syaraf',
                'bpjs_code' => '006',
                'description' => 'Spesialis neurologi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-007',
                'name' => 'Poliklinik Jantung',
                'bpjs_code' => '007',
                'description' => 'Spesialis kardiologi dan pembuluh darah',
                'is_active' => true,
            ],
            [
                'code' => 'POL-008',
                'name' => 'Poliklinik Paru',
                'bpjs_code' => '008',
                'description' => 'Spesialis penyakit paru dan pernapasan',
                'is_active' => true,
            ],
            [
                'code' => 'POL-009',
                'name' => 'Poliklinik THT',
                'bpjs_code' => '009',
                'description' => 'Spesialis telinga, hidung, dan tenggorokan',
                'is_active' => true,
            ],
            [
                'code' => 'POL-010',
                'name' => 'Poliklinik Mata',
                'bpjs_code' => '010',
                'description' => 'Spesialis mata',
                'is_active' => true,
            ],
            [
                'code' => 'POL-011',
                'name' => 'Poliklinik Kulit dan Kelamin',
                'bpjs_code' => '011',
                'description' => 'Spesialis dermatologi dan venerologi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-012',
                'name' => 'Poliklinik Gigi dan Mulut',
                'bpjs_code' => '012',
                'description' => 'Layanan kesehatan gigi dan mulut',
                'is_active' => true,
            ],
            [
                'code' => 'POL-013',
                'name' => 'Poliklinik Kandungan',
                'bpjs_code' => '013',
                'description' => 'Spesialis obstetri dan ginekologi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-014',
                'name' => 'Poliklinik Jiwa',
                'bpjs_code' => '014',
                'description' => 'Spesialis psikiatri dan kesehatan jiwa',
                'is_active' => true,
            ],
            [
                'code' => 'POL-015',
                'name' => 'Poliklinik Rehabilitasi Medis',
                'bpjs_code' => '015',
                'description' => 'Layanan rehabilitasi medis dan fisioterapi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-016',
                'name' => 'Poliklinik Gizi',
                'bpjs_code' => '016',
                'description' => 'Konsultasi gizi dan diet',
                'is_active' => true,
            ],
            [
                'code' => 'POL-017',
                'name' => 'Poliklinik Urologi',
                'bpjs_code' => '017',
                'description' => 'Spesialis saluran kemih dan reproduksi pria',
                'is_active' => true,
            ],
            [
                'code' => 'POL-018',
                'name' => 'Poliklinik Bedah Saraf',
                'bpjs_code' => '018',
                'description' => 'Spesialis bedah neurologi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-019',
                'name' => 'Poliklinik Bedah Plastik',
                'bpjs_code' => '019',
                'description' => 'Spesialis bedah plastik dan rekonstruksi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-020',
                'name' => 'Poliklinik Onkologi',
                'bpjs_code' => '020',
                'description' => 'Spesialis kanker',
                'is_active' => true,
            ],
            [
                'code' => 'POL-021',
                'name' => 'Poliklinik Geriatri',
                'bpjs_code' => '021',
                'description' => 'Layanan kesehatan untuk lansia',
                'is_active' => true,
            ],
            [
                'code' => 'POL-022',
                'name' => 'Poliklinik Alergi dan Imunologi',
                'bpjs_code' => '022',
                'description' => 'Spesialis alergi dan imunologi',
                'is_active' => true,
            ],
            [
                'code' => 'POL-023',
                'name' => 'Poliklinik Endokrinologi',
                'bpjs_code' => '023',
                'description' => 'Spesialis gangguan hormon dan kelenjar',
                'is_active' => true,
            ],
            [
                'code' => 'POL-024',
                'name' => 'Poliklinik Gastroenterologi',
                'bpjs_code' => '024',
                'description' => 'Spesialis saluran cerna',
                'is_active' => true,
            ],
            [
                'code' => 'POL-025',
                'name' => 'Poliklinik Hematologi',
                'bpjs_code' => '025',
                'description' => 'Spesialis penyakit darah',
                'is_active' => true,
            ],
            [
                'code' => 'POL-026',
                'name' => 'Poliklinik Nefrologi',
                'bpjs_code' => '026',
                'description' => 'Spesialis ginjal',
                'is_active' => true,
            ],
            [
                'code' => 'POL-027',
                'name' => 'Poliklinik Radiologi',
                'bpjs_code' => '027',
                'description' => 'Layanan radiologi dan pencitraan medis',
                'is_active' => true,
            ],
        ];

        foreach ($polikliniks as $poliklinik) {
            DB::table('polyclinics')->updateOrInsert(
                ['code' => $poliklinik['code']],
                array_merge($poliklinik, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
