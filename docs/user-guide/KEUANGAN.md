# Panduan Modul Keuangan/Kasir

Panduan lengkap untuk menggunakan modul Keuangan dan Kasir SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Keuangan](#pengenalan-modul-keuangan)
2. [Cara Melihat Tagihan Pasien](#cara-melihat-tagihan-pasien)
3. [Cara Menerima Pembayaran](#cara-menerima-pembayaran)
4. [Cara Menghitung Kembalian](#cara-menghitung-kembalian)
5. [Cara Menerima Pembayaran BPJS](#cara-menerima-pembayaran-bpjs)
6. [Cara Menerima Pembayaran Asuransi](#cara-menerima-pembayaran-asuransi)
7. [Cara Melakukan Refund](#cara-melakukan-refund)
8. [Cara Mencetak Kwitansi](#cara-mencetak-kwitansi)
9. [Cara Melihat Laporan Harian](#cara-melihat-laporan-harian)
10. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Keuangan

Modul Keuangan/Kasir SIMRS RumahSakitKu mengelola seluruh transaksi keuangan rumah sakit, mulai dari pencatatan tagihan, pembayaran, hingga pembuatan laporan keuangan.

### Jenis Transaksi:

| Jenis | Deskripsi |
|-------|-----------|
| **Pembayaran Umum** | Pasien umum/tunai |
| **Pembayaran BPJS** | Klaim pasien BPJS |
| **Pembayaran Asuransi** | Klaim asuransi swasta |
| **Refund/Pengembalian** | Pengembalian uang kelebihan |
| **Deposit Rawat Inap** | Uang muka pasien rawat inap |

### Menu Utama:

| Menu | Fungsi |
|------|--------|
| **Tagihan** | Melihat dan mengelola tagihan |
| **Pembayaran** | Proses pembayaran pasien |
| **Refund** | Proses pengembalian uang |
| **Laporan** | Laporan transaksi harian/bulanan |
| **Setup** | Master tarif dan layanan |

---

## Cara Melihat Tagihan Pasien

### Akses Tagihan Pasien:

#### 1. Dari Antrian Pembayaran:

1. Login ke SIMRS dengan akun kasir
2. Klik menu **"Keuangan"** → **"Antrian Pembayaran"**
3. Lihat daftar pasien yang menunggu pembayaran
4. Klik nama pasien untuk melihat detail tagihan

#### 2. Dari Pencarian:

1. Klik **"Keuangan"** → **"Cari Tagihan"**
2. Masukkan **No. RM** atau **No. Registrasi**
3. Klik **"Cari"**
4. Sistem menampilkan semua tagihan pasien

### Komponen Tagihan:

| Kolom | Keterangan | Contoh |
|-------|------------|--------|
| Tanggal | Tanggal layanan | 08/02/2026 |
| Uraian | Nama layanan | Konsultasi Dokter |
| Jumlah | Kuantitas | 1 |
| Harga Satuan | Tarif per item | Rp 100.000 |
| Total | Jumlah × Harga | Rp 100.000 |
| Status | Lunas/Belum | Belum Lunas |

### Jenis Biaya:

```
┌──────────────────────────────────────────┐
│           RINCIAN TAGIHAN                │
├──────────────────────────────────────────┤
│ A. ADMINISTRASI                          │
│    - Pendaftaran                Rp 25.000│
│    - Kartu Pasien               Rp 10.000│
├──────────────────────────────────────────┤
│ B. TINDAKAN MEDIS                        │
│    - Konsultasi Dokter         Rp 100.000│
│    - Pemeriksaan Fisik          Rp 50.000│
│    - Injeksi                    Rp 25.000│
├──────────────────────────────────────────┤
│ C. PEMERIKSAAN PENUNJANG                 │
│    - Laboratorium              Rp 250.000│
│    - Radiologi                  Rp 150.000│
├──────────────────────────────────────────┤
│ D. OBAT & BHP                            │
│    - Obat Generik              Rp 125.000│
│    - Alat Kesehatan             Rp  75.000│
├──────────────────────────────────────────┤
│                              TOTAL TAGIHAN│
│                              Rp 810.000   │
└──────────────────────────────────────────┘
```

### Status Tagihan:

| Status | Warna | Keterangan |
|--------|-------|------------|
| 🟡 Belum Lunas | Kuning | Menunggu pembayaran |
| 🟢 Lunas | Hijau | Sudah dibayar |
| 🔵 Pending | Biru | Menunggu verifikasi asuransi |
| 🟠 Deposit | Orange | Sudah bayar sebagian (uang muka) |
| 🔴 Batal | Merah | Dibatalkan |

### Screenshot:
![Detail Tagihan](../images/detail-tagihan.png)

---

## Cara Menerima Pembayaran

### Langkah-langkah Pembayaran:

#### 1. Pembayaran Tunai:

1. **Buka tagihan pasien** yang akan dibayar

2. **Verifikasi rincian tagihan**:
   - Nama pasien benar
   - Jumlah tagihan sesuai
   - Tidak ada item yang salah

3. Klik tombol **"Bayar"** atau **"Proses Pembayaran"**

4. **Pilih metode pembayaran**: **TUNAI**

5. **Input jumlah yang diterima**:
   ```
   Total Tagihan: Rp 810.000
   Diterima:      Rp 1.000.000
   Kembalian:     Rp 190.000
   ```

6. **Klik "Hitung Kembalian"**

7. **Verifikasi** jumlah kembalian

8. Klik **"Konfirmasi Pembayaran"**

9. **Cetak kwitansi** (otomatis atau manual)

10. **Serahkan** kwitansi dan kembalian ke pasien

#### 2. Pembayaran Non-Tunai:

##### A. Kartu Debit:

1. Pilih metode: **DEBIT**
2. Pilih **bank** (BCA, Mandiri, BNI, dll)
3. Input **nomor kartu** (opsional)
4. Input **jumlah** (otomatis sama dengan tagihan)
5. Arahkan pasien ke **EDC/mesin kartu**
6. Setelah transaksi sukses:
   - Input **nomor approval** dari struk EDC
   - Upload **foto struk** (jika diperlukan)
7. Konfirmasi pembayaran
8. Cetak kwitansi

##### B. Kartu Kredit:

1. Pilih metode: **KREDIT**
2. Pilih **jenis kartu** (Visa, Mastercard, JCB)
3. Pilih **bank penerbit**
4. Input **jumlah**
5. Proses di mesin EDC
6. Input **nomor approval**
7. Konfirmasi dan cetak kwitansi

##### C. Transfer Bank:

1. Pilih metode: **TRANSFER**
2. Pilih **bank tujuan** (rekening RS)
3. Input **nomor referensi** transfer
4. Upload **bukti transfer**
5. Verifikasi oleh supervisor (jika > batas)
6. Konfirmasi pembayaran

##### D. QRIS:

1. Pilih metode: **QRIS**
2. Sistem generate **QR Code**
3. Tampilkan QR ke pasien
4. Pasien scan dengan aplikasi e-wallet
5. Tunggu notifikasi sukses
6. Konfirmasi pembayaran

##### E. E-Wallet (OVO, GoPay, DANA, dll):

1. Pilih metode: **E-WALLET**
2. Pilih **provider** (OVO/GoPay/DANA)
3. Input **nomor/nama akun** pasien
4. Proses pembayaran
5. Konfirmasi status sukses
6. Cetak kwitansi

### Screenshot:
![Form Pembayaran](../images/form-pembayaran.png)

### Split Payment (Pembayaran Campuran):

Jika pasien ingin bayar dengan beberapa metode:

1. Klik **"Split Payment"**
2. Tentukan **jumlah per metode**:
   ```
   Metode 1: Tunai - Rp 500.000
   Metode 2: Debit - Rp 310.000
   ```
3. Proses pembayaran pertama
4. Proses pembayaran kedua
5. Total harus = total tagihan

---

## Cara Menghitung Kembalian

### Perhitungan Otomatis:

Sistem akan otomatis menghitung kembalian:

```
Rumus: Kembalian = Jumlah Diterima - Total Tagihan

Contoh:
Total Tagihan:     Rp 810.000
Jumlah Diterima:   Rp 1.000.000
──────────────────────────────
Kembalian:         Rp 190.000
```

### Verifikasi Manual:

Sebagai kasir, verifikasi kembali:

1. **Hitung uang yang diterima** dari pasien
2. **Bandngkan** dengan input di sistem
3. **Periksa** perhitungan kembalian sistem
4. **Siapkan uang kembalian** dengan tepat
5. **Hitung ulang** saat menyerahkan ke pasien:
   ```
   "Total tagihan Rp 810.000, dari Bapak/Ibu Rp 1.000.000,
   kembaliannya Rp 190.000. Ini uangnya:
   100.000 + 50.000 + 20.000 + 20.000 = 190.000."
   ```

### Tips Menghitung Cepat:

| Total Tagihan | Diterima | Kembalian |
|---------------|----------|-----------|
| Rp 125.000 | Rp 200.000 | Rp 75.000 |
| Rp 340.000 | Rp 500.000 | Rp 160.000 |
| Rp 1.250.000 | Rp 2.000.000 | Rp 750.000 |

### Pembayaran Pasien Pas:

Jika pasien memberi uang pas:

1. Input jumlah diterima = total tagihan
2. Kembalian = Rp 0
3. Konfirmasi: "Pembayaran pas, terima kasih"
4. Cetak kwitansi

---

## Cara Menerima Pembayaran BPJS

### Skema Pembayaran BPJS:

| Jenis | Cara Bayar | Keterangan |
|-------|------------|------------|
| **PBI** | Gratis | Penerima Bantuan Iuran |
| **Non-PBI** | Gratis | Jaminan Kesehatan Nasional |
| **COB** | Campuran | Coordination of Benefits |

### Proses Pembayaran BPJS:

#### 1. Verifikasi Status BPJS:

1. Scan/Input **No. Kartu BPJS** pasien
2. Klik **"Cek Status"**
3. Sistem akan menampilkan:
   - Status kepesertaan (AKTIF/NON-AKTIF)
   - Kelas perawatan (I/II/III)
   - Faskes tingkat I
   - Masa berlaku

#### 2. Proses Verifikasi SEP:

SEP (Surat Eligibilitas Peserta) harus dibuat:

1. Klik **"Buat SEP"**
2. Pilih **jenis kunjungan**:
   - Rujukan FKTP
   - Rujukan Internal
   - Kontrol
   - Rujukan Antar RS

3. Input data rujukan:
   - No. Rujukan
   - Tanggal rujukan
   - Faskes perujuk
   - Diagnosis rujukan

4. Klik **"Simpan SEP"**
5. Cetak SEP (jika diperlukan)

#### 3. Proses Klaim:

1. Setelah pasien selesai perawatan
2. Pastikan semua tindakan tercatat
3. Finalisasi tagihan
4. Klik **"Proses Klaim BPJS"**
5. Sistem akan:
   - Generate data klaim
   - Grouping ke grouper BPJS (INA-CBGs)
   - Hitung estimasi klaim

6. **Submit klaim** ke BPJS:
   - Klik "Kirim Klaim Online"
   - Atau export file untuk upload manual

#### 4. Pelunasan:

1. BPJS akan membayar sesuai tarif INA-CBGs
2. Sistem akan menerima notifikasi status klaim:
   - **Proses**: Sedang diverifikasi BPJS
   - **Disetujui**: Klaim diterima
   - **Pending**: Ada yang perlu dikoreksi
   - **Ditolak**: Klaim ditolak dengan alasan

3. Jika ada **selisih** (kurang/lebih), sistem akan:
   - Tagih ke pasien jika kurang bayar
   - Refund ke pasien jika kelebihan (jarang)

### Screenshot:
![SEP BPJS](../images/sep-bpjs.png)

### Masalah Umum BPJS:

| Masalah | Solusi |
|---------|--------|
| Status tidak aktif | Suruh pasien urut ke BPJS |
| Melewati batas rujukan (>90 hari) | Buat rujukan ulang |
| Diagnosis tidak sesuai | Konsul dengan dokter |
| Klaim ditolak | Koreksi dan ajukan ulang |

---

## Cara Menerima Pembayaran Asuransi

### Jenis Asuransi:

| Tipe | Contoh | Proses |
|------|--------|--------|
| Asuransi Umum | Manulife, Allianz | Klaim reimbursement |
| Asuransi Kerja | Jamsostek, Asta | Klaim langsung |
| Asuransi Kesehatan | Prudential, AXA | Cashless/Reimburse |

### Proses Pembayaran Asuransi:

#### 1. Verifikasi Kartu Asuransi:

1. Input **No. Kartu Asuransi**
2. Pilih **provider asuransi**
3. Klik **"Verifikasi"**
4. Sistem akan cek:
   - Status polis
   - Batas limit
   - Masa berlaku

#### 2. Proses GL (Guarantee Letter):

Untuk asuransi cashless:

1. **Ajukan GL** ke pihak asuransi:
   - Klik "Ajukan GL"
   - Upload dokumen yang diperlukan
   - Tunggu approval

2. **Status GL**:
   - 🟡 Pending: Menunggu approval
   - 🟢 Approved: GL disetujui, limit ditentukan
   - 🔴 Rejected: GL ditolak, beri tahu pasien

3. Setelah GL **Approved**:
   - Input nomor GL
   - Proses pasien (rawat jalan/inap)
   - Catat pemakaian limit

#### 3. Klaim Asuransi:

1. Setelah perawatan selesai:
   - Finalisasi tagihan
   - Generate invoice asuransi
   - Kumpulkan dokumen lengkap:
     - Invoice
     - Resume medis
     - Hasil lab/radiologi
     - Copy GL

2. **Submit klaim**:
   - Kirim dokumen ke asuransi
   - Atau upload via portal asuransi
   - Input nomor klaim

3. **Follow up** pembayaran:
   - Monitor status klaim
   - Tindak lanjut jika ada kekurangan dokumen
   - Terima pembayaran dari asuransi

### Co-Pay (Pasien Bayar Sebagian):

Jika asuransi hanya cover sebagian:

```
Total Tagihan:     Rp 10.000.000
Cover Asuransi:    Rp 8.000.000 (80%)
Co-Pay Pasien:     Rp 2.000.000 (20%)
─────────────────────────────────────
Pasien bayar:      Rp 2.000.000
```

### Screenshot:
![Form Asuransi](../images/form-asuransi.png)

---

## Cara Melakukan Refund

### Situasi yang Memerlukan Refund:

1. **Kelebihan bayar** oleh pasien
2. **Pembatalan tindakan** setelah bayar
3. **Overcharge** (tagihan terlalu tinggi)
4. **Deposit berlebih** saat pulang rawat inap

### Proses Refund:

#### 1. Verifikasi Kebutuhan Refund:

1. Buka transaksi yang akan di-refund
2. Cek alasan refund dengan supervisor
3. Pastikan refund valid dan didukung bukti

#### 2. Proses Refund di Sistem:

1. Klik menu **"Keuangan"** → **"Refund"**
2. Cari transaksi dengan **No. Kwitansi** atau **No. RM**
3. Klik **"Proses Refund"**
4. **Pilih item** yang di-refund (jika parsial)
5. **Input jumlah refund**
6. **Pilih metode refund**:
   - Tunai
   - Transfer bank
   - Potong tagihan lain

7. **Isi alasan refund**
8. **Upload dokumen pendukung** (opsional)
9. **Mintakan approval** supervisor (jika > batas)
10. Klik **"Konfirmasi Refund"**

#### 3. Pelaksanaan Refund:

1. **Refund Tunai**:
   - Ambil uang dari kas
   - Hitung bersama pasien
   - Serahkan dengan kwitansi refund

2. **Refund Transfer**:
   - Input nomor rekening pasien
   - Proses transfer
   - Upload bukti transfer
   - Beri tahu pasien

### Formulir Refund:

```
┌─────────────────────────────────────┐
│      KWITANSI REFUND/PENGEMBALIAN   │
├─────────────────────────────────────┤
│ No. Refund: RFD/2026/02/0001        │
│ Tanggal: 08 Februari 2026           │
├─────────────────────────────────────┤
│ Telah diterima dari:                │
│ RS RUMAH SAKITKU                    │
│                                     │
│ Uang sejumlah: Rp 250.000           │
│ (Dua Ratus Lima Puluh Ribu Rupiah)  │
│                                     │
│ Untuk: Pengembalian kelebihan       │
│        pembayaran No. INV/001       │
├─────────────────────────────────────┤
│ Alasan: Pembatalan pemeriksaan      │
│         laboratorium                │
├─────────────────────────────────────┤
│ Yang menerima,         Yang memberi,│
│                       Supervisor    │
│ ________________     _____________  │
│ Nama Pasien/Keluarga                │
└─────────────────────────────────────┘
```

### Screenshot:
![Form Refund](../images/form-refund.png)

### Batasan Refund:

| Jumlah | Otorisasi |
|--------|-----------|
| < Rp 500.000 | Kasir |
| Rp 500.000 - 2.000.000 | Supervisor |
| > Rp 2.000.000 | Kepala Bagian Keuangan |

---

## Cara Mencetak Kwitansi

### Format Kwitansi:

```
┌─────────────────────────────────────────┐
│          RS RUMAH SAKITKU               │
│     Jl. Sehat No. 123, Jakarta          │
│         Telp: (021) 1234567             │
├─────────────────────────────────────────┤
│           K W I T A N S I               │
│              No. INV/2026/02/001        │
├─────────────────────────────────────────┤
│ Sudah terima dari: AHMAD SUSANTO        │
│ No. RM: 000123                          │
├─────────────────────────────────────────┤
│ Uang sejumlah: Rp 810.000               │
│ (Delapan Ratus Sepuluh Ribu Rupiah)     │
│                                         │
│ Untuk pembayaran:                       │
│ - Biaya administrasi & pendaftaran      │
│ - Tindakan medis                        │
│ - Obat-obatan                           │
│ sesuai rincian tagihan terlampir        │
├─────────────────────────────────────────┤
│ Metode Bayar: TUNAI                     │
│                                         │
│ Jakarta, 08 Februari 2026               │
│                                         │
│            Kasir,                       │
│                                         │
│         ______________                  │
│         (Siti Aminah)                   │
└─────────────────────────────────────────┘
```

### Jenis Cetakan:

| Jenis | Ukuran | Kegunaan |
|-------|--------|----------|
| Kwitansi Full | A4 | Untuk pasien, lengkap |
| Kwitansi Kasir | 80mm (thermal) | Struk pembayaran |
| Buku Besar Copy | A4 | Arsip RS |
| Copy Pasien | A5 | Duplikat untuk pasien |

### Proses Cetak:

#### 1. Cetak Otomatis:

Setelah pembayaran dikonfirmasi, sistem otomatis:
1. Generate kwitansi PDF
2. Tampilkan preview
3. Kirim ke printer default

#### 2. Cetak Ulang:

Jika kwitansi hilang/rusak:

1. Klik **"Keuangan"** → **"Riwayat Transaksi"**
2. Cari transaksi dengan **No. Kwitansi**
3. Klik **"Cetak Ulang"**
4. Sistem akan menandai "COPY" di kwitansi
5. Cetak dan serahkan ke pasien

### Pengaturan Printer:

**Printer Dot Matrix (Kwitansi Berlebarn):**
- Atur ukuran kertas: 8.5" x 5.5" (setengah letter)
- Atur font: Epson ESC/P
- Cek ribbon (jangan sampai pudar)

**Printer Thermal (Struk):**
- Atur ukuran: 80mm
- Cek kertas thermal
- Atur kecepatan print

### Screenshot:
![Preview Kwitansi](../images/preview-kwitansi.png)

---

## Cara Melihat Laporan Harian

### Akses Laporan:

1. Klik menu **"Keuangan"** → **"Laporan"**

2. Pilih jenis laporan:
   - **Laporan Harian** (per shift)
   - **Laporan Shift** (pagi/sore/malam)
   - **Laporan Rekapitulasi** (akumulasi)

### Laporan Harian Kasir:

```
┌─────────────────────────────────────────┐
│     LAPORAN PENDAPATAN HARIAN          │
│         RS RUMAH SAKITKU                │
│          08 Februari 2026               │
├─────────────────────────────────────────┤
│ A. PENDAPATAN TUNAI                     │
│    - Rawat Jalan          Rp 15.250.000 │
│    - Rawat Inap           Rp  8.500.000 │
│    - IGD                  Rp  3.200.000 │
│    SUBTOTAL               Rp 27.950.000 │
├─────────────────────────────────────────┤
│ B. PENDAPATAN NON-TUNAI                 │
│    - Kartu Debit          Rp 12.300.000 │
│    - Kartu Kredit         Rp  5.500.000 │
│    - Transfer             Rp  2.100.000 │
│    SUBTOTAL               Rp 19.900.000 │
├─────────────────────────────────────────┤
│ C. PENDAPATAN BPJS (Klaim)              │
│    - Rawat Jalan          Rp 45.600.000 │
│    - Rawat Inap           Rp 78.200.000 │
│    SUBTOTAL               Rp123.800.000 │
├─────────────────────────────────────────┤
│ D. REFUND                               │
│    - Pengembalian         (Rp  850.000) │
├─────────────────────────────────────────┤
│ TOTAL PENDAPATAN BERSIH   Rp171.800.000 │
├─────────────────────────────────────────┤
│ RINCIAN TRANSAKSI:                      │
│ - Jumlah transaksi: 145                 │
│ - Rata-rata per transaksi: Rp 1.184.828 │
│ - Pasien Umum: 85                       │
│ - Pasien BPJS: 52                       │
│ - Pasien Asuransi: 8                    │
└─────────────────────────────────────────┘
```

### Filter Laporan:

| Filter | Opsi |
|--------|------|
| **Tanggal** | Pilih range tanggal |
| **Shift** | Pagi/Sore/Malam/Semua |
| **Kasir** | Semua/spesifik kasir |
| **Jenis Bayar** | Tunai/Non-tunai/BPJS |
| **Unit** | RJ/RI/IGD/Semua |

### Export Laporan:

1. Klik **"Export"**
2. Pilih format:
   - **Excel** (.xlsx) - untuk analisis
   - **PDF** (.pdf) - untuk arsip
   - **CSV** (.csv) - untuk import sistem lain
3. Pilih **lokasi penyimpanan**
4. Klik **"Download"**

### Screenshot:
![Laporan Harian](../images/laporan-harian.png)

### Tutup Buku (Closing):

Proses akhir shift:

1. **Generate laporan shift**
2. **Hitung fisik uang** di laci kasir
3. **Bandngkan** dengan laporan sistem
4. Jika **selisih**, catat dan laporkan
5. **Serahkan uang** ke bagian kas
6. **Tanda tangan** laporan closing
7. **Simpan arsip** laporan

---

## Tips dan Troubleshooting

### Tips Kerja Kasir yang Efisien:

1. **Persiapan Awal Shift**:
   - Hitung uang awal (modal kas)
   - Cek kertas printer
   - Verifikasi login dan akses

2. **Selama Bertugas**:
   - Prioritaskan antrian sesuai urutan
   - Verifikasi setiap pembayaran dengan teliti
   - Simpan kwitansi terurut

3. **Akhir Shift**:
   - Hitung uang fisik vs sistem
   - Selesaikan transaksi pending
   - Backup data penting

### Checklist Shift:

- [ ] Uang modal kas tercatat
- [ ] Printer siap (kertas, tinta/ribbon)
- [ ] Koneksi internet stabil
- [ ] EDC/mesin kartu siap
- [ ] Formulir refund tersedia
- [ ] Stempel dan tanda tangan lengkap

### Troubleshooting Umum:

#### 1. Total Tagihan Tidak Sesuai

**Gejala**: Pasien protes harga

**Solusi**:
1. Cek rincian item per item
2. Bandngkan dengan tarif yang berlaku
3. Jika salah, hubungi supervisor untuk koreksi
4. Jika benar, jelaskan dengan baik ke pasien

#### 2. Printer Tidak Cetak Kwitansi

**Gejala**: Pembayaran sukses tapi tidak ada struk

**Solusi**:
1. Cek koneksi printer
2. Cek kertas (macet/habis)
3. Restart printer
4. Cetak ulang dari riwayat transaksi

#### 3. Transaksi Ganda

**Gejala**: Satu tagihan terbayar 2 kali

**Solusi**:
1. Jangan panik
2. Verifikasi dengan sistem (2 kwitansi?)
3. Jika benar double, proses refund
4. Jelaskan ke pasien dengan sopan

#### 4. Kartu Debit Gagal tapi Uang Terpotong

**Gejala**: EDC error, saldo berkurang, tagihan belum lunas

**Solusi**:
1. Cek struk EDC (jika ada)
2. Cek SMS/email notifikasi bank
3. Jika uang terpotong, catat:
   - Nomor kartu
   - Waktu transaksi
   - Nomor approval (jika ada)
4. Suruh pasien cek mutasi rekening
5. Proses komplain ke bank jika perlu
6. Sebagai kasir, catat insiden dan lapor supervisor

#### 5. Sistem Lambat Saat Pembayaran

**Gejala**: Loading lama, pasien menunggu

**Solusi**:
1. Informasikan ke pasien sistem sedang lambat
2. Jangan refresh halaman (takut data hilang)
3. Tunggu respon sistem
4. Jika hang, hubungi IT support
5. Catat transaksi manual sementara jika urgent

### Shortcut Keyboard:

| Shortcut | Fungsi |
|----------|--------|
| `F2` | Cari pasien/tagihan |
| `F4` | Proses pembayaran |
| `F5` | Refresh laporan |
| `F9` | Cetak kwitansi |
| `Ctrl + P` | Print preview |
| `Esc` | Batal/kembali |

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Verifikasi tarif | Bagian Tarif Ext. 7777 |
| Masalah BPJS | BPJS Helpdesk Ext. 5678 |
| Approval refund | Supervisor Kasir Ext. 6666 |
| Kerusakan EDC | Bank/IPSRS Ext. 5555 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
