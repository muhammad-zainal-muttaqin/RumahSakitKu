# RumahSakitKu - SIMRS

Sistem Informasi Manajemen Rumah Sakit (SIMRS) berbasis web untuk rumah sakit kelas A dengan 27 poliklinik.

## Tech Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12.x |
| Admin Panel | Filament 4.x |
| PHP | 8.2+ |
| Database | MySQL 8.0+ / MariaDB 10.6+ |
| Cache/Queue | Redis (opsional) |
| PDF | DomPDF |
| Excel | Maatwebsite Excel |
| QR Code | Simple QRCode |
| Authorization | Spatie Permission |

## Modules

### Clinical
- **Pendaftaran** -- Pasien baru/lama, antrian poliklinik, booking
- **Rawat Jalan** -- EMR, asesmen awal, CPPT (SOAP), e-resep dengan racikan
- **Rawat Inap** -- Bed management, monitoring harian, discharge planning
- **IGD** -- Triage system (merah/kuning/hijau/hitam), pendaftaran darurat
- **Bedah Sentral** -- Jadwal operasi, safety checklist, laporan operasi, implant tracking

### Diagnostic
- **Laboratorium** -- Order, entry hasil, cetak hasil
- **Radiologi** -- Order, hasil, upload DICOM

### Pharmacy & Inventory
- **Farmasi** -- E-resep, peracikan, stok obat, monitoring expired

### Financial
- **Billing** -- Tagihan rawat jalan/inap, deposit, pembayaran multi-metode
- **Kasir** -- Pembayaran tunai/asuransi, refund

### Integration
- **BPJS** -- VClaim 2.0 (SEP, peserta, rujukan), PCare, E-Klaim
- **Satu Sehat** -- FHIR R4 (Patient, Encounter, Observation, Condition, Medication)

### System
- **Master Data** -- 27 poliklinik, dokter/perawat/staff, kamar, obat, tindakan, lab
- **Reporting** -- Laporan RL 1-5, dashboard analytics, KPI
- **Audit Trail** -- Full audit logging dengan retensi 7 tahun
- **User Management** -- Role-based access control (RBAC)

## Roles

| Role | Access |
|------|--------|
| super_admin | Full system access |
| pendaftaran | Pasien, kunjungan, antrian |
| dokter_umum | EMR, assessment, CPPT, resep |
| dokter_spesialis | EMR + spesialisasi |
| perawat | Assessment, CPPT |
| kasir | Billing, payment |
| farmasi | Resep, stok obat |
| laboratorium | Lab orders, hasil lab |
| manajemen | Semua laporan |

## Requirements

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Composer 2.x
- Redis (opsional, untuk cache & queue)

## Installation

```bash
# Clone repository
git clone https://github.com/muhammad-zainal-muttaqin/RumahSakitKu.git
cd RumahSakitKu

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=rumahsakitu_simrs
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Start development server
php artisan serve
```

Access the application at `http://localhost:8000/admin`.

Default credentials:
- Email: `admin@rumahsakitku.test`
- Password: `password`

## Configuration

### BPJS Integration

Set these values in `.env`:

```env
BPJS_CONS_ID=your_cons_id
BPJS_SECRET_KEY=your_secret_key
BPJS_USER_KEY=your_user_key
BPJS_PPK_CODE=your_ppk_code
```

### Satu Sehat Integration

```env
SATUSEHAT_CLIENT_ID=your_client_id
SATUSEHAT_CLIENT_SECRET=your_client_secret
SATUSEHAT_ORGANIZATION_ID=your_org_id
```

## Development

```bash
# Run tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Static analysis
vendor/bin/phpstan analyse --memory-limit=2G

# Code style check
vendor/bin/php-cs-fixer fix --dry-run --diff

# Code style fix
vendor/bin/php-cs-fixer fix --allow-risky=yes

# Queue worker
php artisan queue:work --queue=default,bpjs,satusehat

# Scheduled tasks (crontab)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

See `Makefile` for the full list of available commands (`make help`).

## Database Schema

### Core Tables
- `patients` -- Master data pasien
- `visits` -- Kunjungan pasien (rawat jalan/inap/IGD)
- `medical_records` -- Rekam medis
- `assessments` -- Asesmen awal dengan vital signs
- `cppts` -- Catatan perkembangan pasien (SOAP)

### Clinical Tables
- `prescriptions` / `prescription_items` -- Resep dokter
- `laboratory_orders` / `laboratory_results` -- Lab
- `radiology_orders` / `radiology_results` -- Radiologi
- `surgeries` / `surgery_implants` -- Bedah sentral

### Financial Tables
- `invoices` -- Tagihan
- `payments` -- Pembayaran

### Master Data
- `polyclinics` -- 27 poliklinik
- `employees` -- Dokter, perawat, staff
- `rooms` / `beds` -- Kamar rawat inap (VVIP/VIP/I/II/III)
- `medicines` -- Master obat
- `procedures` / `procedure_categories` -- Master tindakan
- `lab_tests` -- Master pemeriksaan lab

### System Tables
- `visit_queues` -- Antrian kunjungan
- `bpjs_logs` -- Log bridging BPJS
- `satu_sehat_logs` -- Log Satu Sehat
- `audit_logs` -- Audit trail

## Docker

```bash
docker-compose up -d
```

See [DOCKER_SETUP.md](DOCKER_SETUP.md) for the full Docker setup guide.

## License

This project is licensed under the [GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0).

You are free to view, use, and modify the source code, provided that any modifications or derivative works are also distributed under the same license. If you run a modified version of this software on a server and provide access to it over a network, you must also make the source code available.

## Author

**Muhammad Zainal Muttaqin**

- GitHub: [@muhammad-zainal-muttaqin](https://github.com/muhammad-zainal-muttaqin)
