# Changelog

Semua perubahan penting pada SIMRS RumahSakitKu didokumentasikan di file ini.

Format versi mengikuti [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`

---

## [1.0.0] - 2026-02-08

### Ditambahkan (Initial Release)

#### Modul Pendaftaran
- Registrasi pasien baru dengan generate No. RM otomatis
- Pencarian pasien (NIK, No. RM, nama)
- Pendaftaran kunjungan (Rawat Jalan, Rawat Inap, IGD)
- Manajemen antrian dengan display TV
- Cetak kartu pasien
- Integrasi BPJS VClaim 2.0 (SEP generation)
- Integrasi Satu Sehat FHIR R4

#### Modul Rekam Medis (EMR)
- Dokumentasi SOAP (Subjective, Objective, Assessment, Plan)
- CPPT (Catatan Perkembangan Pasien Terintegrasi)
- Input TTV (Tanda-tanda Vital) dengan perhitungan otomatis (IMT, GCS)
- Pencarian diagnosis ICD-10 dengan autocomplete
- Finalisasi EMR dengan lock dokumen
- Cetak resume medis, surat rujukan, copy resep
- Template SOAP untuk kasus umum

#### Modul IGD (Instalasi Gawat Darurat)
- Pendaftaran cepat pasien darurat
- Sistem triase dengan 5 kategori (Merah, Kuning, Hijau, Biru, Hitam)
- Assessment TTV cepat
- Assign dokter jaga dengan notifikasi
- Transfer ke Rawat Inap
- Discharge (pulang, rujuk, DAMA, meninggal)

#### Modul Rawat Inap
- Admisi rawat inap dari berbagai jalur (RJ, IGD, Rujukan, Booking)
- Bed Management dengan peta kamar real-time
- Manajemen kamar (VIP, Kelas I/II/III)
- EMR Rawat Inap dengan CPPT harian per shift
- Monitoring TTV dengan grafik trend
- Rencana pulang dan Medical Check Out
- Okupansi kamar dashboard (BOR, LOS, BTO)

#### Modul Farmasi
- Resep masuk otomatis dari EMR final
- Verifikasi resep (Right Patient, Drug, Dose, Route, Time)
- Resep racik dengan sediaan (pulveres, pil, kapsul, salep)
- Manajemen stok obat dengan FIFO
- Dispensing dengan konseling pasien
- Cetak label obat (thermal printer)
- Monitoring ED (Expired Date) dan stok menipis

#### Modul Keuangan & Kasir
- Tagihan otomatis dari layanan (tindakan, lab, radiologi, obat)
- Pembayaran tunai dan non-tunai (debit, kredit, transfer, QRIS, e-wallet)
- Split payment (mixed payment methods)
- Klaim BPJS dengan integrasi VClaim
-process GL (Guarantee Letter) untuk asuransi
- Refund dengan multi-metode
- Cetak kwitansi (dot matrix/thermal)
- Laporan harian/mingguan/bulanan
- Dashboard okupansi kamar

#### Modul Bedah Sentral
- Jadwal operasi dengan booking
- Safety checklist (sign-in, sign-out, timeout)
- Laporan operasi dengan implant tracking
- Manajemen kamar operasi
- Integrasi dengan schedule dokter

#### Modul Penunjang Medis
- Laboratorium: order, entry hasil, cetak hasil, LIS integration
- Radiologi: PACS integration (X-Ray, CT, MRI), upload hasil luar
- Farmasi: e-resep, stok, expired monitoring
- Gizi: skrining, menu diet, label makanan
- Fisioterapi: jadwal, hasil terapi

#### Modul Laporan & Reporting
- Laporan RL 1-5 (Rumah Sakit) sesuai Kemenkes
- Dashboard analytics dengan KPI
- Laporan keuangan (pendapatan, piutang)
- Laporan inventory (stok, expired)
- Export Excel/PDF/CSV
- Custom report builder

#### Modul Admin & System
- User management dengan role-based access control (RBAC)
- 7 default roles: super_admin, pendaftaran, dokter_umum, dokter_spesialis, perawat, kasir, farmasi, manajemen
- Permission system (view, create, edit, delete, approve, export, print)
- Audit trail lengkap dengan retensi 7 tahun
- Master data management (ICD-10, tarif, dokter, poliklinik, obat)
- Backup/restore database
- System configuration (Nama RS, logo, timezone, bahasa, security settings)

#### Integrasi Eksternal
- **BPJS**:
  - VClaim 2.0: SEP, peserta, rujukan, appointment, klaim e-Claim
  - PCare: kunjungan, rujukan, sertifikat
  - Monitoring log bridging
- **Satu Sehat**:
  - FHIR R4: Patient, Encounter, Condition, Observation, Medication
  - IHS (Indonesian Health System) integration
  - Token management dengan caching

#### API & Integrasi Programatik
- RESTful API dengan Laravel Sanctum authentication
- API versioning (`/api/v1/`)
- Standardized JSON response format (success/error/pagination)
- Rate limiting (100 requests/min, 60 for BPJS, 120 for Satu Sehat)
- 9 API modules dengan dokumentasi lengkap di `docs/api/`
- SDK tersedia: PHP, JavaScript, Python

#### Code Quality & Testing
- **Testing**: 32 test files, 700+ test cases, ~80% coverage
  - Unit tests (models, services)
  - Feature tests (HTTP, API)
  - Parallel testing support
- **Static Analysis**: PHPStan level 5 dengan Larastan extension
- **Code Style**: PHP-CS-Fixer dengan PSR-12 + PER-CS2 (200+ rules)
- **Laravel Pint**: Laravel-specific coding standards
- **Security**: Laravel built-in protections (CSRF, XSS, SQL injection prevention)
- **CI/CD**: GitHub Actions dengan matrix PHP 8.2/8.3, Codecov integration

#### Infrastructure & Deployment
- **Docker Support**:
  - Full stack containers (app, nginx, mysql, redis, queue, scheduler)
  - `docker-compose.yml` dengan volume mounting
  - `.\docker-setup.ps1` automation script
  - `.\sail.bat` wrapper untuk 30+ commands
- **Production Deployment**:
  - Nginx configuration dengan security headers
  - PHP-FPM optimal settings (50 max children)
  - Supervisor untuk queue workers
  - Cron scheduler untuk tasks
  - SSL dengan Let's Encrypt
  - Backup strategy (3-2-1 rule)
  - Monitoring (logs, health checks)
- **Makefile**: 30+ targets untuk develop, test, analyze, deploy

#### Documentation
- **README.md** (English + Indonesian) - Tech stack, installation, configuration, testing strategy, database schema
- **User Guides** (`docs/user-guide/`): 12 modul panduan lengkap untuk end-user
- **API Documentation** (`docs/api/`): 9 modul API dengan request/response examples
- **DEVELOPMENT.md**: Coding standards, Git workflow, testing guidelines, PR process
- **DEPLOYMENT.md**: Production deployment lengkap (60+ pages)
- **DOCKER_SETUP.md**: Docker quick start dan manual setup
- **TESTING_REPORT.md**: Test statistics, code quality report, recommendations
- **Requirements.md**: Tech stack comparison, architecture decisions

---

## [Unreleased] - Upcoming Changes

### Ditambahkan (Planned for v1.1.0)

#### Modul Baru
- **Modul Kesehatan Ibu & Anak (KIA)**: antenatal care, imunisasi, tumbuh kembang
- **Modul MCU (Medical Check Up)**: paket pemeriksaan, hasil, rekomendasi
- **Modul Rehabilitasi Medik**: fisioterapi, terapi occlusion, speech therapy
- **Modul Gizi Klinis**: asesmen gizi, menu diet, monitoring nutrisi

#### API Enhancements
- GraphQL API option (selain REST)
- Webhooks untuk event-driven integration
- Bulk operations untuk import/export massal
- Advanced filtering dengan Elasticsearch
- Real-time notifications dengan WebSocket

#### Integrasi Tambahan
- **JKN Mobile API**: integrating dengan aplikasi JKN mobile
- **Lab Interoperability**: HL7 v2/v3 untuk LIS
- **Radiology PACS**: DICOM integration dengan Orthanc
- **E-Healthcare Indonesia**: national health information exchange

#### Performance & Scalability
- Query optimization dengan indexstrategi baru
- Redis caching untuk frequent queries
- Queue scaling dengan Horizon
- Database sharding untuk data historis
- CDN untuk assets statis

#### Security & Compliance
- Two-factor authentication (2FA)
- SSO dengan SAML/OAuth2
- GDPR compliance tools (data export, deletion)
- Audit log retention management
- Penetration testing report

#### Developer Experience
- API client generator (OpenAPI/Swagger)
- Enhanced test factories dengan Faker
- Database seeder untuk environments
- Docker Compose override untuk development
- VS Code extensions dan settings sync

---

### Changed (Breaking Changes)

#### v1.1.0
- Menghapus support untuk PHP 8.1 (minimal PHP 8.2)
- Migrasi dari Laravel 12.x ke Laravel 12.x LTS
- API response format changes (menambahkan `meta` di semua responses)
- Database columns rename: `patient_id` → `medical_record_id` di tabel `medical_records`
- Policy namespace changes untuk compatibility dengan Laravel 12

---

### Fixed

#### v1.0.1 - 2026-02-10
- Bug: Duplicate SEP generation untuk pasien BPJS kontrol
- Bug: Stok obat tidak berkurang setelah dispensing在某些情况下
- Bug: TTV tidak tersimpan jika SpO2 kosong
- Bug: PDF kwitansi tidak generate di printer thermal
- Bug: Memory leak di dashboard analytics saat data besar
- Bug: Race condition di queue worker untuk billings

#### v1.0.0 - Initial Release
- Initial stable release

---

## Versioning Strategy

### Major Version (X.0.0)
- Breaking changes yang memerlukan migrasi manual
- Database schema changes incompatible with previous version
- API incompatible changes
- Minimum requirements increase (PHP, MySQL)

### Minor Version (1.X.0)
- New features yang backwards-compatible
- New database tables/columns dengan migrations
- New API endpoints (existing endpoints unchanged)
- New configuration options

### Patch Version (1.0.X)
- Bug fixes
- Security patches
- Performance improvements
- Documentation updates
- No new features

---

## Upgrade Path

| Current Version | Recommended Upgrade Path |
|-----------------|--------------------------|
| 1.0.x           | 1.1.0 (minor)            |
| 1.0.x           | 2.0.0 (major) - attention required |
| 0.9.x           | 1.0.0 (major) - full migration needed |

See [UPGRADE.md](./UPGRADE.md) for detailed upgrade instructions untuk setiap major/minor release.

---

## Support Policy

| Version | Status       | Released   | End of Life | PHP Support |
|---------|--------------|------------|-------------|-------------|
| 1.0.x   | Stable       | 2026-02-08 | 2026-08-08 | 8.2, 8.3    |
| 1.1.x   | Development  | TBA        | TBA         | 8.2, 8.3    |
| 2.0.x   | Planned      | TBD        | TBD         | 8.3+        |

**Security fixes** akan diberikan untuk 12 bulan setelah release. **Bug fixes** untuk 6 bulan setelah release.

---

## How to Read This Document

- **Ditambahkan**: New features dan capabilities
- **Changed**: Existing functionality yang berubah (mungkin breaking)
- **Deprecated**: Features yang akan dihapus di release mendatang
- **Removed**: Features yang sudah dihapus
- **Fixed**: Bug fixes
- **Security**: Security-related fixes

---

## Reporting Issues

Jika Anda menemukan bug atau masalah:
1. Check existing issues di [GitHub Issues](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/issues)
2. Create new issue dengan template yang disediakan
3. Include: steps to reproduce, expected vs actual behavior, environment details
4. For security vulnerabilities, lihat [SECURITY.md](./SECURITY.md)

---

## Contributing

Kontribusi diterima! See [CONTRIBUTING.md](./CONTRIBUTING.md) untuk guidelines.

---

*This changelog follows the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format.*  
*Versioning follows [Semantic Versioning](https://semver.org/).*
