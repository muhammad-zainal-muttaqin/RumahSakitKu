# SIMRS RumahSakitKu

Sistem Informasi Manajemen Rumah Sakit untuk RumahSakitKu - RS Kelas A dengan 27 poliklinik.

## Spesifikasi Teknis

- **Framework**: Laravel 12.x
- **Admin Panel**: Filament 4.x (Unified Schema)
- **PHP**: 8.2+
- **Database**: MySQL 8.0+ / MariaDB 10.6+
- **Cache/Queue**: Redis (opsional)

## Fitur Utama

### Modul Pendaftaran
- Master Data Pasien dengan Nomor Rekam Medis otomatis
- Pendaftaran Kunjungan Rawat Jalan/Rawat Inap/IGD
- Sistem Antrian Poliklinik dengan Display
- Integrasi BPJS VClaim (SEP, Peserta, Rujukan)

### Modul Rawat Jalan
- Rekam Medis Elektronik (EMR)
- Asesmen Awal dengan Vital Signs (TTV)
- CPPT (SOAP Format)
- E-Resep dengan Racikan

### Modul Farmasi
- Manajemen Stok Obat
- Proses Resep
- Katalog Obat dengan KFA

### Modul Keuangan
- Billing dan Tagihan
- Pembayaran multi-metode
- Integrasi E-Klaim BPJS

### Integrasi
- **BPJS**: VClaim 2.0, E-Klaim, PCare
- **Satu Sehat**: FHIR R4 (Pasien, Encounter, Observation, Medication)

## Instalasi

### 1. Clone dan Install Dependencies

```bash
# Clone repository
cd C:\Users\Zainal\Desktop\RumahSakitKu

# Install PHP dependencies
composer install

# Install Filament
php artisan filament:install --panels

# Install Spatie Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_DATABASE=rumahsakitu_simrs
DB_USERNAME=root
DB_PASSWORD=your_password

# BPJS Configuration
BPJS_CONS_ID=your_cons_id
BPJS_SECRET_KEY=your_secret_key
BPJS_USER_KEY=your_user_key
BPJS_PPK_CODE=your_ppk_code

# Satu Sehat Configuration
SATUSEHAT_CLIENT_ID=your_client_id
SATUSEHAT_CLIENT_SECRET=your_client_secret
SATUSEHAT_ORGANIZATION_ID=your_org_id
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed
```

### 4. Storage Link

```bash
php artisan storage:link
```

### 5. Run Development Server

```bash
php artisan serve
```

Akses aplikasi di: http://localhost:8000/admin

Default login:
- Email: admin@rumahsakitku.test
- Password: password

## Struktur Database

### Core Tables
- `patients` - Master data pasien
- `visits` - Kunjungan pasien
- `medical_records` - Rekam medis
- `assessments` - Asesmen awal dengan TTV
- `cppts` - Catatan perkembangan pasien (SOAP)
- `prescriptions` - Resep dokter
- `prescription_items` - Item resep
- `invoices` - Tagihan
- `payments` - Pembayaran

### Master Data
- `polyclinics` - 27 poliklinik
- `employees` - Dokter, perawat, staff
- `rooms` - Kamar rawat inap (VVIP-VIP-I-II-III)
- `beds` - Tempat tidur
- `medicines` - Master obat
- `procedures` - Master tindakan
- `lab_tests` - Master pemeriksaan lab

### Supporting
- `visit_queues` - Antrian kunjungan
- `bpjs_logs` - Log bridging BPJS
- `audit_logs` - Audit trail

## Role dan Permissions

| Role | Akses |
|------|-------|
| super_admin | Full system access |
| pendaftaran | Pasien, Kunjungan, Antrian |
| dokter_umum | EMR, Assessment, CPPT, Resep |
| dokter_spesialis | EMR + Spesialisasi |
| perawat | Assessment, CPPT |
| kasir | Billing, Payment |
| farmasi | Resep, Stok Obat |
| laboratorium | Lab Orders |
| manajemen | Semua laporan |

## API Integrasi

### BPJS
```php
// Cek peserta
$bpjs = app(BpjsVclaimService::class);
$peserta = $bpjs->getPesertaByNik('1234567890123456', now());

// Buat SEP
$sep = $bpjs->createSep([
    'noKartu' => '00012345678',
    'tglSep' => now()->format('Y-m-d'),
    'ppkPelayanan' => config('bpjs.ppk.code'),
    // ...
]);
```

### Satu Sehat
```php
// Generate IHS Number
$satuSehat = app(SatuSehatPatientService::class);
$result = $satuSehat->generateNIK($patient);

// Create Encounter
$encounter = app(SatuSehatEncounterService::class);
$result = $encounter->createEncounter($visit, $ihsNumber, $locationId);
```

## Development

### Menjalankan Queue Worker
```bash
php artisan queue:work --queue=default,bpjs,satusehat
```

### Schedule Task
```bash
# Tambahkan ke crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Testing
```bash
php artisan test
```

## Lisensi

Proprietary - RumahSakitKu

## Support

Untuk bantuan teknis, hubungi Tim IT RumahSakitKu.
