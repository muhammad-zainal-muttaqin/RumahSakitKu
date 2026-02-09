# Panduan Modul Rekam Medis

Panduan lengkap untuk menggunakan modul Rekam Medis Elektronik (EMR) SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Rekam Medis](#pengenalan-modul-rekam-medis)
2. [Cara Membuka EMR Pasien](#cara-membuka-emr-pasien)
3. [Cara Mengisi SOAP](#cara-mengisi-soap)
4. [Cara Mengisi CPPT](#cara-mengisi-cppt)
5. [Cara Mengisi Asesmen & TTV](#cara-mengisi-asesmen--ttv)
6. [Cara Memasukkan Diagnosis ICD10](#cara-memasukkan-diagnosis-icd10)
7. [Cara Finalisasi Rekam Medis](#cara-finalisasi-rekam-medis)
8. [Cara Mencetak Rekam Medis](#cara-mencetak-rekam-medis)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Rekam Medis

Modul Rekam Medis Elektronik (EMR) adalah sistem pencatatan kesehatan pasien secara digital yang menggantikan rekam medis berbasis kertas. Sistem ini memungkinkan dokter dan tenaga medis untuk:

- Mengakses riwayat kesehatan pasien secara real-time
- Mengisi dokumen medis dengan terstruktur (SOAP, CPPT)
- Mengintegrasikan data dengan modul farmasi dan laboratorium
- Melacak perjalanan penyakit pasien

### Standar Dokumentasi:

SIMRS RumahSakitKu menggunakan standar dokumentasi:
- **SOAP**: Subjective, Objective, Assessment, Plan
- **CPPT**: Catatan Perkembangan Pasien Terintegrasi
- **ICD-10**: International Classification of Diseases 10th Revision

---

## Cara Membuka EMR Pasien

### Langkah-langkah:

#### 1. Dari Antrian Poliklinik:

1. Login ke SIMRS dengan akun dokter/perawat
2. Klik menu **"Rekam Medis"** → **"Antrian Pasien"**
3. Pilih **poliklinik** Anda
4. Lihat daftar pasien yang menunggu
5. Klik tombol **"Buka EMR"** pada pasien yang akan diperiksa
6. Sistem akan menampilkan halaman EMR lengkap

#### 2. Dari Pencarian Pasien:

1. Klik menu **"Rekam Medis"** → **"Cari Pasien"**
2. Masukkan **No. RM**, **NIK**, atau **Nama Pasien**
3. Pilih pasien dari hasil pencarian
4. Klik **"Lihat Riwayat"** atau **"Buka EMR"**

#### 3. Dari Notifikasi:

1. Perhatikan icon lonceng 🔔 di header
2. Klik untuk melihat notifikasi pasien baru masuk
3. Klik nama pasien untuk langsung membuka EMR

### Komponen Halaman EMR:

| Panel | Keterangan |
|-------|------------|
| **Panel Kiri** | Data pasien (demografi, kontak, penjamin) |
| **Panel Tengah** | Form pengisian SOAP/CPPT |
| **Panel Kanan** | Riwayat kunjungan sebelumnya |
| **Tab Bawah** | Riwayat lab, radiologi, resep obat |

### Screenshot:
![Halaman EMR](../images/emr-halaman.png)

### Status EMR:

| Status | Warna | Keterangan |
|--------|-------|------------|
| 🟡 Draft | Kuning | Sedang diisi, belum selesai |
| 🔵 Proses | Biru | Sedang pemeriksaan |
| 🟢 Final | Hijau | Sudah final, tidak bisa diubah |
| ⚪ Batal | Abu-abu | Kunjungan dibatalkan |

---

## Cara Mengisi SOAP

SOAP adalah format dokumentasi medis standar yang terdiri dari 4 komponen.

### Langkah-langkah Mengisi SOAP:

#### 1. S - Subjective (Keluhan & Riwayat):

**Klik tab "SOAP"** kemudian isi bagian Subjective:

| Field | Keterangan | Contoh Pengisian |
|-------|------------|------------------|
| **Keluhan Utama** | Keluhan utama pasien | Sakit kepala berdenyut sejak 2 hari |
| **Riwayat Penyakit Sekarang** | Detail keluhan | Nyeri di area frontalis, intensitas sedang-berat |
| **Riwayat Penyakit Dahulu** | Penyakit yang pernah diderita | Hipertensi sejak 5 tahun |
| **Riwayat Alergi** | Alergi obat/makanan | Alergi amoxicillin (muncul biduran) |
| **Riwayat Pengobatan** | Obat yang sedang dikonsumsi | Amlodipine 5mg 1x1 |
| **Riwayat Keluarga** | Penyakit dalam keluarga | Ayah: DM, Ibu: Hipertensi |

#### 2. O - Objective (Hasil Pemeriksaan):

| Field | Keterangan | Contoh Pengisian |
|-------|------------|------------------|
| **TTV** | Tanda-tanda vital | TD: 140/90, N: 80x/m, S: 36.5°C, R: 18x/m |
| **Pemeriksaan Fisik** | Temuan fisik | Kesadaran: CM, Kepala: normal, Thorax: normal |
| **Pemeriksaan Penunjang** | Hasil lab/radiologi | Hb: 13.5, Leukosit: 8000 |

#### 3. A - Assessment (Diagnosis):

| Field | Keterangan | Contoh Pengisian |
|-------|------------|------------------|
| **Diagnosis Utama** | Diagnosis primer | G44.2 - Tension-type headache |
| **Diagnosis Sekunder** | Diagnosis sekunder | I10 - Essential (primary) hypertension |
| **Kode ICD10** | Kode diagnosis | G44.2, I10 |

#### 4. P - Plan (Rencana Tindakan):

| Field | Keterangan | Contoh Pengisian |
|-------|------------|------------------|
| **Rencana Pengobatan** | Obat yang diberikan | Paracetamol 500mg 3x1 |
| **Tindakan Medis** | Prosedur yang dilakukan | Anjuran istirahat |
| **Edukasi** | Edukasi pasien | Hindari pemicu stres |
| **Rencana Kontrol** | Jadwal kontrol | Kontrol 1 minggu |
| **Konsul/Rujukan** | Rujukan poli lain | - |

### Screenshot:
![Form SOAP](../images/soap-form.png)

### Template SOAP:

Gunakan template untuk pengisian lebih cepat:

1. Klik **"Load Template"**
2. Pilih template sesuai kasus:
   - Template Hipertensi
   - Template DM
   - Template ISPA
   - Template Diare
   - Dst
3. Sesuaikan dengan kondisi pasien aktual
4. Simpan sebagai draft jika perlu

---

## Cara Mengisi CPPT

CPPT (Catatan Perkembangan Pasien Terintegrasi) digunakan untuk:
- Rawat inap
- Pasien dengan kunjungan berulang
- Kolaborasi multi-disiplin

### Struktur CPPT:

| Kolom | Diisi Oleh | Keterangan |
|-------|------------|------------|
| **S (Subjective)** | Perawat/Dokter | Keluhan pasien |
| **O (Objective)** | Perawat | TTV, hasil monitoring |
| **A (Assesment)** | Dokter | Analisis data |
| **P (Planning)** | Dokter | Rencana tindakan |
| **I (Implementasi)** | Perawat | Tindakan yang dilakukan |
| **E (Evaluasi)** | Dokter/Perawat | Hasil tindakan |

### Langkah-langkah Mengisi CPPT:

#### Untuk Rawat Jalan (CPPT Singkat):

1. Buka EMR pasien
2. Klik tab **"CPPT"**
3. Klik **"+ Tambah CPPT"**
4. Isi form:

```
S: Pasien mengeluh mual sejak pagi
O: TD 120/80, N 76x/m, GDS 180 mg/dL
A: E11.9 - Type 2 diabetes mellitus
P: 1. Metformin 500mg 2x1
   2. Diet DM rendah gula
   3. Kontrol 2 minggu
I: Edukasi diet diberikan
E: Pasien mengerti, mau kontrol ulang
```

5. Klik **"Simpan"**

#### Untuk Rawat Inap (CPPT Lengkap):

1. Buka EMR pasien rawat inap
2. Pilih tanggal dan shift:
   - Pagi (07:00-14:00)
   - Sore (14:00-21:00)
   - Malam (21:00-07:00)
3. Isi bagian sesuai profesi:
   - **Perawat**: Isi S, O, I
   - **Dokter**: Isi A, P, E
4. Tambahkan **TTV** di kolom Objective
5. Klik **"Simpan"**

### Screenshot:
![Form CPPT](../images/cppt-form.png)

### Riwayat CPPT:

- Lihat perkembangan pasien secara kronologis
- Bandingkan TTV dari waktu ke waktu
- Filter berdasarkan tanggal
- Print CPPT untuk dokumen eksternal

---

## Cara Mengisi Asesmen & TTV

### Pengisian Tanda-Tanda Vital (TTV):

#### A. TTV Dasar:

| Parameter | Normal | Satuan | Cara Ukur |
|-----------|--------|--------|-----------|
| Tekanan Darah | 120/80 | mmHg | Tensimeter |
| Nadi | 60-100 | x/menit | Palpasi radial |
| Suhu | 36.5-37.5 | °C | Termometer |
| Pernapasan | 16-20 | x/menit | Inspeksi |
| SpO2 | ≥95 | % | Pulse oximeter |

#### B. TTV Lanjutan:

| Parameter | Keterangan | Satuan |
|-----------|------------|--------|
| Berat Badan | BB saat ini | kg |
| Tinggi Badan | TB saat ini | cm |
| IMT | Indeks Massa Tubuh | kg/m² |
| Lingkar Kepala (bayi) | UK | cm |
| Lingkar Lengan Atas | LILA | cm |

### Langkah Mengisi TTV:

1. Di halaman EMR, cari bagian **"TTV"** atau **"Tanda Vital"**
2. Klik **"+ Input TTV"**
3. Masukkan nilai hasil pengukuran:
   ```
   Tekanan Darah Sistole: 140
   Tekanan Darah Diastole: 90
   Nadi: 85
   Suhu: 36.8
   Pernapasan: 18
   SpO2: 98
   Berat Badan: 70
   Tinggi Badan: 165
   ```
4. Sistem otomatis menghitung **IMT**: 25.7 (Overweight)
5. Pilih **Kesadaran**:
   - Compos Mentis (CM)
   - Apatis
   - Somnolen
   - Sopor
   - Coma
6. Klik **"Simpan TTV"**

### Form Asesmen Awal Medis:

#### A. Asesmen Rawat Jalan:

| Aspek | Parameter |
|-------|-----------|
| **Keluhan Utama** | Keluhan yang membawa pasien berobat |
| **Riwayat Penyakit** | Riwayat penyakit dahulu dan sekarang |
| **Riwayat Alergi** | Alergi obat, makanan, lingkungan |
| **Pemeriksaan Fisik** | Status generalis dan lokalis |
| **Skoring** | NRS (nyeri), GCS (kesadaran), dll |

#### B. Asesmen Rawat Inap:

| Aspek | Parameter |
|-------|-----------|
| **Asesmen Keperawatan** | ADL, risiko jatuh, nutrisi |
| **Asesmen Medis** | Diagnosis, komorbid |
| **Asesmen Farmasi** | Riwayat obat, interaksi obat |
| **Asesmen Gizi** | Status gizi, kebutuhan kalori |

### Screenshot:
![Form TTV](../images/ttv-form.png)

---

## Cara Memasukkan Diagnosis ICD10

### Apa itu ICD10?

ICD-10 (International Classification of Diseases 10th Revision) adalah sistem klasifikasi diagnosis penyakit standar internasional yang digunakan untuk:
- Koding diagnosis
- Pelaporan RL (Rumah Sakit)
- Klaim BPJS/Asuransi
- Riset dan statistik kesehatan

### Langkah Memasukkan Diagnosis:

#### 1. Pencarian Diagnosis:

**Metode A - Pencarian Kode:**
1. Klik field **"Diagnosis"** atau **"ICD10"**
2. Masukkan **kode ICD10** (contoh: I10)
3. Sistem akan menampilkan deskripsi: "Essential (primary) hypertension"

**Metode B - Pencarian Deskripsi:**
1. Ketik **deskripsi penyakit** (contoh: "hipertensi")
2. Sistem akan menampilkan daftar diagnosis yang sesuai
3. Pilih yang paling tepat:
   - I10 - Essential (primary) hypertension
   - I11 - Hypertensive heart disease
   - I15 - Secondary hypertension

**Metode C - Pencarian Parsial:**
1. Ketik sebagian kata (min. 3 huruf)
2. Contoh: "sakit kepala" → akan muncul berbagai jenis sakit kepala

#### 2. Memilih Jenis Diagnosis:

| Jenis | Keterangan | Contoh |
|-------|------------|--------|
| **Utama** | Diagnosis primer | J06.9 - Acute upper respiratory infection |
| **Sekunder** | Diagnosis sekunder | E11.9 - Type 2 DM |
| **Komplikasi** | Komplikasi | N18.9 - Chronic kidney disease |
| **Penyerta** | Penyakit penyerta | I10 - Hypertension |

#### 3. Kasus Khusus ICD10:

| Kasus | Kode | Keterangan |
|-------|------|------------|
| DBD Dengue | A90 | Classical dengue |
| DBD dengan tanda bahaya | A91 | Dengue haemorrhagic fever |
| ISPA non-spesifik | J06.9 | Acute URI |
| Diare akut | A09 | Diarrhoea and gastroenteritis |
| Kehamilan | Z34 | Normal pregnancy |
| Kontrol rutin | Z00.0 | General medical examination |

### Screenshot:
![Pencarian ICD10](../images/icd10-search.png)

### Tips Pemilihan ICD10:

1. **Pilih yang paling spesifik**
   - ❌ Kode: J06 - Acute upper respiratory infections
   - ✅ Kode: J06.9 - Acute URI, unspecified

2. **Perhatikan catatan khusus ICD10**
   - "Use additional code"
   - "Excludes"
   - "Code also"

3. **Untuk diagnosa belum pasti**, gunakan:
   - R50.9 - Fever, unspecified
   - R51 - Headache
   - R10.4 - Other and unspecified abdominal pain

---

## Cara Finalisasi Rekam Medis

### Proses Finalisasi:

Finalisasi adalah proses mengunci EMR agar tidak dapat diubah lagi. Setelah difinalisasi:
- EMR menjadi dokumen legal
- Resep dikirim ke farmasi
- Tagihan keuangan di-generate

#### Langkah Finalisasi:

1. **Pastikan semua bagian terisi:**
   - ✅ SOAP/CPPT lengkap
   - ✅ TTV terisi
   - ✅ Diagnosis ICD10 dimasukkan
   - ✅ Resep obat ditulis (jika ada)
   - ✅ Rencana tindakan jelas

2. **Verifikasi kembali:**
   - Cek nama pasien
   - Cek tanggal dan jam
   - Cek diagnosis
   - Cek obat yang diresepkan

3. **Klik tombol "Finalisasi"** (biasanya berwarna hijau)

4. **Konfirmasi finalisasi:**
   - Baca peringatan bahwa data tidak bisa diubah
   - Centang kotak konfirmasi
   - Masukkan password (jika diminta)

5. **Klik "Ya, Finalisasi"**

6. **Status EMR berubah** dari "Draft" menjadi "Final"

### Screenshot:
![Tombol Finalisasi](../images/finalisasi.png)

### Pembatalan Finalisasi:

> ⚠️ **PERINGATAN**: Pembatalan finalisasi memerlukan otorisasi supervisor.

Jika terjadi kesalahan setelah finalisasi:
1. Hubungi supervisor dokter/spv rekam medis
2. Ajukan permohonan pembukaan kembali EMR
3. Berikan alasan yang jelas
4. Setelah disetujui, EMR dapat diedit kembali
5. Finalisasi ulang setelah perbaikan

---

## Cara Mencetak Rekam Medis

### Jenis Cetakan EMR:

| Jenis | Kapan Digunakan | Format |
|-------|-----------------|--------|
| Resume Medis | Pasien pulang/rujuk | A4 |
| Ringkasan Pasien | Kontrol poli lain | A4 |
| Surat Rujukan | Rujuk ke RS lain | A4 |
| Copy Resep | Duplikat resep | A5 |
| Status Pasien | Identifikasi pasien | Kartu nama |

### Langkah Mencetak:

#### A. Cetak Resume Medis:

1. Buka EMR pasien yang sudah final
2. Klik tombol **"Cetak"** atau icon 🖨️
3. Pilih **"Resume Medis"**
4. Pilih **format**:
   - Format Standar
   - Format BPJS
   - Format Asuransi
5. Klik **"Preview"** untuk melihat hasil
6. Jika sudah sesuai, klik **"Print"**
7. Pilih printer dan klik **"OK"**

#### B. Cetak Surat Rujukan:

1. Di halaman EMR, klik **"Rujukan"**
2. Isi data rujukan:
   - RS/Faskes tujuan
   - Alasan rujukan
   - Hasil pemeriksaan
   - Terapi yang sudah diberikan
3. Klik **"Cetak Surat Rujukan"**
4. Tanda tangan dan stempel dokter

### Screenshot:
![Preview Cetak](../images/cetak-emr.png)

### Pengaturan Printer:

**Untuk Cetak A4:**
1. Pastikan printer kertas A4 tersedia
2. Atur margin: Top 2cm, Bottom 2cm, Left 2.5cm, Right 2cm
3. Gunakan kertas HVS 80gr untuk hasil terbaik

**Untuk Cetak Copy Resep (A5):**
1. Atur ukuran kertas: A5 (148 x 210 mm)
2. Atur orientasi: Landscape (jika template mendukung)
3. Pastikan printer mendukung kertas A5

---

## Tips dan Troubleshooting

### Tips Pengisian EMR yang Efektif:

1. **Gunakan Template** untuk kasus yang sering ditemui
2. **Copy dari Kunjungan Sebelumnya** jika pasien kontrol dengan keluhan sama
3. **Singkat tapi Jelas** - Hindari berlebihan, fokus pada informasi klinis penting
4. **Gunakan Bahasa Medis Standar**
5. **Simpan Draft** secara berkala saat mengisi

### Do's and Don'ts:

| ✅ DO's | ❌ DON'Ts |
|---------|-----------|
| Gunakan singkatan medis standar | Tulis dengan huruf kapital semua |
| Cantumkan tanggal dan jam setiap entry | Meninggalkan form kosong tanpa alasan |
| Coret salah ketik dengan garis tipis | Menggunakan tip-ex atau menghapus |
| Tanda tangani setiap entry | Menandatangani sebelum mengisi |

### Troubleshooting Umum:

#### 1. EMR Tidak Bisa Diedit

**Gejala**: Tombol simpan tidak aktif

**Solusi**:
- Cek status EMR (mungkin sudah final)
- Cek role pengguna (hanya dokter yang bisa edit)
- Refresh halaman (F5)

#### 2. ICD10 Tidak Ditemukan

**Gejala**: Diagnosis yang dicari tidak muncul

**Solusi**:
- Coba kata kunci lain
- Cek ejaan
- Gunakan kode numerik langsung
- Hubungi bagian coding untuk kode spesifik

#### 3. Resep Tidak Terkirim ke Farmasi

**Gejala**: Farmasi tidak menerima resep

**Solusi**:
- Pastikan EMR sudah difinalisasi
- Cek koneksi jaringan
- Verifikasi farmasi sudah buka
- Kirim ulang dari menu "Resep"

#### 4. Data TTV Tidak Tersimpan

**Gejala**: TTV yang diisi hilang

**Solusi**:
- Pastikan klik "Simpan" setelah mengisi
- Cek koneksi internet
- Isi ulang dan simpan kembali

#### 5. Printer Tidak Merespon

**Gejala**: Tidak bisa cetak resume/surat

**Solusi**:
- Cek printer (nyala, kertas, tinta)
- Cek koneksi USB/Network
- Restart printer spooler
- Coba cetak test page dari Windows

### Shortcut Keyboard:

| Shortcut | Fungsi |
|----------|--------|
| `Ctrl + S` | Simpan draft |
| `Ctrl + P` | Cetak |
| `F5` | Refresh halaman |
| `Tab` | Pindah ke field berikutnya |
| `Ctrl + F` | Cari dalam halaman |

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem EMR | IT Support Ext. 8888 |
| Koding ICD10 | Rekam Medis Ext. 5555 |
| Alur klinis | Kepala Ruangan masing-masing |
| Masalah BPJS | BPJS Helpdesk Ext. 5678 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
