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

### Testing Strategy

This project follows a comprehensive testing strategy:

- **Unit Tests**: Located in `tests/Unit`, test individual models, services, and business logic in isolation. Run with:
  ```bash
  make test-unit
  # or
  php artisan test --filter=Unit
  ```

- **Feature Tests**: Located in `tests/Feature`, test complete application flows and HTTP endpoints. Run with:
  ```bash
  make test-feature
  # or
  php artisan test --filter=Feature
  ```

- **Full Test Suite**: Run all tests with coverage reporting:
  ```bash
  make test-coverage
  # Generates XML coverage report (coverage.xml) for CI/CD
  make test-coverage-html
  # Generates HTML coverage report in storage/app/coverage
  ```

- **Parallel Testing**: Speed up test execution:
  ```bash
  make test-parallel
  ```

### Code Quality

Static analysis and code style enforcement:

```bash
make analyze          # PHPStan level 5 analysis
make format           # Auto-fix with PHP-CS-Fixer
make format-check     # Check without fixing
make lint             # Run all linting checks
make pint             # Laravel Pint code style
```

See [DEVELOPMENT.md](docs/DEVELOPMENT.md) for detailed development setup.

### Running Tests Locally

Ensure your `.env` is configured for testing:
```
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Then run:
```bash
php artisan test
```

All tests should pass before committing. The CI pipeline requires:
- PHPStan: 0 errors
- PHP-CS-Fixer: no style violations
- Tests: 100% pass with minimum 80% coverage (enforced via Codecov)

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

## Documentation

Comprehensive documentation is available:

### User Guides
- **[Pendaftaran (Registration)](./docs/user-guide/PENDAFTARAN.md)** - Patient registration and queue management
- **[Rekam Medis (EMR)](./docs/user-guide/REKAM_MEDIS.md)** - SOAP, CPPT, TTV, diagnosis coding
- **[Keuangan/Kasir (Finance)](./docs/user-guide/KEUANGAN.md)** - Billing, payments, refunds, receipts
- **[Rawat Inap (Inpatient)](./docs/user-guide/RAWAT_INAP.md)** - Admission, bed management, discharge
- **[IGD (Emergency)](./docs/user-guide/IGD.md)** - Triage, emergency care, transfer
- **[Farmasi (Pharmacy)](./docs/user-guide/FARMASI.md)** - Prescription processing, dispensing, stock management
- **[Admin (System)](./docs/user-guide/ADMIN.md)** - User management, audit trail, backup/restore
- **Plus more...** (Bedah Sentral, Penunjang Medis, Laporan) in `docs/user-guide/`

### Developer Guides
- **[API Reference](./docs/api/README.md)** - Complete REST API documentation (9 modules)
- **[Development Guide](./docs/DEVELOPMENT.md)** - Coding standards, Git workflow, testing
- **[Deployment Guide](./docs/DEPLOYMENT.md)** - Production deployment (Nginx, PHP-FPM, SSL, backup)
- **[Docker Setup](./DOCKER_SETUP.md)** - Docker development and production
- **[Testing Report](./TESTING_REPORT.md)** - Test coverage, code quality metrics

### Additional Resources
- **[CHANGELOG.md](./CHANGELOG.md)** - Version history dan release notes
- **[ROADMAP.md](./ROADMAP.md)** - Future features and development plans (2026-2027)
- **[FAQ.md](./FAQ.md)** - Frequently asked questions (all modules consolidated)
- **[UPGRADE.md](./UPGRADE.md)** - Upgrade instructions for each version
- **[CONTRIBUTING.md](./CONTRIBUTING.md)** - Guidelines for contributors
- **[SECURITY.md](./SECURITY.md)** - Security policy and vulnerability reporting
- **[Requirements.md](./Requirements.md)** - Tech stack decisions and benchmarks

### Translation Status
- 🇮🇩 **Indonesian**: All user guides complete (primary language)
- 🇺🇸 **English**: API docs, developer guides (partial - translation in progress)

---

## License

This project is licensed under the [GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0).

You are free to view, use, and modify the source code, provided that any modifications or derivative works are also distributed under the same license. If you run a modified version of this software on a server and provide access to it over a network, you must also make the source code available.

## Author

**Muhammad Zainal Muttaqin**

- GitHub: [@muhammad-zainal-muttaqin](https://github.com/muhammad-zainal-muttaqin)
