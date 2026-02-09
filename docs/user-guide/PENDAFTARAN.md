# Panduan Modul Pendaftaran

Panduan lengkap untuk menggunakan modul Pendaftaran SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Pendaftaran](#pengenalan-modul-pendaftaran)
2. [Cara Mendaftarkan Pasien Baru](#cara-mendaftarkan-pasien-baru)
3. [Cara Mencari Pasien Lama](#cara-mencari-pasien-lama)
4. [Cara Mendaftarkan Kunjungan](#cara-mendaftarkan-kunjungan)
5. [Cara Mengatur Antrian](#cara-mengatur-antrian)
6. [Cara Membatalkan Kunjungan](#cara-membatalakan-kunjungan)
7. [Cara Mencetak Kartu Pasien](#cara-mencetak-kartu-pasien)
8. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Pendaftaran

Modul Pendaftaran adalah pintu masuk utama untuk semua pasien yang akan berkunjung ke rumah sakit. Modul ini menangani:

- Registrasi pasien baru
- Pencarian data pasien lama
- Pendaftaran kunjungan (Rawat Jalan, Rawat Inap, IGD)
- Manajemen antrian poliklinik
- Cetak kartu pasien

### Akses Modul Pendaftaran:

1. Login ke SIMRS
2. Klik menu **"Pendaftaran"** di sidebar
3. Pilih submenu sesuai kebutuhan:
   - **Data Pasien** - Kelola master data pasien
   - **Kunjungan** - Daftarkan kunjungan pasien
   - **Antrian** - Monitor dan kelola antrian
   - **Cetak Kartu** - Cetak ulang kartu pasien

---

## Cara Mendaftarkan Pasien Baru

### Langkah-langkah:

1. **Login** ke sistem SIMRS dengan akun petugas pendaftaran

2. Klik menu **"Pendaftaran"** → **"Data Pasien"** di sidebar

3. Klik tombol **"+ Pasien Baru"** di pojok kanan atas
   
4. Isi form **"Data Pasien Baru"** dengan lengkap:

   #### A. Data Pribadi (Wajib Diisi):
   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | No. KTP/NIK | 16 digit NIK | 3175091234567890 |
   | Nama Lengkap | Sesuai KTP | AHMAD SUSANTO |
   | Tempat Lahir | Kota kelahiran | JAKARTA |
   | Tanggal Lahir | Format: DD/MM/YYYY | 15/08/1985 |
   | Jenis Kelamin | Pilih L/P | Laki-laki |
   | Golongan Darah | Pilih A/B/AB/O | O |
   | Status Nikah | Pilih status | Menikah |
   | Agama | Pilih agama | Islam |
   | Pendidikan | Pendidikan terakhir | S1 |
   | Pekerjaan | Pekerjaan saat ini | Pegawai Swasta |

   #### B. Data Alamat (Wajib Diisi):
   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | Alamat Lengkap | Alamat sesuai KTP | Jl. Mawar No. 123 |
   | RT/RW | Nomor RT/RW | 001/002 |
   | Kelurahan/Desa | Nama kelurahan | Kel. Cempaka |
   | Kecamatan | Nama kecamatan | Kec. Cempaka Putih |
   | Kota/Kabupaten | Nama kota | Jakarta Pusat |
   | Provinsi | Nama provinsi | DKI Jakarta |
   | Kode POS | Kode pos | 10510 |

   #### C. Data Kontak:
   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | No. Telepon/HP | Nomor aktif | 081234567890 |
   | Email | Email (opsional) | ahmad@email.com |

   #### D. Data Keluarga/Kontak Darurat:
   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | Nama Keluarga | Nama kontak darurat | SRI SUSANTI |
   | Hubungan | Hubungan dengan pasien | Istri |
   | No. Telepon | Nomor yang bisa dihubungi | 081298765432 |

   #### E. Data Penjamin (Opsional):
   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | Jenis Penjamin | Umum/BPJS/Asuransi | BPJS |
   | No. Kartu BPJS | Jika penjamin BPJS | 0001234567890 |
   | Kelas Rawat | Kelas BPJS | Kelas I |
   | Faskes Tingkat I | Faskes rujukan | Puskesmas Cempaka |

5. **Upload Foto Pasien** (opsional):
   - Klik tombol **"Pilih Foto"**
   - Pilih file foto dari komputer
   - Format: JPG/PNG
   - Ukuran maksimal: 2 MB
   - Klik **"Upload"**

6. **Klik tombol "Simpan"** untuk menyimpan data

7. Sistem akan otomatis generate **Nomor Rekam Medis (No. RM)**:
   - Format: 6 digit angka (contoh: 000123)
   - No. RM bersifat unik dan permanen

8. **Cetak Kartu Pasien** dengan klik tombol **"Cetak Kartu"**
   - Pastikan printer sudah terhubung
   - Gunakan kertas kartu pasien (ukuran sesuai template)

### Screenshot:
![Form Pasien Baru](../images/pasien-baru.png)

### Tips Mengisi Data Pasien:

- ✅ Pastikan NIK valid (16 digit)
- ✅ Verifikasi nama sesuai KTP
- ✅ Tulis nama dengan HURUF KAPITAL
- ✅ Pastikan nomor telepon aktif
- ✅ Isi data kontak darurat untuk keamanan
- ✅ Cek kembali sebelum menyimpan

### Validasi Data:

Sistem akan melakukan validasi otomatis:
- NIK harus 16 digit angka
- Nomor telepon minimal 10 digit
- Email harus format valid (jika diisi)
- Tanggal lahir tidak boleh di masa depan

---

## Cara Mencari Pasien Lama

### Metode Pencarian:

#### 1. Pencarian Cepat (Header):

1. Klik **search box** di header/navbar
2. Ketik **No. RM**, **NIK**, atau **Nama Pasien**
3. Tekan **Enter** atau klik icon 🔍
4. Pilih pasien dari hasil pencarian

#### 2. Pencarian Lengkap (Menu Data Pasien):

1. Klik menu **"Pendaftaran"** → **"Data Pasien"**
2. Gunakan **filter pencarian** di atas tabel:
   - **No. RM**: Masukkan nomor rekam medis
   - **NIK**: Masukkan 16 digit NIK
   - **Nama**: Masukkan nama pasien (bisa sebagian)
   - **Tanggal Lahir**: Pilih range tanggal lahir
   - **Alamat**: Cari berdasarkan alamat

3. **Klik "Cari"** untuk menampilkan hasil

4. **Klik nama pasien** untuk melihat detail lengkap

### Fitur Pencarian:

| Fitur | Cara Menggunakan |
|-------|------------------|
| Pencarian Global | Ketik di search box header |
| Filter Lanjutan | Gunakan form filter di halaman Data Pasien |
| Pencarian Parsial | Ketik sebagian nama (min. 3 huruf) |
| Filter Tanggal | Pilih range tanggal lahir |
| Export Hasil | Klik "Export" untuk Excel/PDF |

### Tips Pencarian:

- 🔍 Gunakan **NIK** untuk hasil paling akurat
- 🔍 Jika nama sulit dieja, gunakan **No. RM**
- 🔍 Untuk nama mirip, cek **tanggal lahir** dan **alamat**
- 🔍 Pasien dengan nama sama akan ditandai

### Hasil Tidak Ditemukan?

Jika pasien tidak ditemukan:
1. Periksa kembali ejaan nama
2. Coba variasi penulisan nama
3. Coba cari dengan NIK
4. Jika memang belum terdaftar → [Daftarkan sebagai pasien baru](#cara-mendaftarkan-pasien-baru)

---

## Cara Mendaftarkan Kunjungan

### Langkah-langkah Pendaftaran Kunjungan:

#### A. Kunjungan Rawat Jalan:

1. **Cari pasien** (baru atau lama)
   - Jika pasien baru: [Daftarkan terlebih dahulu](#cara-mendaftarkan-pasien-baru)
   - Jika pasien lama: [Cari data pasien](#cara-mencari-pasien-lama)

2. Klik tombol **"+ Kunjungan"** atau **"Daftar Kunjungan"**

3. **Pilih jenis kunjungan**: Rawat Jalan

4. Isi form kunjungan:

   | Field | Keterangan | Contoh |
   |-------|------------|--------|
   | Tanggal Kunjungan | Otomatis hari ini | 08/02/2026 |
   | Poliklinik Tujuan | Pilih poli | Poli Umum |
   | Dokter Tujuan | Pilih dokter (opsional) | dr. Budi |
   | Cara Bayar | Umum/BPJS/Asuransi | BPJS |
   | No. Rujukan | Jika ada rujukan | 12345/RS/II/2026 |
   | Keluhan Utama | Keluhan pasien | Sakit kepala |

5. **Klik "Simpan"**

6. Sistem akan:
   - Generate **Nomor Antrian**
   - Generate **No. Registrasi**
   - Mencetak **Bukti Pendaftaran** (opsional)

7. Berikan bukti pendaftaran kepada pasien

#### B. Kunjungan Rawat Inap:

1. Cari/daftarkan pasien
2. Pilih **"+ Kunjungan"** → **"Rawat Inap"**
3. Isi form:
   - Kelas perawatan (VIP/Kelas I/II/III)
   - Perkiraan lama perawatan
   - Diagnosa awal
   - DPJP (Dokter Penanggung Jawab Pelayanan)
4. Sistem akan mencari kamar yang tersedia
5. Pilih kamar dan bed
6. Simpan dan cetak bukti admisi

#### C. Kunjungan IGD:

1. Cari/daftarkan pasien
2. Pilih **"+ Kunjungan"** → **"IGD"**
3. Isi data:
   - Cara datang (sendiri/dibawa/diarahkan)
   - Keluhan utama
   - Keadaan umum
4. Sistem akan otomatis mengarahkan ke proses triase

### Screenshot:
![Form Kunjungan](../images/kunjungan.png)

### Format Nomor Antrian:

| Poliklinik | Format Contoh | Keterangan |
|------------|---------------|------------|
| Umum | U-001 | U = Umum |
| Gigi | G-005 | G = Gigi |
| Anak | A-003 | A = Anak |
| IGD | I-012 | I = IGD |

### Status Kunjungan:

| Status | Warna | Keterangan |
|--------|-------|------------|
| Menunggu | 🟡 Kuning | Belum dipanggil |
| Proses | 🔵 Biru | Sedang diperiksa |
| Selesai | 🟢 Hijau | Pemeriksaan selesai |
| Batal | 🔴 Merah | Dibatalkan |

---

## Cara Mengatur Antrian

### Monitor Antrian:

1. Klik menu **"Pendaftaran"** → **"Antrian"**
2. Pilih **poliklinik** yang ingin dimonitor
3. Lihat daftar antrian real-time

### Fitur Manajemen Antrian:

| Fitur | Cara Menggunakan |
|-------|------------------|
| **Panggil Antrian** | Klik tombol "Panggil" atau "🔊" |
| **Lewati Antrian** | Klik "Lewati" jika pasien tidak hadir |
| **Selesai** | Klik "Selesai" setelah pemeriksaan |
| **Pindah Poli** | Klik "Pindah" untuk rujukan internal |
| **Prioritas** | Klik "Prioritas" untuk pasien darurat |

### Display Antrian (TV):

Untuk menampilkan antrian di TV/Monitor:

1. Buka browser di komputer/TV
2. Akses URL: `https://simrs.rumahsakitku.ac.id/display-antrian`
3. Atau klik menu **"Display Antrian"** → **"Fullscreen"**

### Pengaturan Suara Panggilan:

1. Klik icon **"🔊"** di monitor antrian
2. Pilih **bahasa**: Indonesia/Ingris
3. Atur **volume**
4. Pilih **jenis suara** (pria/wanita)

### Screenshot:
![Monitor Antrian](../images/antrian.png)

---

## Cara Membatalkan Kunjungan

### Skenario Pembatalan:

#### A. Pembatalan oleh Petugas Pendaftaran:

1. Buka menu **"Pendaftaran"** → **"Kunjungan"**
2. Cari kunjungan yang akan dibatalkan
3. Klik tombol **"Batal"** (icon 🗑️)
4. Pilih **alasan pembatalan**:
   - Pasien tidak datang
   - Pasien membatalkan
   - Kesalahan input
   - Lainnya
5. Isi **keterangan** (opsional)
6. Klik **"Konfirmasi Batal"**

#### B. Pembatalan Pasien yang Sudah Diperiksa:

> ⚠️ **PERHATIAN**: Kunjungan yang sudah selesai diperiksa tidak dapat dibatalkan. Hubungi supervisor jika terjadi kesalahan.

### Dampak Pembatalan:

| Aspek | Dampak |
|-------|--------|
| Nomor Antrian | Dilewati dan tidak dipanggil |
| Stok Obat | Tidak ada perubahan (jika belum dispense) |
| Billing | Dihapus dari tagihan |
| Laporan | Tercatat sebagai "Kunjungan Batal" |

### Tips:

- ⏰ Batalkan sebelum pasien dipanggil jika memungkinkan
- 📝 Catat alasan pembatalan untuk evaluasi
- 🔄 Jika pasien ingin daftar ulang, buat kunjungan baru

---

## Cara Mencetak Kartu Pasien

### Cetak Kartu untuk Pasien Baru:

Kartu pasien otomatis tercetak saat pendaftaran pasien baru. Namun jika perlu cetak ulang:

1. Buka **Data Pasien**
2. Cari pasien yang bersangkutan
3. Klik icon **"🖨️ Cetak Kartu"**
4. Pilih **template kartu** (jika ada pilihan)
5. Klik **"Print"**
6. Pilih printer dan klik **"OK"**

### Pengaturan Printer Kartu:

#### Untuk Printer Kartu Khusus:

1. Buka **Pengaturan Printer** di Windows
2. Pilih printer kartu pasien
3. Atur ukuran kertas sesuai kartu:
   - Standar: 86mm x 54mm (ukuran ATM)
   - Custom: Sesuai template rumah sakit
4. Atur margin:
   - Atas: 0 mm
   - Bawah: 0 mm
   - Kiri: 0 mm
   - Kanan: 0 mm

### Cetak Ulang Kartu Hilang:

1. Verifikasi identitas pasien (KTP/identitas lain)
2. Buka data pasien
3. Klik **"Cetak Ulang Kartu"**
4. Sistem akan mencatat log cetak ulang
5. Cetak dan berikan ke pasien

### Screenshot:
![Cetak Kartu](../images/cetak-kartu.png)

### Masalah Cetak dan Solusi:

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| Kartu tidak terprint | Printer offline | Cek koneksi printer |
| Hasil cetak tidak rata | Kertas tidak pas | Sesuaikan ukuran kertas |
| Barcode tidak terbaca | Resolusi rendah | Atur kualitas print ke "High" |

---

## Tips dan Troubleshooting

### Tips Efisiensi Kerja:

1. **Gunakan Shortcuts**:
   - `F2` - Pencarian cepat pasien
   - `F3` - Form pasien baru
   - `F5` - Refresh data
   - `Ctrl + P` - Cetak

2. **Bookmark Pasien Sering Datang**:
   - Tambahkan ke "Favorit" untuk akses cepat

3. **Template Pendaftaran**:
   - Gunakan fitur "Template" untuk pasien rutin

4. **Multi-Monitor**:
   - Gunakan 2 monitor: 1 untuk input, 1 untuk display antrian

### Troubleshooting Umum:

#### 1. NIK Sudah Terdaftar

**Gejala**: Muncul pesan "NIK sudah terdaftar"

**Solusi**:
1. Cari pasien dengan NIK tersebut
2. Verifikasi data dengan pasien
3. Jika benar pasien yang sama → gunakan data lama
4. Jika berbeda orang → hubungi supervisor (kemungkinan NIK salah input)

#### 2. Printer Tidak Merespon

**Gejala**: Tidak bisa cetak kartu/bukti pendaftaran

**Solusi**:
1. Cek koneksi printer (USB/Network)
2. Pastikan printer status "Ready"
3. Restart printer spooler:
   - Windows: Services → Print Spooler → Restart
4. Coba cetak test page dari Windows

#### 3. Nomor Antrian Tidak Muncul

**Gejala**: Pasien daftar tapi tidak masuk antrian

**Solusi**:
1. Refresh halaman antrian (F5)
2. Cek filter poliklinik (pilih "Semua" atau poli spesifik)
3. Verifikasi tanggal kunjungan (hari ini)
4. Cek status kunjungan (mungkin sudah selesai/batal)

#### 4. Sistem Lambat Saat Pencarian

**Gejala**: Pencarian pasien lama

**Solusi**:
1. Gunakan filter yang spesifik (NIK lebih cepat dari nama)
2. Batasi range tanggal
3. Bersihkan cache browser (Ctrl + Shift + Delete)
4. Pastikan koneksi internet stabil

#### 5. Data Pasien Tidak Tersimpan

**Gejala**: Error saat klik "Simpan"

**Solusi**:
1. Cek field yang bertanda * (wajib diisi)
2. Pastikan NIK valid (16 digit)
3. Cek format tanggal (DD/MM/YYYY)
4. Cek koneksi ke server
5. Coba refresh dan ulangi pengisian

### Checklist Harian Petugas Pendaftaran:

- [ ] Nyalakan komputer dan printer
- [ ] Login ke sistem SIMRS
- [ ] Verifikasi printer kartu siap
- [ ] Cek stok kertas kartu pasien
- [ ] Verifikasi nomor antrian poli
- [ ] Cek antrian yang tertunda dari hari sebelumnya
- [ ] Backup data penting jika diperlukan

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Alur pendaftaran | Supervisor Pendaftaran Ext. 1234 |
| Masalah BPJS | BPJS Helpdesk Ext. 5678 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
