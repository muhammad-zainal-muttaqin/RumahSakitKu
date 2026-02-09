# Panduan Pengguna SIMRS RumahSakitKu

Selamat datang di **Panduan Pengguna SIMRS RumahSakitKu** - Sistem Informasi Manajemen Rumah Sakit yang terintegrasi dan lengkap.

---

## Daftar Isi

1. [Pengenalan SIMRS RumahSakitKu](#pengenalan-simrs-rumahsakitku)
2. [Persyaratan Sistem](#persyaratan-sistem)
3. [Cara Login](#cara-login)
4. [Navigasi Dashboard](#navigasi-dashboard)
5. [Panduan Perubahan Password](#panduan-perubahan-password)
6. [Panduan Logout](#panduan-logout)

---

## Pengenalan SIMRS RumahSakitKu

SIMRS RumahSakitKu adalah sistem informasi manajemen rumah sakit yang dirancang untuk membantu operasional rumah sakit secara menyeluruh, mulai dari pendaftaran pasien, rekam medis, farmasi, keuangan, rawat inap, IGD, hingga laporan manajemen.

### Modul-modul yang Tersedia:

| Modul | Deskripsi |
|-------|-----------|
| **Pendaftaran** | Manajemen data pasien dan pendaftaran kunjungan |
| **Rekam Medis** | Manajemen rekam medis elektronik (EMR) |
| **Farmasi** | Manajemen resep dan stok obat |
| **Keuangan/Kasir** | Manajemen tagihan dan pembayaran |
| **Rawat Inap** | Manajemen pasien rawat inap |
| **IGD** | Manajemen pasien gawat darurat |
| **Penunjang Medis** | Laboratorium dan radiologi |
| **Bedah Sentral** | Manajemen operasi dan Kamar Operasi |
| **Laporan** | Laporan manajemen dan RL |
| **Admin** | Manajemen pengguna dan pengaturan sistem |

### Fitur Unggulan:

- ✅ Rekam Medis Elektronik (EMR) berbasis SOAP dan CPPT
- ✅ Integrasi BPJS dan Asuransi
- ✅ Manajemen antrian terintegrasi
- ✅ Stok obat real-time
- ✅ Laporan RL otomatis (RL 1, RL 3, dll)
- ✅ Audit trail lengkap
- ✅ Multi-device dan responsive design

---

## Persyaratan Sistem

### Persyaratan Perangkat Klien:

| Komponen | Spesifikasi Minimum | Spesifikasi Direkomendasikan |
|----------|---------------------|------------------------------|
| **Browser** | Google Chrome 90+, Mozilla Firefox 88+ | Google Chrome terbaru |
| **Resolusi Layar** | 1366 x 768 | 1920 x 1080 atau lebih tinggi |
| **Koneksi Internet** | 2 Mbps | 10 Mbps atau lebih |
| **RAM** | 4 GB | 8 GB atau lebih |
| **Printer** | Printer dot matrix (untuk kwitansi) atau laser | Sesuai kebutuhan |

### Persyaratan Server (On-Premise):

- **OS**: Windows Server 2016/2019 atau Linux Ubuntu 20.04+
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **Database**: MySQL 8.0+ atau MariaDB 10.5+
- **PHP**: 8.1+
- **RAM**: 8 GB minimum (16 GB direkomendasikan)
- **Storage**: 100 GB SSD minimum

### Perangkat Tambahan yang Direkomendasikan:

- Scanner barcode untuk kartu pasien
- Fingerprint reader (untuk verifikasi identitas)
- Printer termal untuk label obat
- Printer dot matrix untuk kwitansi berlebarn

---

## Cara Login

### Langkah-langkah Login:

1. **Buka browser** (disarankan Google Chrome)
2. **Ketik alamat URL** aplikasi SIMRS RumahSakitKu di address bar:
   ```
   https://simrs.rumahsakitku.ac.id
   ```
   atau alamat IP server sesuai konfigurasi rumah sakit Anda.

3. **Halaman Login** akan muncul dengan form:
   - Username
   - Password
   - Captcha (jika diaktifkan)

4. **Masukkan Username** sesuai yang diberikan oleh admin IT

5. **Masukkan Password** dengan benar
   - Password bersifat case-sensitive (huruf besar/kecil berbeda)
   - Gunakan icon mata 👁️ untuk melihat password yang diketik

6. **Isi Captcha** (jika muncul)

7. **Klik tombol "Login"** atau tekan **Enter**

8. Sistem akan mengarahkan ke **Dashboard** sesuai role pengguna

### Screenshot:
![Halaman Login](../images/login.png)

### Tips Login:

- ✅ Pastikan Caps Lock tidak aktif
- ✅ Bookmark halaman login untuk akses cepat
- ✅ Jangan simpan password di browser komputer publik
- ✅ Gunakan "Ingat Saya" hanya di komputer pribadi

### Troubleshooting Login:

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| "Username atau password salah" | Typo atau case-sensitive | Periksa huruf besar/kecil, coba lagi |
| "Akun tidak aktif" | Akun dinonaktifkan | Hubungi admin IT |
| "Sesi habis" | Tidak aktif terlalu lama | Login ulang |
| Halaman tidak terbuka | Masalah koneksi/jaringan | Cek koneksi internet |

---

## Navigasi Dashboard

Setelah login berhasil, Anda akan melihat **Dashboard** yang merupakan pusat kontrol utama sistem.

### Komponen Dashboard:

#### 1. **Header/Navbar (Bagian Atas)**
- Logo Rumah Sakit
- Search box (pencarian cepat)
- Notifikasi (icon lonceng 🔔)
- Profil pengguna (dropdown menu)

#### 2. **Sidebar (Menu Samping Kiri)**
Menu yang tersedia sesuai role pengguna:

| Icon | Menu | Deskripsi |
|------|------|-----------|
| 🏠 | Dashboard | Halaman utama |
| 👤 | Pendaftaran | Data pasien & kunjungan |
| 📋 | Rekam Medis | EMR & CPPT |
| 💊 | Farmasi | Resep & stok obat |
| 💰 | Keuangan | Tagihan & pembayaran |
| 🛏️ | Rawat Inap | Admisi & pemulangan |
| 🚨 | IGD | Pasien gawat darurat |
| 🔬 | Penunjang | Lab & radiologi |
| 🏥 | Bedah Sentral | Jadwal operasi |
| 📊 | Laporan | RL & statistik |
| ⚙️ | Admin | Pengaturan sistem |

#### 3. **Content Area (Area Konten)**
Area utama untuk menampilkan:
- Ringkasan data (cards)
- Grafik dan statistik
- Tabel data
- Form input

#### 4. **Footer (Bagian Bawah)**
- Informasi versi aplikasi
- Copyright
- Waktu server

### Cara Navigasi:

1. **Klik menu di sidebar** untuk membuka modul
2. **Menu aktif** akan ditandai dengan warna berbeda
3. **Sub-menu** akan muncul saat mengklik menu induk
4. **Breadcrumb** di atas konten menunjukkan lokasi Anda saat ini

### Fitur Cepat Dashboard:

| Fitur | Cara Menggunakan |
|-------|------------------|
| Collapse Sidebar | Klik icon ☰ (hamburger) |
| Fullscreen | Klik icon ⛶ di header |
| Dark Mode | Klik icon 🌙 (jika tersedia) |
| Refresh Data | Klik icon 🔄 atau F5 |
| Cepat Cari Pasien | Gunakan search box di header |

### Widget Dashboard:

Dashboard menampilkan beberapa widget informatif:
- **Jumlah Pasien Hari Ini** (Rawat Jalan, Rawat Inap, IGD)
- **Antrian Aktif** (per poli)
- **Stok Obat Menipis**
- **Kamar Tersedia**
- **Aktivitas Terbaru**

---

## Panduan Perubahan Password

Demi keamanan, disarankan untuk mengganti password secara berkala (minimal 3 bulan sekali).

### Langkah-langkah Mengganti Password:

#### Melalui Profil Pengguna:

1. **Klik foto profil** di pojok kanan atas header
2. **Pilih "Profil"** atau **"Ganti Password"** dari dropdown
3. **Isi form ganti password**:
   - Password Lama
   - Password Baru
   - Konfirmasi Password Baru
4. **Klik "Simpan"** atau **"Update Password"**
5. **Konfirmasi berhasil** akan muncul
6. **Logout dan login kembali** dengan password baru

#### Melalui Menu Admin:

1. **Klik menu "Profil"** atau **"Pengaturan Akun"** di sidebar
2. **Pilih tab "Keamanan"** atau **"Password"**
3. **Isi form** seperti di atas
4. **Simpan perubahan**

### Screenshot:
![Form Ganti Password](../images/ganti-password.png)

### Persyaratan Password:

| Kriteria | Minimal | Direkomendasikan |
|----------|---------|------------------|
| Panjang | 8 karakter | 12+ karakter |
| Huruf Besar | 1 huruf | 2+ huruf |
| Huruf Kecil | 1 huruf | 2+ huruf |
| Angka | 1 angka | 2+ angka |
| Karakter Khusus | 1 karakter | 2+ karakter |

### Contoh Password Kuat:

- ✅ `RumahSakitKu@2024`
- ✅ `S1mR5#J4g4S3h4t`
- ❌ `password123` (terlalu umum)
- ❌ `12345678` (terlalu sederhana)

### Tips Keamanan Password:

1. **Jangan gunakan informasi pribadi** (tanggal lahir, nama, dll)
2. **Jangan bagikan password** dengan siapapun
3. **Jangan catat password** di tempat yang mudah ditemukan
4. **Gunakan password manager** jika diperlukan
5. **Ganti password segera** jika mencurigakan ada yang tahu

### Jika Lupa Password:

1. **Klik "Lupa Password"** di halaman login
2. **Masukkan email/username** yang terdaftar
3. **Cek email** untuk instruksi reset password
4. **Ikuti link reset** dan buat password baru
5. Atau **hubungi Admin IT** untuk reset manual

---

## Panduan Logout

Selalu logout setelah selesai menggunakan sistem, terutama di komputer bersama.

### Langkah-langkah Logout:

1. **Klik foto profil** di pojok kanan atas header
2. **Pilih "Logout"** dari dropdown menu
3. **Konfirmasi logout** (jika diminta)
4. Sistem akan mengarahkan ke **halaman login**
5. **Tutup browser** untuk keamanan tambahan

### Alternatif Logout:

- **Klik icon logout** (⏻ atau →) jika tersedia di header
- Atau klik **"Keluar"** di menu sidebar bawah

### Screenshot:
![Menu Logout](../images/logout.png)

### Pentingnya Logout:

| Risiko | Dampak |
|--------|--------|
| Data pasien diakses orang lain | Pelanggaran privasi |
| Perubahan data tanpa izin | Ketidakakuratan data |
| Penghapusan data penting | Kehilangan informasi |
| Penggunaan akun untuk tindakan tidak sah | Tindak pidana |

### Auto-Logout:

Sistem secara otomatis akan logout jika:
- Tidak ada aktivitas selama **30 menit** (idle timeout)
- Sesi login melebihi **8 jam**
- Mendeteksi aktivitas mencurigakan

---

## Dokumentasi Modul Lengkap

Untuk panduan detail masing-masing modul, silakan lihat:

| Dokumentasi | Link |
|-------------|------|
| Panduan Pendaftaran | [PENDAFTARAN.md](./PENDAFTARAN.md) |
| Panduan Rekam Medis | [REKAM_MEDIS.md](./REKAM_MEDIS.md) |
| Panduan Farmasi | [FARMASI.md](./FARMASI.md) |
| Panduan Keuangan | [KEUANGAN.md](./KEUANGAN.md) |
| Panduan Rawat Inap | [RAWAT_INAP.md](./RAWAT_INAP.md) |
| Panduan IGD | [IGD.md](./IGD.md) |
| Panduan Penunjang Medis | [PENUNJANG_MEDIS.md](./PENUNJANG_MEDIS.md) |
| Panduan Bedah Sentral | [BEDAH_SENTRAL.md](./BEDAH_SENTRAL.md) |
| Panduan Laporan | [LAPORAN.md](./LAPORAN.md) |
| Panduan Admin | [ADMIN.md](./ADMIN.md) |
| Panduan Troubleshooting | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) |

---

## Kontak Bantuan

Jika mengalami kesulitan, hubungi:

| Divisi | Kontak | Keterangan |
|--------|--------|------------|
| **IT Support** | Ext. 8888 / it@rumahsakitku.ac.id | Teknis sistem |
| **Helpdesk** | Ext. 9999 / helpdesk@rumahsakitku.ac.id | Umum |
| **Admin SIMRS** | Ext. 1234 | Akun & akses |

**Jam Operasional:** Senin - Jumat, 08:00 - 17:00 WIB

---

*Dokumen ini terakhir diperbarui: Februari 2026*

*Versi SIMRS RumahSakitKu: 1.0.0*
