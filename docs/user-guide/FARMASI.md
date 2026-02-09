# Panduan Modul Farmasi

Panduan lengkap untuk menggunakan modul Farmasi SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Farmasi](#pengenalan-modul-farmasi)
2. [Cara Menerima Resep dari Dokter](#cara-menerima-resep-dari-dokter)
3. [Cara Memproses Resep](#cara-memproses-resep)
4. [Cara Menangani Resep Racik](#cara-menangani-resep-racik)
5. [Cara Mengelola Stok Obat](#cara-mengelola-stok-obat)
6. [Cara Mendispensasi Obat](#cara-mendispensasi-obat)
7. [Cara Menangani Obat Kosong](#cara-menangani-obat-kosong)
8. [Cara Mencetak Label Obat](#cara-mencetak-label-obat)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Farmasi

Modul Farmasi SIMRS RumahSakitKu adalah sistem terintegrasi untuk mengelola seluruh aktivitas kefarmasian di rumah sakit, mulai dari penerimaan resep, pengelolaan stok, hingga pendispensingan obat ke pasien.

### Menu Utama Modul Farmasi:

| Menu | Fungsi |
|------|--------|
| **Resep Masuk** | Monitor dan proses resep dari dokter |
| **Resep Racik** | Kelola resep obat racikan |
| **Stok Obat** | Manajemen persediaan obat |
| **Dispensing** | Serah terima obat ke pasien |
| **Retur Obat** | Proses pengembalian obat |
| **Laporan** | Laporan penggunaan obat |

### Alur Kerja Farmasi:

```
Resep Masuk → Verifikasi → Proses (Racik/Jadi) → Dispensing → Serah Terima
     ↑                                                      ↓
     └──────────── Stok Obat (Pengeluaran) ←───────────────┘
```

---

## Cara Menerima Resep dari Dokter

### Pemberitahuan Resep Masuk:

Resep dari dokter akan masuk ke sistem farmasi secara otomatis saat EMR difinalisasi.

#### Notifikasi Resep:

1. **Notifikasi Pop-up**: Muncul di layar apoteker
2. **Icon Notifikasi**: Lonceng 🔔 di header (angka menunjukkan jumlah resep baru)
3. **Suara Alarm**: Bunyi notifikasi (jika diaktifkan)
4. **Monitor Antrian**: Tampilan di layar farmasi

### Langkah Menerima Resep:

1. **Login** ke SIMRS dengan akun apoteker

2. Klik menu **"Farmasi"** → **"Resep Masuk"**

3. Lihat daftar resep yang masuk:

   | Kolom | Keterangan |
   |-------|------------|
   | No. | Nomor urut |
   | No. Resep | Nomor resep unik |
   | No. RM | Nomor rekam medis |
   | Nama Pasien | Nama lengkap pasien |
   | Poliklinik | Asal resep |
   | Dokter | Nama dokter yang meresepkan |
   | Waktu | Jam resep masuk |
   | Status | Menunggu/Proses/Selesai |

4. **Pilih resep** yang akan diproses:
   - Klik pada baris resep
   - Atau klik tombol **"Proses"**

5. **Review resep** yang muncul:
   - Data pasien
   - Daftar obat yang diresepkan
   - Aturan pakai
   - Catatan dokter

### Screenshot:
![Daftar Resep Masuk](../images/resep-masuk.png)

### Prioritas Resep:

| Prioritas | Warna | Keterangan |
|-----------|-------|------------|
| 🚨 Darurat | Merah | Obat vital segera (antibiotik, etc) |
| 🟡 Normal | Kuning | Resep reguler |
| 🟢 Pre-order | Hijau | Obat yang perlu dipesan |

---

## Cara Memproses Resep

### Tahapan Verifikasi Resep:

#### 1. Screen Resep (Administratif):

| Pemeriksaan | Ya | Tidak |
|-------------|-----|-------|
| Nama pasien lengkap | ☐ | ☐ |
| Umur/tanggal lahir jelas | ☐ | ☐ |
| Nama dokter lengkap | ☐ | ☐ |
| Tanggal resep | ☐ | ☐ |
| Tanda tangan/paraf dokter | ☐ | ☐ |
| Tidak ada pengubahan | ☐ | ☐ |

#### 2. Verifikasi Klinis (Pharmaceutical Care):

1. **Right Patient**: Benarkah pasiennya?
2. **Right Drug**: Obat yang tepat?
3. **Right Dose**: Dosis yang benar?
4. **Right Route**: Cara pemberian sesuai?
5. **Right Time**: Waktu/jadwal sesuai?
6. **Right Documentation**: Dokumentasi lengkap?

#### 3. Cek Interaksi Obat:

Sistem akan otomatis cek:
- Interaksi obat-obat
- Kontraindikasi
- Duplikasi terapi
- Alergi (dari riwayat pasien)

### Langkah Memproses:

1. **Review resep** di layar detail

2. **Klik "Verifikasi"** jika resep aman

3. **Jika ada masalah**, klik "Konsul" untuk:
   - Chat dengan dokter
   - Telepon dokter (jika urgent)
   - Tandai untuk revisi

4. Setelah verifikasi, **pilih jenis resep**:
   - **Non-Racik**: Obat jadi/langsung ambil
   - **Racik**: Obat yang perlu ditimbang/dicampur

5. **Proses pengambilan obat**:
   - Untuk obat jadi: Ambil dari rak sesuai jumlah
   - Untuk obat racik: Lanjut ke proses racik

6. **Input hasil** di sistem:
   - Jumlah obat yang dikeluarkan
   - Batch number obat
   - ED (Expired Date) obat

7. **Klik "Selesai Proses"**

### Screenshot:
![Verifikasi Resep](../images/verifikasi-resep.png)

### Status Resep:

| Status | Keterangan | Warna |
|--------|------------|-------|
| Menunggu | Belum diproses | Kuning |
| Verifikasi | Sedang diverifikasi | Biru |
| Proses | Sedang disiapkan | Orange |
| Selesai | Siap diambil | Hijau |
| Ditolak | Ada masalah/revisi | Merah |

---

## Cara Menangani Resep Racik

### Pengertian Resep Racik:

Resep racik adalah resep yang berisi obat-obat yang harus ditimbang, dicampur, dan dibuat dalam bentuk sediaan tertentu (serbuk, pil, salep, dll).

### Jenis Sediaan Racik:

| Sediaan | Kode | Contoh |
|---------|------|--------|
| Pulveres | pulv. | Serbuk dalam kantong |
| Pilula | pil. | Pil bulat kecil |
| Capsule | cap. | Isi kapsul |
| Ointment | ungt. | Salep |
| Solution | sol. | Larutan |
| Mixture | mist. | Campuran minum |

### Langkah Membuat Resep Racik:

#### 1. Siapkan Bahan:

1. Klik **"Resep Racik"** di menu farmasi
2. Pilih resep racik yang akan dibuat
3. Lihat daftar bahan yang diperlukan:
   ```
   R/ Paracetamol 250 mg
      Amoxicillin 125 mg
      m.f. pulv. dtd No. X
   S. 3 d d 1
   ```

4. Ambil bahan dari storage:
   - Cek ketersediaan stok
   - Catat nomor batch
   - Periksa tanggal expired

#### 2. Proses Penimbangan:

1. Siapkan **timbangan analitik**
2. Kalibrasi timbangan
3. Timbang setiap komponen sesuai dosis:
   - Paracetamol: 250 mg × 10 = 2500 mg
   - Amoxicillin: 125 mg × 10 = 1250 mg
4. Timbang massa total
5. **Catat hasil penimbangan** di lembar kerja

#### 3. Proses Pencampuran:

1. Masukkan bahan ke dalam mortar
2. Gunakan teknik **geometric dilution**:
   - Campur bahan dengan jumlah terkecil dulu
   - Tambahkan bahan lain sedikit demi sedikit
   - Triturasi (gerus) hingga homogen

3. **Kemas sesuai sediaan**:
   - Pulveres: Bungkus dalam kertas perkamen/pot
   - Pil: Cetak dengan pilen
   - Kapsul: Isi dalam kapsul kosong

#### 4. Pelabelan:

1. Tempel label pada kemasan
2. Isi informasi:
   - Nama pasien
   - No. RM
   - Aturan pakai
   - Tanggal pembuatan
   - Paraf apoteker

3. **Cetak label** dari sistem (jika tersedia printer)

#### 5. Input ke Sistem:

1. Klik **"Input Hasil Racik"**
2. Masukkan:
   - Jumlah yang dibuat
   - Nomor batch bahan
   - Paraf pembuat
3. **Klik "Simpan"**

### Screenshot:
![Proses Racik](../images/resep-racik.png)

### Lembar Kerja Racik:

| No | Nama Bahan | Dosis/Satuan | Jumlah | Batch | ED |
|----|------------|--------------|--------|-------|-----|
| 1 | Paracetamol | 250 mg | 2.5 g | ABC123 | 12/2026 |
| 2 | Amoxicillin | 125 mg | 1.25 g | DEF456 | 06/2026 |

---

## Cara Mengelola Stok Obat

### Monitoring Stok Obat:

1. Klik menu **"Farmasi"** → **"Stok Obat"**

2. Lihat dashboard stok:
   - Total jenis obat
   - Obat dengan stok aman
   - Obat stok menipis
   - Obat stok kosong

### Informasi Stok:

| Kolom | Keterangan |
|-------|------------|
| Kode Obat | Kode unik farmasi |
| Nama Obat | Nama generik |
| Satuan | Tablet, sirup, injeksi, etc |
| Stok Tersedia | Jumlah saat ini |
| Stok Minimum | Batas aman |
| ED Terdekat | Expired date paling dekat |
| Lokasi | Rak/gudang penyimpanan |

### Status Stok:

| Status | Warna | Keterangan | Tindakan |
|--------|-------|------------|----------|
| 🟢 Aman | Hijau | Stok > minimum | Monitor |
| 🟡 Menipis | Kuning | Stok ≤ minimum | Ajukan pemesanan |
| 🔴 Kosong | Merah | Stok = 0 | Emergency order |
| ⚠️ ED Dekat | Orange | ED < 6 bulan | Prioritaskan penggunaan |

### Proses Mutasi Stok:

#### 1. Pengeluaran Obat ( dispensing ):

Stok otomatis berkurang saat:
- Resep diproses
- Obat diberikan ke pasien

#### 2. Penambahan Stok (Penerimaan):

1. Klik **"Penerimaan Obat"**
2. Pilih **No. Faktur/PO** dari supplier
3. Input detail penerimaan:
   - Nama obat
   - Jumlah diterima
   - Nomor batch
   - Tanggal expired
   - Harga beli
4. Klik **"Simpan"**
5. Stok otomatis bertambah

#### 3. Penyesuaian Stok (Adjustment):

1. Klik **"Adjustment"**
2. Pilih obat yang akan disesuaikan
3. Input alasan penyesuaian:
   - Rusak
   - Expired
   - Hilang
   - Koreksi input
4. Input jumlah penyesuaian (+/-)
5. Klik **"Simpan"** dengan otorisasi supervisor

### Screenshot:
![Manajemen Stok](../images/stok-obat.png)

### Laporan Stok:

| Jenis Laporan | Frekuensi | Kegunaan |
|---------------|-----------|----------|
| Stok Harian | Harian | Monitoring real-time |
| Kartu Stok | Bulanan | Riwayat per obat |
| Obat ED Dekat | Mingguan | Manajemen expired |
| Obat Slow Moving | Bulanan | Evaluasi pembelian |

---

## Cara Mendispensasi Obat

### Persiapan Sebelum Dispensing:

1. **Pastikan resep sudah selesai diproses**
2. **Siapkan obat** di counter dispensing
3. **Cek kelengkapan**:
   - Nama pasien benar
   - Jumlah obat sesuai
   - Label terpasang
   - Brosur edukasi (jika perlu)

### Proses Dispensing:

#### 1. Panggil Pasien:

1. Klik **"Panggil Pasien"** di monitor
2. Sistem akan memanggil: "Nomor antrian A-005, silakan ke loket 2"
3. Verifikasi identitas pasien:
   - Nama lengkap
   - Nomor RM
   - Tanggal lahir

#### 2. Penyerahan Obat:

1. Sampaikan setiap obat:
   ```
   "Ini obat [nama obat], diminum [aturan pakai].
   Ada [jumlah] [satuan]."
   ```

2. Berikan **konseling**:
   - Aturan minum obat
   - Efek samping yang mungkin
   - Interaksi makanan/minuman
   - Penyimpanan obat

3. Pastikan pasien **mengerti**:
   - Tanyakan kembali cara pakai
   - Periksa pemahaman pasien

#### 3. Tanda Tangan:

1. Minta pasien **tanda tangan** di lembar serah terima
2. **Paraf apoteker** yang menyerahkan
3. **Simpan lembar** sebagai arsip

#### 4. Input Sistem:

1. Klik **"Selesai Dispensing"**
2. Input:
   - Nama penerima obat (jika bukan pasien)
   - Hubungan dengan pasien
   - Jam penyerahan
3. **Klik "Simpan"**

### Screenshot:
![Dispensing Obat](../images/dispensing.png)

### Formulir Serah Terima:

```
┌─────────────────────────────────────┐
│      BUKTI SERAH TERIMA OBAT       │
├─────────────────────────────────────┤
│ No. Resep: ________________         │
│ Nama Pasien: ________________       │
│ No. RM: ________________            │
├─────────────────────────────────────┤
│ OBAT YANG DITERIMA:                 │
│ ☐ Semua obat lengkap                │
│ ☐ Brosur edukasi diterima           │
│ ☐ Cara pakai sudah dijelaskan       │
├─────────────────────────────────────┤
│ Tanda tangan Penerima:              │
│ _________________                   │
│ Nama: ________________              │
│ Hubungan: ________________          │
├─────────────────────────────────────┤
│ Tanda tangan Apoteker:              │
│ _________________                   │
│ Nama: ________________              │
│ Tanggal/Jam: ________________       │
└─────────────────────────────────────┘
```

---

## Cara Menangani Obat Kosong

### Skenario Obat Kosong:

#### 1. Obat Alternatif Tersedia:

1. **Cek stok obat** dengan generik sama
2. **Konsul dengan dokter** untuk:
   - Pengganti dengan generik lain
   - Pengganti dengan kelas terapi sama
3. **Update resep** jika dokter setuju

#### 2. Obat Harus Dipesan:

1. **Informasikan ke pasien**:
   ```
   "Obat [nama] sedang kosong stok.
   Bisa diambil besok/setelah [estimasi waktu].
   Atau bisa dibeli di apotek luar dengan resep ini."
   ```

2. **Opsi untuk pasien**:
   - Tunggu stok masuk (booking)
   - Beli di apotek luar dengan copy resep
   - Ganti dengan obat alternatif (konfirmasi dokter)

3. **Proses booking**:
   - Klik **"Booking Obat"**
   - Input data pasien
   - Input obat yang dipesan
   - Estimasi waktu tersedia
   - Kontak pasien saat obat ready

#### 3. Obat Langka/Special Order:

1. **Cek distributor** yang menyediakan
2. **Buat PO khusus** (Purchase Order)
3. **Informasikan estimasi** ke pasien (3-7 hari)
4. **Follow up** status pemesanan

### Formulir Informasi Obat Kosong:

```
┌─────────────────────────────────────┐
│     INFORMASI OBAT TIDAK TERSEDIA   │
├─────────────────────────────────────┤
│ Nama Obat: ________________         │
│ Jumlah: ________________            │
│ Alasan: ☐ Stok habis                │
│        ☐ Belum tersedia di RS       │
│        ☐ ED semua stock             │
├─────────────────────────────────────┤
│ ALTERNATIF:                         │
│ ☐ Tunggu stok masuk                 │
│   Estimasi: ________________        │
│ ☐ Beli di apotek luar               │
│ ☐ Ganti dengan: ________________    │
│   Konfirmasi dokter: ☐ Ya ☐ Tidak   │
├─────────────────────────────────────┤
│ Tanda tangan:                       │
│ Pasien/ Keluarga: _______ Apoteker: │
└─────────────────────────────────────┘
```

### Screenshot:
![Obat Kosong](../images/obat-kosong.png)

---

## Cara Mencetak Label Obat

### Jenis Label Obat:

| Jenis | Ukuran | Kegunaan |
|-------|--------|----------|
| Label Kecil | 50mm x 25mm | Botol kecil, kapsul |
| Label Sedang | 75mm x 50mm | Botol sirup, salep |
| Label Besar | 100mm x 75mm | Infus, obat besar |
| Label Racik | 75mm x 100mm | Kantung obat racik |

### Informasi pada Label:

```
┌─────────────────────────┐
│   RS RUMAH SAKITKU      │
├─────────────────────────┤
│ Nama: AHMAD SUSANTO     │
│ No. RM: 000123          │
│ Tgl: 08/02/2026         │
├─────────────────────────┤
│ PARACETAMOL 500mg       │
│ No. 10 tablet           │
├─────────────────────────┤
│ S. 3 dd 1 pc            │
│ (3x sehari 1 tablet)    │
│ Sesudah makan           │
├─────────────────────────┤
│ Exp: 06/2026            │
│ Batch: ABC123           │
└─────────────────────────┘
```

### Langkah Mencetak Label:

#### 1. Cetak Otomatis (Saat Proses Resep):

1. Setelah resep diverifikasi
2. Klik **"Cetak Label"**
3. Pilih **printer label**
4. Pilih **ukuran label**
5. Preview dan klik **"Print"**

#### 2. Cetak Manual:

1. Buka menu **"Cetak Ulang Label"**
2. Cari dengan **No. Resep** atau **No. RM**
3. Pilih obat yang akan dicetak labelnya
4. Klik **"Cetak"**

### Pengaturan Printer Label:

**Printer Termal (Zebra/TSC):**
1. Pastikan driver terinstall
2. Atur ukuran kertas sesuai label
3. Set density/kepekatan tulisan
4. Set kecepatan print (jangan terlalu cepat)

**Printer Laser/Inkjet:**
1. Gunakan kertas label A4
2. Atur margin minimal
3. Print sesuai ukuran label
4. Potong label sesuai ukuran

### Screenshot:
![Cetak Label](../images/cetak-label.png)

### Tips Pelabelan:

- ✅ Pastikan label menempel rapi
- ✅ Hindari menutupi informasi penting
- ✅ Tulisan tetap terbaca setelah ditempel
- ✅ Gunakan label tahan air untuk kulkas
- ✅ Tempel pada bagian yang mudah terlihat

---

## Tips dan Troubleshooting

### Tips Kerja Efisien di Farmasi:

1. **Organisir Rak Obat**:
   - Kelompokkan berdasarkan kelas terapi
   - Letakkan obat sering pakai di depan
   - Gunahan FIFO (First In First Out)

2. **Gunakan Teknologi**:
   - Scanner barcode untuk verifikasi
   - Monitor ganda untuk efisiensi
   - Printer termal untuk kecepatan

3. **Komunikasi Tim**:
   - Briefing pagi tentang obat kosong
   - Update status resep di papan tulis
   - Koordinasi dengan dokter

### Checklist Harian:

- [ ] Cek stok obat menipis
- [ ] Cek obat ED dalam 6 bulan
- [ ] Bersihkan counter dispensing
- [ ] Cek kertas printer label
- [ ] Verifikasi suhu ruangan penyimpanan
- [ ] Backup data sistem

### Troubleshooting Umum:

#### 1. Resep Tidak Muncul di Sistem

**Gejala**: Dokter sudah finalisasi tapi resep tidak masuk farmasi

**Solusi**:
1. Refresh halaman (F5)
2. Cek filter tanggal (pastikan "Hari Ini")
3. Cek koneksi jaringan
4. Hubungi IT support jika masalah berlanjut

#### 2. Stok Tidak Berkurang Setelah Dispensing

**Gejala**: Obat sudah diberikan tapi stok tidak update

**Solusi**:
1. Cek apakah sudah klik "Selesai Dispensing"
2. Refresh halaman stok
3. Lakukan adjustment manual jika perlu
4. Laporkan ke supervisor untuk investigasi

#### 3. Printer Label Macet

**Gejala**: Label tidak keluar atau nyangkut

**Solusi**:
1. Matikan printer
2. Bersihkan head printer dengan alkohol
3. Cek kertas label (tidak melipat)
4. Restart printer dan coba lagi

#### 4. Obat ED Tidak Terdeteksi

**Gejala**: Sistem tidak warning untuk obat expired

**Solusi**:
1. Verifikasi input ED saat penerimaan
2. Cek pengaturan warning di sistem
3. Audit manual obat dengan ED dekat
4. Laporkan bug ke IT

#### 5. Kesalahan Input Resep Racik

**Gejala**: Jumlah racik tidak sesuai

**Solusi**:
1. Jangan panik
2. Hitung ulang bahan yang tersisa
3. Buat racik baru dengan dosis benar
4. Laporkan kesalahan untuk perbaikan SOP

### Shortcut Keyboard:

| Shortcut | Fungsi |
|----------|--------|
| `F2` | Cari resep |
| `F3` | Resep baru (manual) |
| `F5` | Refresh daftar resep |
| `Ctrl + P` | Cetak label |
| `Alt + V` | Verifikasi resep |

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Masalah resep | Kepala Apotek Ext. 7777 |
| Stok obat | Gudang Farmasi Ext. 6666 |
| Kerusakan peralatan | IPSRS Ext. 5555 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
