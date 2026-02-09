# Panduan Modul Penunjang Medis

Panduan lengkap untuk menggunakan modul Penunjang Medis (Laboratorium dan Radiologi) SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Penunjang Medis](#pengenalan-modul-penunjang-medis)
2. [Cara Order Laboratorium](#cara-order-laboratorium)
3. [Cara Entry Hasil Laboratorium](#cara-entry-hasil-laboratorium)
4. [Cara Validasi Hasil Laboratorium](#cara-validasi-hasil-laboratorium)
5. [Cara Order Radiologi](#cara-order-radiologi)
6. [Cara Upload Hasil Radiologi](#cara-upload-hasil-radiologi)
7. [Cara Baca Hasil Radiologi](#cara-baca-hasil-radiologi)
8. [Cara Cetak Hasil](#cara-cetak-hasil)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Penunjang Medis

Modul Penunjang Medis mengelola layanan diagnostic support yang terdiri dari:
- **Laboratorium**: Pemeriksaan darah, urin, dan cairan tubuh lainnya
- **Radiologi**: Pemeriksaan foto Rontgen, CT Scan, MRI, USG

### Integrasi dengan Modul Lain:

```
Dokter Order → Penunjang Proses → Hasil → Dokter Review
     ↓                ↓              ↓
  EMR Pasien      Pendaftaran    Billing
```

### Menu Utama:

| Menu | Fungsi |
|------|--------|
| **Order Masuk** | Lihat permintaan pemeriksaan |
| **Pendaftaran** | Daftar pasien ke antrian |
| **Entry Hasil** | Input hasil pemeriksaan |
| **Validasi** | Verifikasi hasil oleh dokter |
| **Cetak Hasil** | Print laporan |
| **Laporan** | Statistik pemeriksaan |

---

## Cara Order Laboratorium

### Order dari Dokter:

#### 1. Order dari EMR:

1. Buka EMR pasien
2. Klik tab **"Laboratorium"** atau **"Order Lab"**
3. Klik **"+ Order Baru"**
4. Pilih **jenis pemeriksaan**:

   **Hematologi:**
   - Hemoglobin (Hb)
   - Hematokrit (Ht)
   - Leukosit
   - Trombosit
   - LED

   **Kimia Klinik:**
   - Gula Darah (GDP, GD2JPP, HbA1c)
   - Fungsi Ginjal (Ureum, Kreatinin)
   - Fungsi Hati (SGOT, SGPT, Bilirubin)
   - Lemak Darah (Kolesterol, Trigliserida, HDL, LDL)
   - Elektrolit (Natrium, Kalium, Klorida)

   **Urinalisa:**
   - Urine lengkap
   - Protein urine
   - Glukosa urine

   **Serologi:**
   - Widal
   - NS1/Dengue
   - HBsAg
   - HIV
   - Tes Kehamilan

   **Mikrobiologi:**
   - Pewarnaan Gram
   - Kultur dan sensitivitas

#### 2. Prioritas Pemeriksaan:

| Prioritas | Warna | Waktu Penanganan |
|-----------|-------|------------------|
| CITO/Darurat | 🔴 Merah | < 1 jam |
| Priority | 🟡 Kuning | < 4 jam |
| Rutin | 🟢 Hijau | < 24 jam |

#### 3. Simpan Order:

1. Pilih prioritas
2. Tambahkan **catatan khusus** (jika perlu)
3. Klik **"Simpan Order"**
4. Sistem akan:
   - Kirim order ke laboratorium
   - Generate nomor order lab
   - Muncul di antrian lab

### Screenshot:
![Order Laboratorium](../images/order-lab.png)

### Form Order Lab:

```
┌─────────────────────────────────────────┐
│      PERMINTAAN PEMERIKSAAN LAB         │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ No. Order: LAB-20260208-001             │
│ No. RM: 000123                          │
│ Nama: AHMAD SUSANTO                     │
│ Tgl Order: 08/02/2026 14:30             │
├─────────────────────────────────────────┤
│ PEMERIKSAAN:                            │
│ ☑ Hb                                    │
│ ☑ Ht                                    │
│ ☑ Leukosit                              │
│ ☑ Trombosit                             │
│ ☐ LED                                   │
│ ☑ Gula Darah Puasa                      │
│ ☑ Kreatinin                             │
│ ☐ SGOT                                  │
│ ☐ SGPT                                  │
├─────────────────────────────────────────┤
│ Prioritas: ☐ CITO ☑ Rutin               │
│ Catatan: __________________________     │
├─────────────────────────────────────────┤
│ Dokter Pengirim: dr. Budi Santoso       │
│ Tanda tangan: _________________         │
└─────────────────────────────────────────┘
```

---

## Cara Entry Hasil Laboratorium

### Menerima Order:

1. Login sebagai petugas lab
2. Klik menu **"Laboratorium"** → **"Order Masuk"**
3. Lihat daftar order
4. Pilih order yang akan diproses

### Proses Pengambilan Sampel:

#### 1. Pendaftaran Sampel:

1. Klik **"Proses Order"**
2. Pilih **jenis sampel**:
   - Darah (EDTA, Citrate, Serum, Heparin)
   - Urine
   - Feses
   - Cairan tubuh lain (CSF, pleura, dll)
3. Cetak **label sampel**
4. Tempel label pada tabung

#### 2. Entry Hasil:

1. Klik **"Entry Hasil"**
2. Pilih order yang akan di-entry
3. Input hasil per parameter:

```
┌─────────────────────────────────────────┐
│         ENTRY HASIL LABORATORIUM        │
├─────────────────────────────────────────┤
│ No. Order: LAB-20260208-001             │
│ Nama: AHMAD SUSANTO                     │
├─────────────────────────────────────────┤
│ HEMATOLOGI:                             │
│ Parameter    Hasil   Satuan   Nilai Rujuk│
│ Hemoglobin   12.5     g/dL    13.5-18.0 │
│ Hematokrit   38       %       40-54     │
│ Leukosit     8,500    /μL     4,000-11,000│
│ Trombosit    250,000  /μL     150,000-400,000│
├─────────────────────────────────────────┤
│ KIMIA KLINIK:                           │
│ Gula Puasa   110      mg/dL   70-110    │
│ Kreatinin    1.2      mg/dL   0.7-1.3   │
│ Ureum        25       mg/dL   10-50     │
└─────────────────────────────────────────┘
```

#### 3. Validasi Input:

Sistem otomatis cek:
- Hasil di luar nilai rujuk → tandai merah
- Hasir kritis → alarm/notifikasi
- Format hasil sesuai

### Nilai Kritis (Critical Value):

Nilai yang harus segera dilaporkan:

| Parameter | Nilai Kritis Rendah | Nilai Kritis Tinggi |
|-----------|---------------------|---------------------|
| Hemoglobin | < 7 g/dL | > 20 g/dL |
| Leukosit | < 2,000 /μL | > 50,000 /μL |
| Trombosit | < 20,000 /μL | > 1,000,000 /μL |
| Gula Darah | < 40 mg/dL | > 500 mg/dL |
| Kalium | < 2.5 mEq/L | > 6.5 mEq/L |
| Natrium | < 120 mEq/L | > 160 mEq/L |

Jika nilai kritis:
1. Sistem alarm
2. Verifikasi hasil (ulang jika perlu)
3. Telepon dokter yang meminta
4. Dokumentasikan pemberitahuan

### Screenshot:
![Entry Hasil Lab](../images/entry-hasil-lab.png)

---

## Cara Validasi Hasil Laboratorium

### Proses Validasi:

Validasi adalah proses pemeriksaan ulang hasil oleh validator (dokter/pejabat lab) sebelum diterbitkan.

#### 1. Review Hasil:

1. Validator login ke sistem
2. Klik menu **"Laboratorium"** → **"Validasi"**
3. Lihat daftar hasil yang menunggu validasi
4. Klik order untuk review

#### 2. Pemeriksaan Hasil:

Cek kembali:
- ✅ Identitas pasien benar
- ✅ Hasil logis dan konsisten
- ✅ Tidak ada nilai kritis yang terlewat
- ✅ Satuan sudah benar
- ✅ Tidak ada hasil yang inkompatibel

#### 3. Tindakan Validasi:

| Tindakan | Kapan Digunakan |
|----------|-----------------|
| **Validasi** | Hasil normal/layak terbit |
| **Revisi** | Ada kesalahan input |
| **Ulang** | Sampel bermasalah |
| **Konsul** | Hasil aneh/kurang jelas |

#### 4. Tanda Tangan Elektronik:

1. Klik **"Validasi"**
2. Masukkan **password** validator
3. Sistem mencatat:
   - Nama validator
   - Tanggal dan jam validasi
   - IP address

### Screenshot:
![Validasi Lab](../images/validasi-lab.png)

### Hasil Tervalidasi:

Setelah validasi:
- Hasil dapat dilihat di EMR pasien
- Otomatis notifikasi ke dokter pengirim
- Bisa dicetak/di-download
- Tidak bisa diubah lagi (kecuali pembatalan)

---

## Cara Order Radiologi

### Jenis Pemeriksaan Radiologi:

| Modalitas | Pemeriksaan |
|-----------|-------------|
| **X-Ray** | Thorax, Ekstremitas, Skull, Abdomen |
| **USG** | Abdomen, Obstetri, Kardiak, Tiroid |
| **CT Scan** | Head, Thorax, Abdomen, MSCT |
| **MRI** | Brain, Spine, Joint |
| **Mammografi** | Breast imaging |
| **Fluoroskopi** | BNO, IVP, Barium |

### Langkah Order:

#### 1. Order dari EMR:

1. Buka EMR pasien
2. Klik tab **"Radiologi"** atau **"Order Radiologi"**
3. Klik **"+ Order Baru"**
4. Pilih **modalitas** dan **pemeriksaan**:

```
☑ X-Ray
   ☑ Thorax PA
   ☐ Thorax AP/Lateral
   ☐ Ekstremitas
☐ CT Scan
☐ USG
☐ MRI
```

5. Isi **clinical question**:
   - Keluhan utama
   - Pertanyaan klinis yang ingin dijawab
   - Riwayat relevan

#### 2. Persiapan Pasien:

| Pemeriksaan | Persiapan |
|-------------|-----------|
| Abdomen USG | Puasa 6-8 jam |
| Pelvis USG | Kencing penuh |
| CT dengan kontras | Puasa 4-6 jam, cek fungsi ginjal |
| BNO/IVP | Puasa, bowel prep |

#### 3. Simpan Order:

1. Tentukan **prioritas** (CITO/Rutin)
2. Klik **"Simpan Order"**
3. Sistem kirim ke radiologi

### Screenshot:
![Order Radiologi](../images/order-radiologi.png)

### Form Order Radiologi:

```
┌─────────────────────────────────────────┐
│      PERMINTAAN PEMERIKSAAN RADIOLOGI   │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ No. Order: RAD-20260208-001             │
│ No. RM: 000123                          │
│ Nama: AHMAD SUSANTO                     │
│ Tgl Order: 08/02/2026                   │
├─────────────────────────────────────────┤
│ PEMERIKSAAN:                            │
│ ☑ X-Ray Thorax PA                       │
│ ☐ X-Ray Thorax Lateral                  │
│ ☐ CT Scan Thorax                        │
│ ☐ USG Abdomen                           │
├─────────────────────────────────────────┤
│ CLINICAL QUESTION:                      │
│ Pria 40 th, sesak napas 3 hari,         │
│ demam, batuk. Suspek pneumonia.         │
│ Tolong evaluasi infiltrat.              │
├─────────────────────────────────────────┤
│ Prioritas: ☐ CITO ☑ Rutin               │
├─────────────────────────────────────────┤
│ Dokter Pengirim: dr. Budi Santoso       │
│ Tanda tangan: _________________         │
└─────────────────────────────────────────┘
```

---

## Cara Upload Hasil Radiologi

### Hasil Radiologi Digital:

#### 1. Format File:

| Tipe | Format | Ukuran |
|------|--------|--------|
| **Gambar** | DICOM | Bervariasi |
| **Preview** | JPEG/PNG | < 5 MB |
| **Laporan** | PDF | < 10 MB |

#### 2. Proses Upload:

1. Petugas radiologi login
2. Klik menu **"Radiologi"** → **"Upload Hasil"**
3. Pilih order yang akan diupload
4. Klik **"Pilih File"**
5. Pilih file hasil (DICOM/JPEG/PDF)
6. Klik **"Upload"**
7. Tunggu proses upload selesai

#### 3. Pengorganisasian:

1. Beri **judul** setiap seri gambar:
   - "Thorax PA"
   - "Thorax Lateral"
2. Pastikan **urutan** benar
3. Klik **"Simpan"**

### Screenshot:
![Upload Radiologi](../images/upload-radiologi.png)

### PACS Integration:

Jika terintegrasi dengan PACS (Picture Archiving and Communication System):
- Hasil otomatis sinkron dari mesin ke sistem
- Tidak perlu upload manual
- Viewer DICOM built-in di SIMRS

---

## Cara Baca Hasil Radiologi

### Penulisan Laporan Radiologi:

#### 1. Struktur Laporan:

```
┌─────────────────────────────────────────┐
│      LAPORAN PEMERIKSAAN RADIOLOGI      │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ DATA PASIEN                             │
│ Nama: AHMAD SUSANTO                     │
│ No. RM: 000123                          │
│ Tgl Periksa: 08/02/2026                 │
│ Dokter Pengirim: dr. Budi Santoso       │
├─────────────────────────────────────────┤
│ PEMERIKSAAN: X-RAY THORAX PA            │
├─────────────────────────────────────────┤
│ TEKNIK PEMERIKSAAN:                     │
│ Thorax PA erect, good inspiration,      │
│ adequate penetration                    │
├─────────────────────────────────────────┤
│ HASIL:                                  │
│                                         │
│ Cor: CTR normal, aorta tidak melebar    │
│ Pulmo: Infiltrat konsolidasi pada       │
│        regio paru inferior dextra       │
│ Sinus: Costophrenicus sinistra tajam,   │
│        dextra agak tumpul               │
│ Tulang: Tidak ada kelainan              │
│ Soft tissue: Normal                     │
├─────────────────────────────────────────┤
│ KESIMPULAN:                             │
│ Pneumonia lobaris inferior dx           │
│ Efusi pleura dextra ringan              │
├─────────────────────────────────────────┤
│ SARAN:                                  │
│ Kontrol setelah terapi                  │
├─────────────────────────────────────────┤
│ Dijakarta, 08 Februari 2026             │
│                                         │
│ dr. _________, Sp.Rad                   │
│ NIP. __________________                 │
└─────────────────────────────────────────┘
```

#### 2. Template Laporan:

Gunakan template untuk efisiensi:
- Template Thorax Normal
- Template Fraktur
- Template Normal CT Brain
- Dst

#### 3. Entry ke Sistem:

1. Buka order radiologi
2. Klik **"Baca/Entry Hasil"**
3. Pilih **template** (opsional)
4. Tulis hasil sesuai struktur:
   - Teknik
   - Hasil deskripsi
   - Kesimpulan
   - Saran
5. Klik **"Simpan"**
6. **Validasi** oleh dokter radiologi

### Screenshot:
![Baca Radiologi](../images/baca-radiologi.png)

### Critical Finding:

Temuan kritis yang harus segera dilaporkan:
- Pneumothorax tension
- Hematoma intrakranial dengan herniasi
- Aneurisma aorta ruptur
- Emboli paru masif
- Perforasi viskus

Jika critical finding:
1. Telpon dokter pengirim segera
2. Dokumentasikan komunikasi
3. Beri tanda "URGENT" di laporan

---

## Cara Cetak Hasil

### Cetak Hasil Laboratorium:

1. Buka hasil lab yang sudah tervalidasi
2. Klik **"Cetak"** atau icon 🖨️
3. Pilih format:
   - Format Lengkap (semua parameter)
   - Format Abnormal Only (hanya yang diluar normal)
4. Preview
5. Pilih printer
6. Klik **"Print"**

### Cetak Hasil Radiologi:

1. Buka laporan radiologi
2. Klik **"Cetak Laporan"** untuk PDF
3. Klik **"Cetak Gambar"** untuk film/gambar
4. Pilih ukuran kertas:
   - A4 untuk laporan
   - 14x17 inch untuk film X-ray
   - 8x10 inch untuk film kecil
5. Print

### Format Cetakan:

#### Laboratorium:

```
┌─────────────────────────────────────────┐
│ RS RUMAH SAKITKU                        │
│ Laboratorium Klinik                     │
├─────────────────────────────────────────┤
│ No. Lab: LAB-20260208-001               │
│ Nama: AHMAD SUSANTO                     │
│ Tgl Lahir: 15/08/1985 (40 th)           │
├─────────────────────────────────────────┤
│ PARAMETER         HASIL    UNIT   REF   │
│ Hemoglobin        12.5*    g/dL   13.5-18│
│ Leukosit          8,500    /μL    4-11K │
│ Trombosit         250,000  /μL   150-400K│
│ Gula Puasa        110      mg/dL  70-110│
├─────────────────────────────────────────┤
│ * = di luar nilai rujukan               │
│ Validasi: dr. _____, Sp.PK (08/02/2026) │
└─────────────────────────────────────────┘
```

### Screenshot:
![Cetak Hasil](../images/cetak-hasil.png)

### Kirim Hasil Elektronik:

1. Klik **"Kirim Email"**
2. Input alamat email dokter/pasien
3. Klik **"Kirim"**
4. Sistem mengirim PDF hasil

---

## Tips dan Troubleshooting

### Tips Kerja di Penunjang Medis:

1. **Laboratorium**:
   - Selalu labeling sampel dengan benar
   - Pastikan sampel cukup
   - Kriteria lipemik/hemolisis perhatikan

2. **Radiologi**:
   - Pastikan identitas pasien benar sebelum foto
   - Proteksi radiasi selalu dipakai
   - Kualitas gambar dicek sebelum pasien pulang

### Troubleshooting:

#### 1. Hasil Lab Tidak Muncul di EMR

**Solusi**:
- Cek status validasi
- Refresh halaman EMR
- Pastikan order sudah final

#### 2. Gambar Radiologi Tidak Terupload

**Solusi**:
- Cek ukuran file (maksimal batas)
- Cek format file
- Coba kompres file
- Upload ulang

#### 3. Nilai Kritis Tidak Teralarm

**Solusi**:
- Setting nilai rujuk dicek
- Verifikasi manual dan lapor dokter
- Update setting jika perlu

#### 4. Order Tertukar Pasien

**Solusi**:
- JANGAN hapus order
- Batalkan order dengan alasan
- Buat order baru yang benar
- Dokumentasikan kesalahan

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Hasil lab | Dokter Lab Ext. 7777 |
| Hasil radiologi | Dokter Radiologi Ext. 6666 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
