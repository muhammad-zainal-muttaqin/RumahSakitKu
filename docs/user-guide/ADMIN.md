# Panduan Admin Sistem

Panduan lengkap untuk administrator SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Admin Sistem](#pengenalan-admin-sistem)
2. [Cara Mengelola Pengguna](#cara-mengelola-pengguna)
3. [Cara Mengelola Role dan Permission](#cara-mengelola-role-dan-permission)
4. [Cara Melihat Audit Trail](#cara-melihat-audit-trail)
5. [Cara Backup Database](#cara-backup-database)
6. [Cara Restore Database](#cara-restore-database)
7. [Cara Mengubah Pengaturan Sistem](#cara-mengubah-pengaturan-sistem)
8. [Cara Mengelola Master Data](#cara-mengelola-master-data)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Admin Sistem

Administrator sistem bertanggung jawab atas:
- Manajemen pengguna dan akses
- Keamanan sistem
- Pemeliharaan dan backup data
- Konfigurasi sistem
- Troubleshooting teknis

### Hak Akses Admin:

| Menu | Fungsi |
|------|--------|
| **User Management** | Kelola pengguna |
| **Role & Permission** | Atur hak akses |
| **Audit Trail** | Log aktivitas |
| **Backup/Restore** | Backup database |
| **System Config** | Pengaturan sistem |
| **Master Data** | Data referensi |

---

## Cara Mengelola Pengguna

### Akses Menu Pengguna:

1. Login sebagai **Administrator**
2. Klik menu **"Admin"** → **"Pengguna"**
3. Lihat daftar semua pengguna

### Membuat Pengguna Baru:

#### 1. Tambah User:

1. Klik **"+ Tambah Pengguna"**
2. Isi form data pengguna:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **Nama Lengkap** | Nama asli | Dr. Budi Santoso |
| **Username** | Login ID | dr.budi |
| **Email** | Email aktif | budi@rs-sakitku.id |
| **Password** | Password awal | Generate otomatis |
| **No. Telepon** | HP aktif | 081234567890 |
| **Unit/Bagian** | Tempat bertugas | Poli Umum |
| **Jabatan** | Posisi | Dokter |

3. Pilih **Role** (peran):
   - Admin
   - Dokter
   - Perawat
   - Apoteker
   - Kasir
   - Petugas Pendaftaran
   - dll

4. Klik **"Simpan"**
5. Sistem akan:
   - Buat akun
   - Kirim email notifikasi (jika diaktifkan)
   - Generate password sementara

### Screenshot:
![Tambah Pengguna](../images/tambah-pengguna.png)

### Mengedit Pengguna:

1. Cari pengguna di daftar
2. Klik icon **"Edit"**
3. Ubah data yang diperlukan
4. Klik **"Simpan"**

### Reset Password:

Jika pengguna lupa password:

1. Cari pengguna
2. Klik **"Reset Password"**
3. Pilih metode:
   - **Generate otomatis**: Sistem buat password baru
   - **Input manual**: Admin tentukan password
4. Password baru bisa:
   - Dikirim via email
   - Diberikan langsung ke user
5. User harus ganti password saat pertama login

### Menonaktifkan Pengguna:

1. Cari pengguna
2. Klik **"Nonaktifkan"**
3. Pilih alasan:
   - Resign
   - Cuti panjang
   - Pindah unit
   - Pelanggaran
4. Klik **"Konfirmasi"**
5. User tidak bisa login lagi

### Mengaktifkan Kembali:

1. Filter daftar: **"Nonaktif"**
2. Cari pengguna
3. Klik **"Aktifkan"**
4. User bisa login kembali

---

## Cara Mengelola Role dan Permission

### Pengertian Role:

Role adalah kumpulan permission (hak akses) untuk tugas tertentu.

### Role Default:

| Role | Deskripsi | Permission Utama |
|------|-----------|------------------|
| **Superadmin** | Admin tertinggi | Semua akses |
| **Admin** | Administrator | Kelola user, konfigurasi |
| **Dokter** | Tenaga medis | EMR, resep, order lab |
| **Perawat** | Tenaga keperawatan | Askep, CPPT, TTV |
| **Apoteker** | Staf farmasi | Resep, stok obat |
| **Kasir** | Staf keuangan | Pembayaran, refund |
| **Pendaftaran** | Front office | Admisi, antrian |

### Membuat Role Baru:

1. Klik **"Admin"** → **"Role"**
2. Klik **"+ Tambah Role"**
3. Isi:
   - Nama role
   - Deskripsi
   - Level akses
4. Pilih **permissions**:

```
Modul Pendaftaran
  [x] Lihat pasien
  [x] Tambah pasien
  [x] Edit pasien
  [ ] Hapus pasien

Modul Rekam Medis
  [x] Lihat EMR
  [x] Edit EMR
  [ ] Hapus EMR
  [ ] Finalisasi EMR

Modul Farmasi
  [ ] Lihat resep
  [ ] Proses resep
```

5. Klik **"Simpan"**

### Screenshot:
![Kelola Role](../images/kelola-role.png)

### Menetapkan Role ke User:

1. Edit pengguna
2. Pilih tab **"Role"**
3. Centang role yang diinginkan
4. User bisa memiliki **multiple role**
5. Klik **"Simpan"**

### Permission Detail:

| Permission | Keterangan |
|------------|------------|
| **View** | Melihat data |
| **Create** | Membuat data baru |
| **Edit** | Mengubah data |
| **Delete** | Menghapus data |
| **Approve** | Menyetujui atau menolak |
| **Export** | Export data |
| **Print** | Mencetak |

---

## Cara Melihat Audit Trail

### Pengertian Audit Trail:

Audit trail adalah catatan log semua aktivitas pengguna di sistem untuk keamanan dan accountability.

### Akses Audit Trail:

1. Klik **"Admin"** → **"Audit Trail"**
2. Lihat daftar log aktivitas

### Informasi Audit:

| Kolom | Keterangan |
|-------|------------|
| **Waktu** | Timestamp kejadian |
| **User** | Pengguna yang melakukan |
| **Aksi** | Create/Update/Delete/Login/Logout |
| **Modul** | Bagian sistem yang diakses |
| **Data** | Detail data yang diubah |
| **IP Address** | Lokasi akses |

### Filter Audit:

1. **Filter Tanggal**: Range waktu
2. **Filter User**: Pengguna spesifik
3. **Filter Aksi**: Tipe aktivitas
4. **Filter Modul**: Bagian sistem

### Contoh Log:

```
-----------------------------------------------------------------
                         AUDIT TRAIL
-----------------------------------------------------------------

2026-02-08 14:30:15 | dr.budi | LOGIN | IP: 192.168.1.100
2026-02-08 14:32:08 | dr.budi | CREATE | EMR | RM: 00123
2026-02-08 14:35:22 | dr.budi | UPDATE | Resep | ID: 456
2026-02-08 14:40:10 | dr.budi | FINALIZE | EMR | RM: 00123
2026-02-08 15:00:05 | dr.budi | LOGOUT | IP: 192.168.1.100

2026-02-08 15:15:30 | admin | RESET_PASSWORD | User: ani
2026-02-08 15:20:45 | siti | CREATE | Pasien | RM: 00124

-----------------------------------------------------------------
```

### Export Audit:

1. Filter log yang diinginkan
2. Klik **"Export"**
3. Pilih format (Excel/PDF)
4. Gunakan untuk:
   - Investigasi insiden
   - Audit internal
   - Pelaporan keamanan

### Screenshot:
![Audit Trail](../images/audit-trail.png)

---

## Cara Backup Database

### Jenis Backup:

| Jenis | Frekuensi | Kegunaan |
|-------|-----------|----------|
| **Full Backup** | Harian | Backup lengkap semua data |
| **Incremental** | Tiap jam | Perubahan sejak backup terakhir |
| **Manual** | Sesuai kebutuhan | Sebelum update besar |

### Backup Manual:

#### 1. Melalui Sistem:

1. Klik **"Admin"** → **"Backup"**
2. Pilih **jenis backup**:
   - Full Database
   - Tabel Tertentu
   - File Attachment

3. Pilih **metode**:
   - Download ke lokal
   - Simpan ke server
   - Cloud storage

4. Klik **"Mulai Backup"**
5. Tunggu proses selesai
6. Simpan file backup dengan aman

#### 2. Melalui Command Line (Server):

```bash
# Backup MySQL/MariaDB
mysqldump -u username -p database_name > backup_YYYYMMDD_HHMMSS.sql

# Backup dengan kompresi
mysqldump -u username -p database_name | gzip > backup_YYYYMMDD_HHMMSS.sql.gz
```

### Jadwal Backup Otomatis:

Backup otomatis sebaiknya diatur:
- **Harian**: Pukul 02:00 WIB
- **Mingguan**: Hari Minggu pukul 03:00 WIB
- **Bulanan**: Tanggal 1 pukul 04:00 WIB

### Verifikasi Backup:

1. Cek ukuran file backup
2. Coba restore ke database test
3. Verifikasi integritas data
4. Dokumentasikan hasil backup

### Screenshot:
![Backup Database](../images/backup-database.png)

### Penyimpanan Backup:

| Lokasi | Keterangan |
|--------|------------|
| **Local Server** | RAID/mirror disk |
| **External Storage** | HDD eksternal |
| **Network Storage** | NAS/SAN |
| **Cloud** | AWS S3, Google Cloud, dll |

**3-2-1 Rule**:
- 3 copy data
- 2 media berbeda
- 1 offsite/cloud

---

## Cara Restore Database

### Kapan Restore Diperlukan:

- Data corrupt
- Kesalahan hapus data
- Migrasi server
- Setup environment baru

### Langkah Restore:

#### 1. Persiapan:

1. **Backup dulu** database saat ini (jika masih ada data penting)
2. Pastikan file backup valid
3. Siapkan waktu maintenance window
4. Notifikasi user: sistem akan down

#### 2. Melalui Sistem:

1. Klik **"Admin"** → **"Restore"**
2. Pilih **sumber restore**:
   - Upload file backup
   - Pilih dari daftar backup

3. Verifikasi file:
   - Cek integritas
   - Cek versi compatibility

4. Pilih **opsi restore**:
   - Full restore (ganti semua)
   - Partial restore (tabel tertentu)

5. Klik **"Mulai Restore"**
6. Tunggu proses selesai
7. Verifikasi hasil

#### 3. Melalui Command Line:

```bash
# Restore dari SQL file
mysql -u username -p database_name < backup_file.sql

# Restore dari file terkompresi
gunzip < backup_file.sql.gz | mysql -u username -p database_name
```

### Verifikasi Restore:

1. Cek jumlah tabel
2. Cek jumlah record di tabel utama
3. Coba login ke aplikasi
4. Test fungsi-fungsi utama
5. Cek log error

### Rollback Plan:

Jika restore gagal:
1. Stop proses restore
2. Restore dari backup yang lebih baru/lama
3. Atau kembalikan ke database sebelumnya

### Screenshot:
![Restore Database](../images/restore-database.png)

---

## Cara Mengubah Pengaturan Sistem

### Akses Pengaturan:

1. Klik **"Admin"** → **"Pengaturan"**
2. Lihat kategori pengaturan

### Kategori Pengaturan:

#### 1. Umum:

| Setting | Keterangan | Contoh |
|---------|------------|--------|
| **Nama RS** | Nama rumah sakit | RS RumahSakitKu |
| **Alamat** | Alamat lengkap | Jl. Sehat No. 123 |
| **Telepon** | Kontak | (021) 1234567 |
| **Email** | Email RS | info@rs-sakitku.id |
| **Logo** | Logo RS | Upload file |

#### 2. Sistem:

| Setting | Keterangan | Default |
|---------|------------|---------|
| **Timezone** | Zona waktu | Asia/Jakarta |
| **Bahasa** | Bahasa default | Indonesia |
| **Format Tanggal** | DD/MM/YYYY | DD/MM/YYYY |
| **Mata Uang** | Currency | IDR |

#### 3. Keamanan:

| Setting | Keterangan | Rekomendasi |
|---------|------------|-------------|
| **Session Timeout** | Auto logout | 30 menit |
| **Password Min Length** | Minimal karakter password | 8 |
| **Password Complexity** | Syarat password | Huruf + Angka + Symbol |
| **Max Login Attempt** | Batas gagal login | 3 kali |
| **Enforce HTTPS** | Wajib HTTPS | Ya |

#### 4. Notifikasi:

| Setting | Keterangan | Status |
|---------|------------|--------|
| **Email Notification** | Kirim email | Aktif |
| **SMS Notification** | Kirim SMS | Aktif |
| **Push Notification** | Notifikasi browser | Aktif |

### Simpan Pengaturan:

1. Ubah nilai setting
2. Klik **"Simpan"**
3. Beberapa perubahan mungkin perlu restart aplikasi

### Screenshot:
![Pengaturan Sistem](../images/pengaturan-sistem.png)

---

## Cara Mengelola Master Data

### Pengertian Master Data:

Master data adalah data referensi yang digunakan di seluruh sistem.

### Jenis Master Data:

#### 1. Data Medis:

| Master | Contoh Data |
|--------|-------------|
| **Diagnosis (ICD10)** | Semua kode penyakit |
| **Tindakan (ICD9)** | Semua kode prosedur |
| **Obat** | Formularium RS |
| **Laboratorium** | Paket dan parameter lab |
| **Radiologi** | Jenis pemeriksaan |

#### 2. Data Tarif:

| Master | Contoh |
|--------|--------|
| **Tarif Tindakan** | Bedah minor, mayor |
| **Tarif Kamar** | VIP, Kelas I, II, III |
| **Tarif Visite** | Dokter spesialis, umum |
| **Tarif Admin** | Kartu, administrasi |

#### 3. Data Organisasi:

| Master | Contoh |
|--------|--------|
| **Unit/Bagian** | Poliklinik, Rawat Inap |
| **Jabatan** | Dokter, Perawat, dll |
| **Ruangan** | Daftar kamar |
| **Dokter** | Daftar dokter |

### Tambah Master Data:

1. Klik **"Admin"** → **"Master Data"**
2. Pilih kategori master
3. Klik **"+ Tambah"**
4. Isi form sesuai jenis data
5. Klik **"Simpan"**

### Edit Master Data:

1. Cari data yang akan diedit
2. Klik **"Edit"**
3. Ubah nilai
4. Klik **"Simpan"**

**Perhatian**: Edit master data bisa mempengaruhi data historis. Pertimbangkan:
- Buat baru vs edit
- Impact ke laporan
- Dokumentasikan perubahan

### Import Master Data:

Untuk data dalam jumlah besar:

1. Siapkan file **Excel/CSV** dengan format template
2. Klik **"Import"**
3. Upload file
4. Preview data
5. Klik **"Konfirmasi Import"**

### Screenshot:
![Master Data](../images/master-data.png)

---

## Tips dan Troubleshooting

### Best Practices Admin:

1. **Keamanan**:
   - Ganti password admin secara berkala
   - Jangan bagikan akun admin
   - Aktifkan 2FA jika tersedia
   - Monitor audit trail secara rutin

2. **Backup**:
   - Verifikasi backup berjalan otomatis
   - Test restore secara berkala
   - Simpan backup di lokasi terpisah

3. **Maintenance**:
   - Monitor log error
   - Update sistem jika ada patch
   - Bersihkan log lama secara berkala

### Troubleshooting:

#### 1. User Tidak Bisa Login

**Penyebab**:
- Password salah
- Akun terkunci
- Akun nonaktif
- Browser cache

**Solusi**:
1. Reset password
2. Cek status akun
3. Clear browser cache
4. Coba browser lain

#### 2. Permission Tidak Berfungsi

**Solusi**:
1. Cek role user
2. Clear cache aplikasi
3. Logout dan login ulang
4. Cek konflik multiple role

#### 3. Backup Gagal

**Penyebab**:
- Disk penuh
- Koneksi database putus
- File backup corrupt

**Solusi**:
1. Cek free space disk
2. Restart service database
3. Coba backup manual
4. Periksa log error

#### 4. Restore Gagal

**Penyebab**:
- File backup corrupt
- Versi tidak compatible
- Database sedang digunakan

**Solusi**:
1. Verifikasi integritas file
2. Pastikan versi sama
3. Stop aplikasi sebelum restore
4. Gunakan file backup lain

#### 5. Sistem Lambat

**Solusi**:
1. Cek resource server (CPU, RAM)
2. Optimasi database (index, query)
3. Bersihkan data lama
4. Scale up server jika perlu

### Kontak Eskalasi:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Keamanan data | IT Security Ext. 7777 |
| Vendor SIMRS | Support Vendor |
| Emergency 24/7 | Hotline: 0812-XXXX-XXXX |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
