# Frequently Asked Questions (FAQ)

Kumpulkan pertanyaan umum dari semua modul dokumentasi.

---

## Contents

- [Umum](#umum)
- [Instalasi & Setup](#instalasi--setup)
- [Login & Access](#login--access)
- [Pendaftaran & Pasien](#pendaftaran--pasien)
- [Rekam Medis (EMR)](#rekam-medis-emr)
- [Farmasi](#farmasi)
- [Keuangan & Kasir](#keuangan--kasir)
- [Rawat Inap](#rawat-inap)
- [IGD](#igd)
- [BPJS & Integrasi](#bpjs--integrasi)
- [API & Developer](#api--developer)
- [Troubleshooting](#troubleshooting)

---

## Umum

### Q: Apa itu SIMRS RumahSakitKu?
**A:** SIMRS (Sistem Informasi Manajemen Rumah Sakit) adalah sistem terintegrasi untuk mengelola seluruh operasional rumah sakit, termasuk pendaftaran pasien, rekam medis elektronik (EMR), farmasi, keuangan, rawat inap, IGD, laboratorium, radiologi, dan laporan rumah sakit. Sistem ini dikembangkan dengan Laravel 12.x dan Filament 4.x.

### Q: Berapa minimal requirement untuk menjalankan SIMRS?
**A:**
- PHP 8.2+
- MySQL 8.0+ atau MariaDB 10.6+
- 4 GB RAM (8 GB recommended)
- 50 GB storage (SSD recommended)
- Redis (opsional untuk cache/queue)

Lihat [Requirements.md](./Requirements.md) untuk detail lengkap.

### Q: Apakah SIMRS ini gratis atau berbayar?
**A:** SIMRS RumahSakitKu adalah open-source (AGPL-3.0 license). Anda bebas menggunakan, memodifikasi, dan mendistribusikan, namun perubahan harus dibagikan kembali di bawah license yang sama. Untuk deployment production, pertimbangkan support services.

### Q: Apakah bisa untuk rumah sakit swasta & pemerintah?
**A:** Ya, desain modular memungkinkan digunakan untuk RS swasta, pemerintah, atau klinik pratama. Sesuaikan dengan kebutuhan dan skala.

### Q: Berapa kapasitas maksimal pasien/hari?
**A:** Sistem tested untuk 100-500 concurrent users dengan baik. Untuk 1000+ concurrent, perlu optimasi infrastructure (load balancer, database replication, Redis cluster).

---

## Instalasi & Setup

### Q: Instalasi dalam 5 menit mungkin?
**A:** Ya! Gunakan Docker:

```powershell
.\docker-setup.ps1
```

Atau manual:
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Detail lihat [README.md](../README.md#installation) dan [DOCKER_SETUP.md](./DOCKER_SETUP.md).

### Q: Tidak ada Xdebug, coverage test tidak jalan?
**A:** Xdebug diperlukan untuk code coverage. Install dengan:

```bash
pecl install xdebug
```

Atau gunakan Docker (Xdebug sudah include di `docker-compose.yml`). Di CI/CD (GitHub Actions), Xdebug sudah dikonfigurasi.

### Q: Database error "SQLSTATE[HY000] [2002] Connection refused"?
**A:**
1. Pastikan MySQL/MariaDB running:
   ```bash
   sudo systemctl status mysql
   ```
2. Cek credentials di `.env`:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rumahsakitu_simrs
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
3. Test connection:
   ```bash
   mysql -u root -p -e "SHOW DATABASES;"
   ```

### Q: Permission error saat write ke storage?
**A:** Set permissions:

**Linux/Mac:**
```bash
chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

**Windows:**
```powershell
icacls storage /grant "IIS_IUSRS:(OI)(CI)(F)"
icacls bootstrap\cache /grant "IIS_IUSRS:(OI)(CI)(F)"
```

Atau dalam Docker, permissions sudah disetup otomatis.

---

## Login & Access

### Q: Default credentials apa?
**A:**
- Email: `admin@rumahsakitku.test`
- Password: `password`

**⚠️ Ganti password segera setelah login pertama!**

### Q: Lupa password,如何 reset?
**A:** Login sebagai admin, lalu:
1. Menu **Admin** → **Pengguna**
2. Cari user yang lupa password
3. Klik **"Reset Password"**
4. Generate password baru atau input manual
5. User login dengan password baru, REQUIRED ganti password

Alternatif: Edit direkt di database (`users` table) dengan `php artisan tinker`:

```php
$user = App\Models\User::where('email', 'user@email.com')->first();
$user->password = Hash::make('newpassword');
$user->save();
```

### Q: Akun locked setelah beberapa kali gagal login?
**A:** Default setting: akun terkunci setelah 3x gagal login. Admin dapat:
1. Menu **Admin** → **Audit Trail** (cari failed login attempts)
2. Unlock akun di **Pengguna** → Edit → Centang "Active"
Atau via command:
```bash
php artisan auth:unlock user@email.com
```

### Q: Role apa yang punya akses ke modul X?
**A:** Cek di **Admin** → **Role & Permission**. Default roles:

| Role | Akses Utama |
|------|-------------|
| super_admin | Semua |
| pendaftaran | Pasien, kunjungan, antrian |
| dokter_umum/dokter_spesialis | EMR, SOAP, CPPT, resep |
| perawat | Asesmen, TTV, CPPT (hanya I/O) |
| kasir | Billing, pembayaran, refund |
| farmasi | Resep, stok, dispensing |
| manajemen | Laporan, analytics |

---

## Pendaftaran & Pasien

### Q: NIK sudah terdaftar, apa artinya?
**A:** Setiap pasien harus memiliki NIK unik (16 digit). Jika error "NIK sudah terdaftar":
1. Cari pasien dengan NIK tersebut via search
2. Jika itu pasien yang sama → gunakan data lama (tidak buat baru)
3. Jika berbeda orang → kemungkinan NIK salah input, hubungi admin untuk resolution

### Q: No. RM (Nomor Rekam Medis) bisa diubah?
**A:** No. RM bersifat **immutable** setelah dibuat. Ini untuk konsistensi data historis. Jika ada kesalahan, create new patient record dengan NIK sama (jika memang orang yang sama) atau consult admin untuk special handling.

### Q: Cara cetak ulang kartu pasien?
**A:** Di menu **Pendaftaran** → **Data Pasien**, cari pasien, klik icon **"🖨️ Cetak Kartu"**. Atau dari detail pasien, tab **"Kartu"**.

### Q: Format No. RM apa?
**A:** Generated otomatis dengan format: `YYMMDD-XXXX` (tanggal registrasi + sequence). Contoh: `240101-0001` (pasien pertama tanggal 1 Jan 2024).

### Q: Pasien Lama tidak ketemu saat search?
**A:** Pastikan:
1. Cari dengan NIK (paling akurat)
2. Cari dengan No. RM lengkap
3. Coba sebagian nama (min 3 huruf)
4. Cek apakah pasien sudah non-aktif (soft deleted)
5. Filter "Semua Status" di search

---

## Rekam Medis (EMR)

### Q: Apa perbedaan SOAP dan CPPT?
**A:**
- **SOAP**: Subjective, Objective, Assessment, Plan. Digunakan untuk kunjungan rawat jalan dan initial assessment.
- **CPPT**: Catatan Perkembangan Pasien Terintegrasi (S, O, A, P, I, E). Digunakan untuk rawat inap dan berulang, dengan kolom Implementasi (I) dan Evaluasi (E).

### Q: EMR sudah final, bisaDiedit lagi?
**A:** Tidak, EMR final adalah dokumen legal. Untuk emergency correction:
1. Hubungi **supervisor dokter** atau **spv rekam medis**
2. Ajukan permohonan pembukaan kembali
3. Setelah approved, EMR dapat diedit, lalu finalisasi ulang
4. Alasan pembukaan tercatat di audit trail

### Q: ICD-10 tidak ketemu saat search diagnosis?
**A:**
1. Coba kata kunci lebih umum (misal: "hipertensi" bukan "hipertensi dengan komplikasi")
2. Gunakan kode numerik langsung (I10, E11, J06.9)
3. Beberapa diagnosis specifically excluded - cek note di database
4. Jika memang belum ada di master data, hubungi bagian coding/rekam medis untuk tambah master ICD-10

### Q: TTV tidak muncul di grafik monitoring?
**A:**
1. Pastikan TTV sudah **disimpan** ( klik "Simpan" )
2. Filter grafik dengan range tanggal yang benar
3. Cek apakah data TTV di另一个 patient atau shifted
4. Coba refresh halaman (F5)
5. Jika masih tidak, contact IT untuk investigate database

### Q: Resep tidak terkirim ke farmasi setelah finalisasi?
**A:**
1. Pastikan EMR sudah **final** (bukan draft)
2. Cek koneksi jaringan ke server farmasi
3. Verifikasi farmasi module sudah running (queue worker)
4. Manual trigger: **Menu Farmasi** → **Resep Masuk** → **Refresh**
5. Jika masih tidak, cek log:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Farmasi

### Q: Stok obat tidak berkurang setelah dispensing?
**A:** Ini shouldn't happen. Possible causes:
1. Belum klik **"Selesai Dispensing"** (status masih "Proses")
2. System error - cek log untuk database transaction failure
3. Obat tersebut adalah "racik" dengan batch yang belum di-finalize
4. **Manual adjustment** diperlukan: Menu **Farmasi** → **Stok Obat** → **Adjustment** (memerlukan approval supervisor)

### Q: Obat ED (Expired Date) kurang dari 6 bulan, bagaimana?
**A:** Sistem akan otomatis warning jika ED < 6 bulan. Tindakan:
1. **Prioritaskan penggunaan** untuk pasien (FEFO: First Expire First Out)
2. **Ajukan retur** ke supplier jika memungkinkan
3. **Tuliskan di label** "ED Dekat" untuk peringatan
4. **Emergency order** untuk obat alternatif jika stok lain tersedia

### Q: Resep racik tidak siap sama waktu dengan obat jadi?
**A:** Resep racik membutuhkan waktu proses (timban, campur, kemas):
1. Informasikan ke pasien estimasi waktu (30-60 menit)
2. **Booking/reservation** untuk racik
3. Notifikasi ke pasien via SMS/display当 ready
4. Jika membutuhkan waktu sangat cepat (emergency), konsul dokter untuk obat jadi alternatif

### Q: Bisa reject resep jika ada interaksi berbahaya?
**A:** Ya, sebagai apoteker:
1. Klik **"Konsul"** pada resep
2. Chat/telepon dokter yang meresepkan
3. Documentkan alasan reject
4. Jika tetap ingin diberikan (after doctor approval), override dengan **"Verifikasi dengan Catatan"**
5. Log semua komunikasi di audit trail

---

## Keuangan & Kasir

### Q: Total tagihan tidak sesuai dengan rincian?
**A:**
1. Expand semua item, verify qty dan harga satuan
2. Cek apakah ada duplicate item
3. Cek diskon/promotion yang applied
4. Cek tarif master data (mungkin tarif sudah update)
5. Jika benar ada kesalahan, supervisor bisa **adjust** invoice sebelum payment

### Q: Pembayaran BPJS, kenapa klaim ditolak?
**A:** Common reasons:
- **SEP tidak sesuai** dengan diagnosis (upcoding/downcoding)
- **Diagnosis tidak涵盖** di INA-CBGs untuk tarif kelas
- **Kelahiran/tanggal pelayanan** tidak sesuai
- **Dokter tidak sesuai** dengan spesialisasi yang dijamin
- **Missing required data** (diagnosis secundair, procedural code)
- **Tarif exceed** dari batas BPJS

**Solution:** 
1. Cek detail rejection reason di **Billing** → **BPJS Klaim** → **Detail Rejection**
2. Konsul dokter untuk koreksi diagnosis/tindakan
3. Resubmit dengan data yang dikoreksi
4. Jika perlu, manual claim via portal BPJS

### Q: How to refund jika pasien kelebihan bayar?
**A:**
1. Cari transaksi via No. Kwitansi atau No. RM
2. Menu **Keuangan** → **Refund**
3. Input nominal refund
4. Pilih metode:
   - **Tunai**: Give cash dengan tanda tangan
   - **Transfer**: Input no. rekening pasien
   - **Potong Tagihan**: Jika ada tagihan lain
5. **Approval** dari supervisor if > Rp 500.000
6. Cetak **Kwitansi Refund** (RF/YYYY/MM/NNNN)
7. Archive dokumen pembayaran asli + refund

### Q: Pembayaran kartu debit gagal tapi saldo sudah terpotong?
**A:** Ini kasus **"pending transaction"**:
1. Cek **struk EDC** - ada approval code?
2. Cek **SMS bank** - konfirmasi debit?
3. Jika saldo terpotong tapi invoice belum lunas:
   - **Jangan proses refund manual dulu**
   - Tunggu balancing EOD (end-of-day) dari bank
   - Jika 2x24 jam belum settle, lakukan **chargeback** dengan bantuan bank
4. Dokumentasikan dan assign ke **keuangan** untuk follow-up

---

## Rawat Inap

### Q: Bed tidak tersedia untuk pasien emergency?
**A:**
1. Cek **"Siap Cleaning"** beds (sudah bersih tapi belum di-assign)
2. Prioritaskan cleaning untuk kamar yang ready最快
3. Consider **upgrade/downgrade kelas** sesuai penjamin dan让步 pasien
4. Jika.kamar同类 full, koordinasi dengan **IGD** untuk暂留 di IGD observation
5. Emergency escalation ke **kepala ruangan** dan **bed management team**
6. Jika semua kamar penuh dan pasien critical, consider **referral** ke RS lain (dokumen dengan surat rujukan)

### Q: Pasien tidak bisa checkout karena billing belum final?
**A:**
1. Pastikan semua **layanan tercatat** (lab, radiologi, operasi)
2. Pastikan **farmasi** sudah final semua resep
3. Pastikan **nutrition** (Gizi) sudah input
4. **DPJP tanda tangan** Medical Check Out
5. Jika ada komplain tarif, konsul dengan **keuangan** untuk verifikasi
6. Jika billing system error, escalate ke IT dengan screenshot

### Q: Okupansi kamar sangat tinggi (>95%), bagaimana?
**A:** Tingkat okupansi >85% sudah high. Mitigation:
1. **Accelerate discharge** untuk pasien siap pulang (fast-track)
2. **Prioritize transfer** ke kelas lebih rendah jika possible
3. **Optimasi bed turnover**: cleaning más rápido (target <30 menit)
4. **Day care** untuk procedures yang bisa same-day discharge
5. **Negotiate dengan admissions**: buffer untuk elective admissions
6. **Consider opening surge capacity** (convert other rooms to inpatient)

### Q: TTV rawat inap tidak masuk ke grafik?
**A:** Mungkin:
1. TTV entered dengan **wrong timestamp** (check date/time)
2. **Missing shift selection** - TTV dikaitkan dengan shift (pagi/sore/malam)
3. **GCS calculation error** - fields E, V, M tidak terisi
4. **Cache issue** - clear browser cache atau refresh dashboard

---

## IGD

### Q: Triase merah vs kuning perbedaannya?
**A:**

| Parameter | Merah (Resusitasi) | Kuning (Emergency) |
|-----------|-------------------|-------------------|
| Waktu Respon | 0 menit (segara) | < 15 menit |
| Contoh | Cardiac arrest, airway obstruction, massive bleeding | Chest pain, stroke, severe asthma |
| Lokasi | Resusitation room | High care/monitoring |
| Tim | Full trauma/resus team | Single doctor + nurse |
| Equipment | Crash cart, defib, airway set | Monitor, O2, basic airway |

**Important:** Triase decision clinical judgment, not just scores. Re-assess regularly!

### Q: Pasien IGD bisa langsung rawat inap tanpa melalui RJ?
**A:** Ya, **Direct Admission dari IGD**:
1. Triase → Assessment → Dokter IGD oficinal diagnosis
2. Jika perlu rawat inap, order **"Transfer ke Rawat Inap"**
3. System generates new RI registration number
4. Proceed to bed assignment (jika ada bed)
5. Jika tidak ada bed, status **"RI Pending Bed"** sampai bed tersedia

### Q: Bagaimana dengan pasien unknown (tidak dikenal)?
**A:**
1. Beri **nama sementara**: `UNKNOWN-L` atau `UNKNOWN-P` + gender
2. Estimasi umur based on appearance
3. Dokumentasikan **physical characteristics** (height, weight, tattoos, scars, clothing)
4. **Police notification** untuk identifikasi
5. Beri **gelang** dengan UNKNOWN + No. RM temporary
6. Jika keluarga datang, lakukan **contoh-matched DNA** atau other identification

---

## BPJS & Integrasi

### Q: SEP tidak bisa generate (error dengan VClaim)?
**A:** Common issues:
1. **Connection timeout**: Cek koneksi internet, firewall
2. **Invalid CONS-ID/SECRET-KEY**: Verify credentials di `.env`:
   ```env
   BPJS_CONS_ID=your_cons_id
   BPJS_SECRET_KEY=your_secret_key
   ```
3. **CORS error**: VClaim endpoint changes - check [BPJS documentation](https://dvlp.bpjs-kesehatan.go.id)
4. **User account locked** di vClaim - contact BPJS IT
5. **Rujukan tidak ditemukan** - verify no. rujukan and Faskes level
6. **PPK code mismatch** - check `BPJS_PPK_CODE`

Cek logs: `storage/logs/bpjs*.log`

### Q: Klaim BPJS ditolak dengan reason "Diagnosis tidak sesuai INA-CBGs"?
**A:**
- **ICD-10 code** tidak sesuai grouping untuk tarif kelas
- **Diagnosis principal** kurang spesifik (use more specific code!)
- **Secondary diagnosis** tidak di-input yang dibutuhkan
- **Procedure code** (ICD-9) tidak sesuai atau missing

**Solution:**
1. Konsul **coding officer** atau dokter untuk koreksi ICD-10
2. Gunakan **Grouper tool** BPJS untuk test before submit
3. Modify claim data, then **resubmit** (edit → submit)
4. If repeated, **manual submit** via portal BPJS dengan dokumen pendukung

### Q: Satu Sehat integration, kenapa data tidak sync?
**A:** Satu Sehat menggunakan FHIR R4. Check:
1. **Client credentials**:
   ```env
   SATUSEHAT_CLIENT_ID=your_client_id
   SATUSEHAT_CLIENT_SECRET=your_client_secret
   SATUSEHAT_ORGANIZATION_ID=your_org_id
   ```
2. **Token expiration** - token di-cache 1 jam, cek valid
3. **FHIR endpoint** correct (production vs. sandbox)
4. **IHS generation** - Patient must have IHS number first
5. **Resource validation** - some required fields missing (e.g., `identifier` system)
6. **Check logs**: `storage/logs/satusehat*.log`

---

## API & Developer

### Q: How to get API token?
**A:**
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@rumahsakitku.com",
  "password": "password"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": { ... }
  }
}
```

Gunakan token di header:
```http
Authorization: Bearer 1|abc123...
```

### Q: API rate limit exceeded,怎么办?
**A:** Default limits:
- Authentication: 10 req/min
- Standard API: 100 req/min
- BPJS: 60 req/min
- Satu Sehat: 120 req/min

Jika exceeded, Tunggu **60 seconds** untuk reset. Atau contact admin untuk increase limit untuk your app.

Implement **exponential backoff**:
```javascript
const delay = Math.min(1000 * Math.pow(2, retryCount) + Math.random() * 1000, 30000);
```

### Q: Which API endpoints are available?
**A:** Lengkap lihat [docs/api/README.md](./docs/api/README.md) dan sub-modules:
- `/api/auth/*` - Authentication
- `/api/patients/*` - Patient management
- `/api/visits/*` - Visit/kunjungan
- `/api/medical-records/*` - EMR
- `/api/pharmacy/*` - Farmasi
- `/api/billing/*` - Keuangan
- `/api/bpjs/*` - BPJS integration
- `/api/satusehat/*` - Satu Sehat FHIR
- `/api/webhooks/*` - Event notifications

### Q: How to test API with Postman?
**A:**
1. Import Postman collection dari: `https://api.rumahsakitku.com/docs/postman` (jika production)
2. Atau create new collection:
   - Set base URL: `http://localhost:8000/api`
   - Login dulu untuk get token
   - Save token ke environment variable `{{token}}`
   - Set header: `Authorization: Bearer {{token}}`
3. Test endpoints:
   - `GET /patients` - list patients
   - `POST /patients` - create patient
   - `GET /patients/{id}/visits` - get visits

### Q: Error "Token has expired" (401)?
**A:** Token lifetime default: **1 hour**. Refresh dengan:
```http
POST /api/auth/refresh
Authorization: Bearer {expired_token}
```

Atau login ulang untuk get new token.

Implement automatic refresh di client:
- Store token dengan `expires_at` timestamp
- Before API call, check if expired
- If expired, call refresh endpoint
- If refresh fails, re-login

---

## Troubleshooting

### Q: "Class 'ZipArchive' not found" saat install composer?
**A:** PHP extension `zip` tidak terinstall. Install:

**Ubuntu/Debian:**
```bash
sudo apt-get install php-zip
sudo service apache2 restart
```

**CentOS/RHEL:**
```bash
sudo yum install php-pecl-zip
sudo systemctl restart httpd
```

**Windows (XAMPP/WAMP):**
Enable `extension=zip` di `php.ini`, restart Apache.

### Q: Memory limit exhausted saat running tests/artisan?
**A:** Increase PHP memory limit di `.env`:
```env
PHP_MEMORY_LIMIT=512M
```

Atau di CLI:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
php -d memory_limit=512M artisan test
```

### Q: Queue worker tidak menjalankan jobs?
**A:** Pastikan:
1. Redis running:
   ```bash
   redis-cli ping
   ```
2. Queue worker started:
   ```bash
   php artisan queue:work --daemon
   # atau dengan supervisor
   sudo supervisorctl status simrs-worker:*
   ```
3. Check failed jobs:
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```
4. Gunakan `php artisan queue:listen` untuk development

### Q: Storage link (symbolic link) tidak bekerja di Windows?
**A:** Di Windows, run sebagai Administrator:
```powershell
php artisan storage:link
```

Pastikan `C:\Windows\System32\cmd.exe` memiliki permission untuk create symlinks. Atau manual:
```powershell
mklink /D public\storage storage\app\public
```

### Q: MySQL error "Table is full" saat migration?
**A:** MySQL innodb_log_file_size terlalu kecil. Edit `my.cnf`:
```ini
innodb_log_file_size = 256M
innodb_file_per_table = 1
```
Restart MySQL setelah edit.

Alternatif: Reduce batch size di migration atau disable foreign key checks temporarily.

### Q: Docker container tidak start (port 8000 already in use)?
**A:**
1. Find process using port 8000:
   ```bash
   netstat -ano | findstr :8000  # Windows
   lsof -i :8000                # Linux/Mac
   ```
2. Kill process atau stop service
3. Atau edit `docker-compose.yml` → change nginx port mapping ke `8001:80`
4. Restart docker-compose:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Q: PDF tidak generate (DomPDF error)?
**A:**
1. Install PHP GD/Imagick:
   ```bash
   sudo apt-get install php-gd
   ```
2. Pastikan `storage/framework/views` writable
3. Clear view cache:
   ```bash
   php artisan view:clear
   ```
4. Check permissions:
   ```bash
   chmod -R 775 storage
   ```

### Q: SSL certificate error (self-signed) di local?
**A:** Untuk development, allow self-signed:
```php
// config/trustedproxy.php atau middleware
\URL::forceScheme('https');
```

Atau di browser, click "Advanced" → "Proceed to localhost (unsafe)". Untuk production, gunakan Let's Encrypt valid certificate.

---

## Performance

### Q: System lambat saat load hundreds of patients?
**A:** Causes & solutions:
1. **N+1 query problem**: Ensure eager loading with `with()` di models
2. **Missing database indexes**: Add index untuk columns yang sering di-filter (`nik`, `medical_record_number`, `name`)
3. **Large result set**: Implement pagination (default 20 per page)
4. **Server resource**: Increase PHP-FPM `pm.max_children` (default 50)
5. **Cache**: Implement Redis cache untuk frequently accessed data (config, master data)

### Q: How to enable query logging untuk debug slow queries?
**A:**
```php
// di route atau controller temporary
DB::listen(function ($query) {
    logger($query->sql, $query->bindings, $query->time);
});
```

Atau enable di `.env`:
```env
APP_DEBUG=true
DB_DEBUG=true
```

Check Laravel Telescope untuk detailed query analysis (development only).

---

## Security

### Q: How to change default admin password?
**A:** 
1. Login sebagai admin
2. Klik foto profil → **Profil** → **Ganti Password**
3. Input password lama + baru
4. Logout dan login dengan password baru

Force password change untuk all users:
```bash
php artisan password:force-reset --all
```

### Q: How to enable 2FA?
**A:** SIMRS belum support 2FA by default. Untuk deployment production, pertimbangkan:
1. Implement Laravel Fortify dengan 2FA
2. Atau reverse proxy dengan 2FA (Cloudflare Access, Authelia)
3. Atau hardware token untuk admin accounts

### Q: Data pasien aman? GDPR/health data compliance?
**A:** 
- **Encryption at rest**: Database columns `nik`, `phone` di-enkripsi dengan Laravel Encryption
- **Audit trail**:所有操作 tercatat dengan user, timestamp, IP
- **Backup**: Encrypted backups
- **Access control**: RBAC dengan permission system
- **Data retention**: Compliance dengan retention policy (7 years untuk Indonesia医疗记录)

Untuk GDPR/PDP compliance:
- Implement data export endpoint
- Implement "right to be forgotten" (soft delete + anonymize)
- Cookie consent (jika using tracking)
- Data breach notification procedure

---

## Updates & Maintenance

### Q: How to update ke versi terbaru?
**A:** See [UPGRADE.md](./UPGRADE.md) untuk detailed instructions.

Quick update:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**⚠� Always backup database before update!**

### Q: How to backup database otomatis?
**A:** Setup cron job:
```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /var/www/simrs/artisan backup:run --only-db
```

Atau gunakan Spatie Laravel Backup package (sudah included):
```bash
composer require spatie/laravel-backup
php artisan backup:run
```

Configure backup destination di `config/backup.php` (S3, Dropbox, etc).

---

## Miscellaneous

### Q: Mobile-responsive? Bisa diakses dari tablet?
**A:** Ya, SIMRS menggunakan Filament dengan responsive design. Tapi optimal untuk desktop/laptop (min. 1366x768). Mobile app dalam roadmap (PWA di Q1 2026).

### Q: Bisa multiple hospital (multi-tenant)?
**A:** Saat ini single-tenant. Multi-tenant dalam roadmap untuk v2.0 (2027). Untuk beberapaRS, gunakan separate installation atau schema-based multi-tenancy (custom modification).

### Q: How to customize logo dan warna?
**A:**
1. Upload logo di **Admin** → **Pengaturan** → **Umum** → Logo
2. Atau manual upload ke `public/storage/logos/` dan set di `.env`:
   ```env
   APP_LOGO=logos/custom-logo.png
   ```
3. Custom CSS: Edit `resources/css/filament.css` dan recompile:
   ```bash
   npm run build
   ```

### Q: Print tidak jalan di server Linux?
**A:** Server Linux by tidak punya printer default. Configure:
1. Install CUPS:
   ```bash
   sudo apt-get install cups
   ```
2. Add user ke `lp` group:
   ```bash
   sudo usermod -a -G lp www-data
   ```
3. Configure printer di CUPS admin panel (http://server:631)
4. Set default printer:
   ```bash
   lpoptions -d printer_name
   ```

---

## Contact Support

Jika FAQ tidak menyelesaikan masalah:

| Category | Contact | Response Time |
|----------|---------|---------------|
| **Bug Report** | GitHub Issues | 2-5 business days |
| **Security Issue** | security@rumahsakitku.com | < 24 hours |
| **General Question** | GitHub Discussions | 1-3 days |
| **Urgent Production Issue** | WhatsApp: +62 XXX-XXXX-XXXX | < 1 hour (SLA) |

Include dalam report:
1. SIMRS version (`php artisan --version`)
2. Environment (production/staging/local)
3. Steps to reproduce
4. Screenshots/error messages
5. Logs from `storage/logs/laravel.log`

---

*Last Updated: 2026-02-14*  
*Sedang dalam pengembangan untuk pertanyaan lebih lanjut. Submit your questions ke [GitHub Discussions](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/discussions).*
