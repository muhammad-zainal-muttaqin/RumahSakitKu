# Panduan Troubleshooting

Panduan penyelesaian masalah umum SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Masalah Login dan Solusi](#masalah-login-dan-solusi)
2. [Masalah Printer dan Solusi](#masalah-printer-dan-solusi)
3. [Masalah Jaringan dan Solusi](#masalah-jaringan-dan-solusi)
4. [Data Tidak Tersimpan](#data-tidak-tersimpan)
5. [Halaman Lambat Loading](#halaman-lambat-loading)
6. [Error BPJS Bridging](#error-bpjs-bridging)
7. [Cara Menghubungi IT Support](#cara-menghubungi-it-support)

---

## Masalah Login dan Solusi

### 1.1 Password Salah

**Gejala:**
- Muncul pesan "Username atau password salah"
- Tidak bisa masuk ke sistem

**Solusi:**

1. **Periksa Caps Lock**
   - Pastikan tombol Caps Lock tidak aktif
   - Password bersifat case-sensitive (huruf besar/kecil berbeda)

2. **Periksa Keyboard**
   - Coba ketik password di Notepad untuk melihat karakter yang muncul
   - Pastikan keyboard tidak ada masalah

3. **Reset Password**
   - Klik link "Lupa Password" di halaman login
   - Masukkan email/username terdaftar
   - Ikuti instruksi di email
   - Atau hubungi Admin IT untuk reset manual

4. **Coba Browser Lain**
   - Kadang browser menyimpan cache password lama
   - Coba login dengan browser berbeda (Chrome, Firefox, Edge)

### 1.2 Akun Tidak Aktif

**Gejala:**
- Muncul pesan "Akun tidak aktif" atau "Account disabled"

**Solusi:**

1. **Hubungi Supervisor**
   - Akun mungkin dinonaktifkan karena:
     - Cuti panjang
     - Pindah unit
     - Resign
     - Pelanggaran

2. **Aktivasi Ulang**
   - Admin IT dapat mengaktifkan kembali
   - Hubungi IT Support Ext. 8888

### 1.3 Session Expired / Sesi Habis

**Gejala:**
- Tiba-tiba keluar dari sistem
- Muncul pesan "Session expired" atau "Sesi habis"

**Solusi:**

1. **Login Ulang**
   - Sesi otomatis habis setelah 30 menit tidak aktif
   - Login kembali dengan username dan password

2. **Simpan Pekerjaan Secara Berkala**
   - Tekan Ctrl+S atau klik "Simpan" saat mengisi form
   - Hindari meninggalkan komputer lama saat form terbuka

3. **Hubungi Admin**
   - Jika sering terjadi padahal sedang aktif menggunakan
   - Mungkin perlu penyesuaian timeout di sistem

### 1.4 Halaman Login Tidak Terbuka

**Gejala:**
- Browser error: "This site can't be reached"
- Loading terus menerus

**Solusi:**

1. **Cek Koneksi Internet**
   - Coba buka website lain (google.com)
   - Jika tidak bisa, masalah jaringan lokal

2. **Cek Alamat URL**
   - Pastikan URL benar
   - Coba dengan http:// dan https://

3. **Clear Browser Cache**
   - Chrome: Ctrl+Shift+Delete → Clear browsing data
   - Pilih "Cached images and files"
   - Klik "Clear data"

4. **Hubungi IT Support**
   - Jika server SIMRS down
   - Ext. 8888 atau Hotline: 0812-XXXX-XXXX

---

## Masalah Printer dan Solusi

### 2.1 Printer Tidak Merespon

**Gejala:**
- Klik "Cetak" tapi tidak ada reaksi
- Tidak ada output dari printer

**Solusi:**

1. **Cek Koneksi Printer**
   - Pastikan kabel USB terhubung dengan baik
   - Jika network printer, cek koneksi LAN
   - Lampu indikator printer harus menyala (tidak merah)

2. **Cek Status Printer**
   - Windows: Start → Settings → Printers & Scanners
   - Pastikan printer status "Ready"
   - Bukan "Offline" atau "Error"

3. **Restart Printer**
   - Matikan printer
   - Tunggu 10 detik
   - Nyalakan kembali
   - Tunggu sampai siap

4. **Cek Antrian Print**
   - Buka "See what's printing"
   - Hapus dokumen yang stuck (cancel all documents)
   - Coba cetak ulang

### 2.2 Hasil Cetak Tidak Jelas/Blur

**Gejala:**
- Tulisan pudar
- Garis putus-putus
- Gambar tidak jelas

**Solusi:**

1. **Printer Dot Matrix**
   - Ganti ribbon (pita tinta)
   - Atur jarak head dengan kertas
   - Bersihkan head dengan kain lembab

2. **Printer Inkjet**
   - Ganti cartridge tinta
   - Jalankan "Clean Print Head" dari utility printer
   - Align print head

3. **Printer Laser**
   - Ganti toner cartridge
   - Kocok toner lama (sementara)
   - Bersihkan drum unit

4. **Printer Thermal**
   - Ganti kertas thermal
   - Bersihkan head thermal dengan alkohol
   - Atur density/contrast

### 2.3 Kertas Macet (Paper Jam)

**Gejala:**
- Lampu printer berkedip merah
- Pesan "Paper Jam" di layar komputer

**Solusi:**

1. **Matikan Printer**
   - Tekan tombol power
   - Lepas kabel power

2. **Buka Tutup Printer**
   - Buka cover sesuai manual printer
   - Cari lokasi kertas yang nyangkut

3. **Keluarkan Kertas**
   - Tarik kertas perlahan searah paper path
   - Jangan robek kertas
   - Pastikan tidak ada sisa kertas

4. **Tutup dan Nyalakan**
   - Tutup semua cover dengan benar
   - Nyalakan printer
   - Coba cetak test page

### 2.4 Printer Tidak Terdeteksi Sistem

**Gejala:**
- Printer tidak muncul di daftar printer
- "Printer not found"

**Solusi:**

1. **Install Driver**
   - Download driver sesuai model printer dari website
   - Install driver
   - Restart komputer

2. **Add Printer Manual**
   - Settings → Printers & Scanners → Add a printer
   - Pilih printer dari daftar atau add manual

3. **Update Driver**
   - Device Manager → Printers
   - Right click printer → Update driver

4. **Hubungi IPSRS**
   - Ext. 5555 untuk bantuan teknis printer

---

## Masalah Jaringan dan Solusi

### 3.1 Tidak Ada Koneksi Internet

**Gejala:**
- Icon jaringan ada tanda silang merah
- Tidak bisa buka website apa pun

**Solusi:**

1. **Cek Kabel/Lampu**
   - Lampu indikator LAN harus menyala/hijau
   - Jika merah/tidak menyala, kabel mungkin lepas

2. **Restart Perangkat Jaringan**
   - Matikan komputer
   - Restart switch/router (cabut dan pasang kabel power)
   - Tunggu 1 menit
   - Nyalakan komputer

3. **Cek IP Address**
   - Buka Command Prompt (cmd)
   - Ketik: `ipconfig`
   - Pastikan dapat IP address (bukan 169.254.x.x)

4. **Hubungi IT Network**
   - Ext. 8888 atau tim jaringan
   - Laporkan nomor IP komputer

### 3.2 Sistem Lambat/Jaringan Lemot

**Gejala:**
- Halaman loading lama
- Terputus-putus saat menggunakan

**Solusi:**

1. **Cek Penggunaan Bandwidth**
   - Banyak user streaming/download
   - Tanyakan ke tim IT

2. **Tutup Aplikasi Tidak Perlu**
   - Browser tab yang tidak digunakan
   - Aplikasi background

3. **Restart Komputer**
   - Bersihkan cache jaringan

4. **Gunakan Off-Peak Hours**
   - Hindari jam sibuk (pagi 08-10, siang 12-13)

### 3.3 SIMRS Error "Connection Timeout"

**Gejala:**
- Muncul pesan "Connection timeout"
- "Unable to connect to server"

**Solusi:**

1. **Refresh Halaman**
   - Tekan F5 atau Ctrl+R
   - Jika masih error, tunggu beberapa menit

2. **Cek Server Status**
   - Tanya rekan di unit lain:
     - Apakau mereka juga mengalami masalah?
   - Jika semua down = server bermasalah

3. **Hubungi IT Support Segera**
   - Ext. 8888 (24 jam)
   - Laporkan:
     - Waktu kejadian
     - Unit/lokasi
     - Jenis error

---

## Data Tidak Tersimpan

### 4.1 Error Saat Klik "Simpan"

**Gejala:**
- Muncul pesan error merah
- Data tidak tersimpan
- Tombol simpan tidak berfungsi

**Solusi:**

1. **Cek Field Wajib**
   - Pastikan semua field bertanda * terisi
   - Periksa format data (tanggal, email, dll)

2. **Cek Koneksi Internet**
   - Jika koneksi putus saat simpan, data hilang
   - Pastikan koneksi stabil

3. **Refresh dan Ulangi**
   - Copy data yang sudah diisi (Ctrl+A, Ctrl+C)
   - Refresh halaman (F5)
   - Isi ulang dan paste data
   - Klik simpan lagi

4. **Cek Validasi Data**
   - NIK harus 16 digit
   - Nomor telepon minimal 10 digit
   - Email format valid

### 4.2 Data Tersimpan Tapi Tidak Muncul

**Gejala:**
- Pesan "Simpan berhasil"
- Tapi data tidak ada di daftar

**Solusi:**

1. **Refresh Halaman**
   - F5 untuk refresh
   - Data mungkin belum terupdate di tampilan

2. **Cek Filter**
   - Pastikan filter tanggal benar
   - Reset filter untuk melihat semua data

3. **Cek di Modul Lain**
   - Mungkin tersimpan di unit/modul berbeda
   - Koordinasi dengan tim terkait

4. **Hubungi IT**
   - Jika data benar-benar hilang
   - Cek dari database

---

## Halaman Lambat Loading

### 5.1 SIMRS Sangat Lambat

**Gejala:**
- Loading berputar lama
- Halaman blank putih
- Timeout error

**Solusi:**

1. **Bersihkan Browser Cache**
   - Ctrl+Shift+Delete
   - Pilih "All time"
   - Centang "Cached images and files"
   - Clear data

2. **Tutup Tab Tidak Digunakan**
   - Semakin banyak tab, semakin lambat
   - Tutup aplikasi lain

3. **Restart Browser**
   - Tutup semua browser
   - Buka lagi

4. **Ganti Browser**
   - Coba Google Chrome (terbaik untuk SIMRS)
   - Atau Mozilla Firefox

5. **Restart Komputer**
   - Bersihkan memori

### 5.2 Hanya Satu Halaman yang Lambat

**Solusi:**

1. **Cek Data**
   - Jika data terlalu banyak (ribuan record)
   - Gunakan filter untuk membatasi tampilan

2. **Tunggu Proses**
   - Laporan besar memang butuh waktu
   - Jangan refresh berulang kali

3. **Export ke Excel**
   - Jika hanya perlu melihat data
   - Export saja ke Excel

---

## Error BPJS Bridging

### 6.1 Error " bridging gagal"

**Gejala:**
- Tidak bisa cek status BPJS
- SEP tidak bisa dibuat
- Pesan error saat koneksi ke BPJS

**Solusi:**

1. **Cek Koneksi Internet**
   - BPJS bridging butuh internet stabil
   - Test buka website bpjs-kesehatan.go.id

2. **Cek Jaringan BPJS**
   - Hubungi BPJS Helpdesk: 1500400
   - Tanyakan status server BPJS

3. **Cek Konfigurasi**
   - Cons ID dan Secret Key harus benar
   - Hubungi IT untuk verifikasi

4. **Coba Secara Manual**
   - Jika sistem error, gunakan:
     - Aplikasi Mobile JKN
     - Website bpjs-kesehatan.go.id
   - Sementara sampai bridging normal

### 6.2 "Peserta Tidak Ditemukan"

**Solusi:**

1. **Cek Nomor Kartu**
   - Pastikan 13 digit benar
   - Coba input tanpa spasi/titik

2. **Cek Faskes**
   - Pasien mungkin tidak terdaftar di RS ini
   - Suruh urut ke BPJS dulu

3. **Verifikasi Manual**
   - Cek ke website BPJS
   - atau Aplikasi Mobile JKN

---

## Cara Menghubungi IT Support

### Kontak IT Support:

| Kanal | Kontak | Keterangan |
|-------|--------|------------|
| **Telepon** | Ext. 8888 | Senin-Jumat, 08:00-17:00 |
| **Hotline** | 0812-XXXX-XXXX | 24 Jam (Emergency) |
| **Email** | it-support@rs-sakitku.id | Untuk non-urgent |
| **WhatsApp** | 0812-YYYY-YYYY | Chat support |
| **Lokasi** | Lantai 2, Ruang IT | Kunjungi langsung |

### Informasi yang Perlu Disiapkan:

Sebelum menghubungi IT, siapkan:

1. **Identitas**
   - Nama lengkap
   - Unit/bagian
   - Nomor telepon yang bisa dihubungi

2. **Detail Masalah**
   - Apa yang terjadi?
   - Kapan mulai terjadi?
   - Apa yang sudah dicoba?

3. **Informasi Teknis**
   - Nomor komputer/IP (jika tahu)
   - Username SIMRS
   - Browser yang digunakan
   - Screenshot error (jika bisa)

### Contoh Laporan:

```
Nama: Siti Aminah
Unit: Pendaftaran
Telepon: 081234567890

Masalah: Tidak bisa cetak kartu pasien
Waktu: Pukul 10:30 WIB, 08 Februari 2026

Detail:
- Printer Zebra ZD230
- Klik "Cetak" tidak ada reaksi
- Lampu printer hijau (ready)
- Sudah restart printer, masih sama

Screenshot: [lampirkan]
```

### Eskalasi:

Jika masalah urgent dan IT support tidak merespon:

1. **Hubungi Supervisor IT**
   - Ext. 8889

2. **Hubungi Kepala IT**
   - Ext. 8890

3. **Lapor ke Direksi**
   - Jika masalah kritis (sistem down lama)

### Jam Layanan:

| Hari | Jam Operasional |
|------|-----------------|
| Senin - Jumat | 08:00 - 17:00 WIB |
| Sabtu | 08:00 - 12:00 WIB |
| Minggu | Emergency only |
| Libur Nasional | Emergency hotline |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
