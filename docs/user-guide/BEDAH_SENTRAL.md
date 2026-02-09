# Panduan Modul OK/Bedah Sentral

Panduan lengkap untuk menggunakan modul Bedah Sentral (Kamar Operasi) SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Bedah Sentral](#pengenalan-modul-bedah-sentral)
2. [Cara Menjadwalkan Operasi](#cara-menjadwalkan-operasi)
3. [Cara Mengisi Safety Checklist](#cara-mengisi-safety-checklist)
4. [Cara Mencatat Implant/BHP](#cara-mencatat-implantbhp)
5. [Cara Mengisi Laporan Operasi](#cara-mengisi-laporan-operasi)
6. [Cara Reschedule Operasi](#cara-reschedule-operasi)
7. [Cara Membatalkan Operasi](#cara-membatalakan-operasi)
8. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Bedah Sentral

Modul Bedah Sentral mengelola seluruh aktivitas operasi di rumah sakit, mulai dari penjadwalan, persiapan operasi, pelaksanaan operasi, hingga pencatatan laporan dan billing.

### Komponen Modul:

| Komponen | Fungsi |
|----------|--------|
| **Jadwal Operasi** | Booking dan jadwal OK |
| **Safety Checklist** | Sign-in, Time-out, Sign-out |
| **Implant Tracking** | Pencatatan implant dan BHP |
| **Laporan Operasi** | Dokumentasi tindakan bedah |
| **Billing OK** | Tarif operasi dan alat |

### Tipe Operasi:

| Kategori | Keterangan | Contoh |
|----------|------------|--------|
| **Elektif** | Terjadwal | Hernia, Katarak |
| **Emergency** | Darurat | Apendisitis, Trauma |
| **Semi-Elektif** | Perlu segera tapi bisa tunggu | Kolesistitis |

### Status Operasi:

| Status | Warna | Keterangan |
|--------|-------|------------|
| 🟡 Booking | Kuning | Sudah dijadwalkan |
| 🔵 Siap Operasi | Biru | Persiapan selesai |
| 🟢 On-Table | Hijau | Sedang operasi |
| 🟠 Recovery | Orange | Di RR/PACU |
| 🔵 Selesai | Biru | Pulang dari OK |
| 🔴 Batal | Merah | Dibatalkan |

---

## Cara Menjadwalkan Operasi

### Jenis Penjadwalan:

#### 1. Elektif (Terjadwal):

Dijadwalkan minimal 24 jam sebelumnya.

#### 2. Emergency:

Langsung ke OK tanpa jadwal, setelah persiapan minimal.

### Langkah Penjadwalan:

#### 1. Order Operasi dari Dokter:

1. Dokter buka EMR pasien
2. Klik **"Order Operasi"** atau **"Booking OK"**
3. Isi form permintaan:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **Diagnosis Pre-Op** | Diagnosis sebelum operasi | Appendisitis Akut |
| **Tindakan yang Akan Dilakukan** | Nama operasi | Appendektomi |
| **Kode ICD-9-CM** | Kode prosedur | 47.0 |
| **Dokter Operator** | Nama dokter bedah | dr. Santoso, Sp.B |
| **Asisten** | Dokter asisten | dr. Budi |
| **Estimasi Durasi** | Lama operasi | 90 menit |
| **Jenis Anastesi** | Umum/Spinal/Lokal | General Anastesi |
| **Dokter Anestesi** | Nama dokter anestesi | dr. Ani, Sp.An |
| **Implant yang Diperlukan** | Mesh, plate, dll | - |
| **Tanggal Diinginkan** | Jadwal yang diinginkan | 10/02/2026 |

#### 2. Koordinasi Jadwal:

1. Koordinator OK menerima order
2. Cek ketersediaan:
   - Ruang operasi
   - Dokter operator
   - Dokter anestesi
   - Perawat OK
   - Peralatan khusus

3. Tentukan **tanggal dan jam operasi**

#### 3. Input Jadwal:

1. Login ke **Bedah Sentral**
2. Klik **"Jadwal Operasi"** → **"+ Jadwal Baru"**
3. Pilih pasien dari order masuk
4. Tentukan:
   - Tanggal operasi
   - Jam mulai
   - Ruang operasi (OK 1/OK 2/OK 3)
   - Regulasi antrian

5. Klik **"Simpan Jadwal"**
6. Sistem notifikasi:
   - Ke dokter operator
   - Ke dokter anestesi
   - Ke pasien (SMS/WA)
   - Ke ruangan rawat (jika pasien RI)

### Screenshot:
![Jadwal Operasi](../images/jadwal-operasi.png)

### Form Booking Operasi:

```
┌─────────────────────────────────────────┐
│         BOOKING OPERASI                 │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ No. Booking: OK-20260208-001            │
│ No. RM: 000123                          │
│ Nama: AHMAD SUSANTO                     │
├─────────────────────────────────────────┤
│ DIAGNOSA PRE-OP:                        │
│ Hernia Inguinalis Lateralis Dextra      │
├─────────────────────────────────────────┤
│ TINDAKAN OPERASI:                       │
│ Hernioraphy Mesh Dextra                 │
│ Kode ICD-9: 53.04                       │
├─────────────────────────────────────────┤
│ TIM OPERASI:                            │
│ Operator: dr. Santoso, Sp.B             │
│ Asisten: dr. Budi                       │
│ Anestesi: dr. Ani, Sp.An                │
│ Perawat: Siti Aminah, Amd.Kep           │
├─────────────────────────────────────────┤
│ JADWAL:                                 │
│ Tanggal: 10 Februari 2026               │
│ Jam: 08:00 WIB                          │
│ Ruang: OK 1                             │
│ Estimasi: 120 menit                     │
├─────────────────────────────────────────┤
│ PERMINTAAN KHUSUS:                      │
│ ☑ Mesh hernia ukuran 10x15 cm           │
│ ☐ Implant lain: _______________         │
│ ☐ Peralatan khusus: ____________        │
├─────────────────────────────────────────┤
│ Status: ☐ Elektif ☑ Emergency           │
│ Puasa dari: 10/02/2026 00:00            │
└─────────────────────────────────────────┘
```

### Persiapan Pre-Operatif:

| Aspek | Persiapan |
|-------|-----------|
| **Puasa** | Minimal 6-8 jam |
| **Informed Consent** | Tanda tangan pasien/keluarga |
| **Pre-op Lab** | Cek darah lengkap, EKG (jika >40 th) |
| **Skin Prep** | Cukur area operasi |
| **Premedikasi** | Atropin, Midazolam (jika perlu) |

---

## Cara Mengisi Safety Checklist

### WHO Surgical Safety Checklist:

Safety checklist terdiri dari 3 fase:
1. **Sign In** - Sebelum induksi anestesi
2. **Time Out** - Sebelum sayatan pertama
3. **Sign Out** - Sebelum pasien keluar OK

### 1. Sign In (Before Induction of Anaesthesia):

Dilakukan sebelum pasien diinduksi.

| Cek Item | Ya | Tidak | N/A |
|----------|-----|-------|-----|
| Pasien telah konfirmasi identitas | ☐ | ☐ | ☐ |
| Site/ lokasi operasi ditandai | ☐ | ☐ | ☐ |
| Prosedur dikonfirmasi | ☐ | ☐ | ☐ |
| Consent ditandatangani | ☐ | ☐ | ☐ |
| Monitor pasien terpasang (pulse oxymeter) | ☐ | ☐ | ☐ |
| Alergi diketahui | ☐ | ☐ | ☐ |
| Risiko aspirasi | ☐ | ☐ | ☐ |
| Risiko pendarahan > 500ml (7ml/kg anak) | ☐ | ☐ | ☐ |

**Input di Sistem:**
1. Buka jadwal operasi
2. Klik **"Sign In"**
3. Centang setiap item
4. Input paraf:
   - Dokter anestesi
   - Dokter operator
   - Perawat sirkulasi
5. Klik **"Simpan"**

### 2. Time Out (Before Skin Incision):

Dilakukan sebelum sayatan pertama.

| Cek Item | Ya | Tidak |
|----------|-----|-------|
| Semua anggota tim memperkenalkan diri dengan nama | ☐ | ☐ |
| Konfirmasi nama pasien | ☐ | ☐ |
| Konfirmasi tindakan yang akan dilakukan | ☐ | ☐ |
| Konfirmasi lokasi operasi | ☐ | ☐ |
| Review antibiotik profilaksis diberikan 60 menit sebelum sayatan | ☐ | ☐ |
| Review antisipasi event kritis | ☐ | ☐ |
| Review kelengkapan alat (X-ray, implan, dll) | ☐ | ☐ |
| Sterilisasi instrumen dikonfirmasi | ☐ | ☐ |

**Input di Sistem:**
1. Saat akan mulai operasi
2. Klik **"Time Out"**
3. Konfirmasi bersama tim
4. Centang semua item
5. Paraf semua anggota tim
6. Klik **"Simpan"**

### 3. Sign Out (Before Patient Leaves Operating Room):

Dilakukan sebelum pasien meninggalkan meja operasi.

| Cek Item | Ya | Tidak | N/A |
|----------|-----|-------|-----|
| Konfirmasi nama prosedur | ☐ | ☐ | ☐ |
| Hitung instrumen, kasa, jarum sesuai | ☐ | ☐ | ☐ |
| Spesimen sudah diberi label (baca label) | ☐ | ☐ | ☐ |
| Peralatan berfungsi dengan baik | ☐ | ☐ | ☐ |
| Masalah peralatan untuk diperbaiki | ☐ | ☐ | ☐ |
| Perawatan post-op dan recovery sudah direncanakan | ☐ | ☐ | ☐ |

**Input di Sistem:**
1. Setelah operasi selesai
2. Klik **"Sign Out"**
3. Verifikasi semua item
4. Paraf tim
5. Klik **"Simpan"**

### Screenshot:
![Safety Checklist](../images/safety-checklist.png)

### Dokumentasi Checklist:

Sistem akan generate laporan:
```
┌─────────────────────────────────────────┐
│      WHO SURGICAL SAFETY CHECKLIST      │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ Pasien: AHMAD SUSANTO                   │
│ No. RM: 000123                          │
│ Tindakan: Appendektomi                  │
│ Tanggal: 08/02/2026                     │
├─────────────────────────────────────────┤
│ SIGN IN (14:30)                         │
│ ☐ Identitas konfirmasi                  │
│ ☐ Consent lengkap                       │
│ ☐ Site marking                          │
│ ☐ Monitor terpasang                     │
│ Anestesi: _______ Ttd: _______          │
├─────────────────────────────────────────┤
│ TIME OUT (14:45)                        │
│ ☐ Tim intro                             │
│ ☐ Konfirmasi pasien-prosedur-site       │
│ ☐ Antibiotik profilaksis                │
│ ☐ Instrumen lengkap                     │
│ Operator: _______ Ttd: _______          │
├─────────────────────────────────────────┤
│ SIGN OUT (16:15)                        │
│ ☐ Count complete                        │
│ ☐ Spesimen label                        │
│ ☐ Post-op plan                          │
│ Sirkulasi: _______ Ttd: _______         │
└─────────────────────────────────────────┘
```

---

## Cara Mencatat Implant/BHP

### Jenis Implant:

| Kategori | Contoh |
|----------|--------|
| **Ortopedi** | Plate, screw, prosthesis sendi |
| **Jantung** | Ring jantung, stent, pacemaker |
| **Bedah Umum** | Mesh hernia, drain |
| **Neurosurgery** | Clip aneurisma, shunt |
| **Plastik** | Implant payudara |

### Pencatatan Implant:

#### 1. Input Data Implant:

1. Saat **Sign Out** atau setelahnya
2. Klik **"Catat Implant"**
3. Input setiap implant:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **Nama Implant** | Nama alat | Mesh Hernia |
| **Merk** | Brand | Ethicon |
| **Tipe/Model** | Model | Proceed |
| **Ukuran** | Spesifikasi | 10x15 cm |
| **No. Batch/SN** | Nomor seri | ABC123456 |
| **Jumlah** | Quantity | 1 |
| **Expired Date** | Masa berlaku | 12/2027 |
| **Harga Satuan** | Biaya | Rp 2.500.000 |

#### 2. Tracking Implant:

Sistem akan mencatat:
- Pasien yang dipasangi
- Tanggal pemasangan
- Dokter yang memasang
- Nomor batch (untuk recall jika ada masalah)

### Screenshot:
![Catat Implant](../images/catat-implant.png)

### BHP (Bahan Habis Pakai) Operasi:

| Kategori | Contoh |
|----------|--------|
| **Suture** | Silk, Vicryl, PDS, Prolene |
| **Disposable** | Handscone, masker, syringe |
| **Instrumen** | Blade, stapler |
| **Dressing** | Kasa, plester |

**Input BHP:**
- Bisa otomatis dari paket operasi
- Atau manual input jika ada tambahan
- Terintegrasi dengan billing

---

## Cara Mengisi Laporan Operasi

### Struktur Laporan Operasi:

#### 1. Data Umum:

```
┌─────────────────────────────────────────┐
│         LAPORAN OPERASI                 │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ DATA PASIEN:                            │
│ Nama: AHMAD SUSANTO                     │
│ No. RM: 000123                          │
│ Tgl Lahir: 15/08/1985 (40 tahun)        │
│ Jenis Kelamin: Laki-laki                │
├─────────────────────────────────────────┤
│ DATA OPERASI:                           │
│ Tanggal: 08 Februari 2026               │
│ Jam Mulai: 14:45 WIB                    │
│ Jam Selesai: 16:15 WIB                  │
│ Durasi: 90 menit                        │
│ Ruang Operasi: OK 1                     │
├─────────────────────────────────────────┤
│ TIM OPERASI:                            │
│ Operator: dr. Santoso, Sp.B             │
│ Asisten: dr. Budi                       │
│ Instrumen: Siti Aminah, Amd.Kep         │
│ Anestesi: dr. Ani, Sp.An                │
│ Jenis Anestesi: General Anestesi        │
└─────────────────────────────────────────┘
```

#### 2. Deskripsi Operasi:

| Bagian | Isi |
|--------|-----|
| **Diagnosis Pre-Op** | Diagnosis sebelum operasi |
| **Diagnosis Post-Op** | Diagnosis setelah operasi (bisa berbeda) |
| **Tindakan** | Nama prosedur yang dilakukan |
| **Indikasi** | Alasan dilakukan operasi |
| **Deskripsi Procedur** | Langkah-langkah operasi secara detail |
| **Temuan** | Temuan saat operasi |
| **Komplikasi** | Jika ada komplikasi |
| **Perkiraan Pendarahan** | Volume darah hilang |
| **Jaringan ke Lab** | Spesimen yang dikirim |

#### 3. Contoh Deskripsi Operasi (Appendektomi):

```
DIAGNOSIS PRE-OPERATIF:
Appendisitis Akut

DIAGNOSIS POST-OPERATIF:
Appendisitis Akut Flegmonosa

TINDAKAN OPERATIF:
Appendektomi Terbuka (Open Appendectomy)

INDIKASI:
Pasien laki-laki 40 tahun dengan nyeri abdomen kanan 
bawah 2 hari, mual, muntah, demam. Hasil USG: 
appendiks tebal, periapendikular abses kecil.

DESKRIPSI PROSEDUR:
Setelah anestesi umum dengan intubasi, pasien dalam 
posisi supine. Posisi kamar Trendelenburg 15°. 
Area operasi dibetadine dan ditutup kain operasi.

Insisi McBurney sepanjang 5 cm di abdomen kanan bawah. 
Lapisan dibuka bertahap: kulit, subkutis, fasia, otot, 
peritoneum. Masuk rongga abdomen, identifikasi caecum. 
Appendiks tampak edema, flegmonosa, tertutup omentum. 
Appendiks diisolasi, mesoappendiks di-clamp dan diikat 
dengan 2-0 silk. Basis appendiks di-crush dan di-ligate 
dengan 2-0 chromic. Appendiks dipotong di atas ligatur. 
Stump di-cauterize dan dilakukan invaginasi (purse string). 
Hemostasis baik, cavity di-irrigate dengan saline hangat. 
Peritoneum ditutup dengan 2-0 vicryl kontinu. 
Fasia dengan 2-0 vicryl. Subkutis dengan 3-0 vicryl. 
Kulit dengan stapler kulit.

TEMUAN:
Appendiks edema, diameter 1.5 cm, flegmonosa, 
tidak perforasi. Cairan sedikit di kavum Douglas.

KOMPLIKASI:
Tidak ada

PERKIRAAN PENDARAHAN:
Minimal (sekitar 50 cc)

JARINGAN KE PATOLOGI ANATOMI:
Appendiks dengan mesoappendiks untuk pemeriksaan 
histopatologi

KONDISI PASIEN AKHIR OPERASI:
Stabil, sadar, ekstubasi spontan

RENCANA PASCABEDAH:
1. NPO sampai peristaltik kembali
2. Antibiotik Ceftriaxone 2x1 gr IV
3. Analgetik
4. Cek Hb 24 jam post-op
5. Kontrol luka jahitan 7 hari
```

#### 4. Input ke Sistem:

1. Selesai operasi, buka jadwal operasi
2. Klik **"Laporan Operasi"**
3. Pilih **template** (jika tersedia)
4. Isi semua bagian:
   - Data umum (otomatis dari jadwal)
   - Diagnosis
   - Deskripsi prosedur
   - Temuan
   - Komplikasi
5. Klik **"Simpan"**
6. Print untuk tanda tangan

### Screenshot:
![Laporan Operasi](../images/laporan-operasi.png)

---

## Cara Reschedule Operasi

### Alasan Reschedule:

- Pasien belum siap medis
- Dokter ada emergency
- Peralatan tidak tersedia
- Ruang operasi digunakan emergency
- Pasien membatalkan

### Langkah Reschedule:

1. Buka **"Jadwal Operasi"**
2. Cari operasi yang akan di-reschedule
3. Klik **"Reschedule"**
4. Pilih **alasan reschedule**
5. Tentukan **tanggal dan jam baru**
6. Klik **"Simpan"**
7. Sistem notifikasi:
   - Semua pihak terkait
   - Pasien (SMS/WA)

### Screenshot:
![Reschedule Operasi](../images/reschedule.png)

---

## Cara Membatalkan Operasi

### Skenario Pembatalan:

| Skenario | Penanganan |
|----------|------------|
| Batal oleh pasien | Dokumentasi alasan |
| Batal kondisi medis | Kaji ulang kondisi |
| Batal administratif | Jadwal ulang |

### Langkah Pembatalan:

1. Buka **"Jadwal Operasi"**
2. Cari operasi yang akan dibatalkan
3. Klik **"Batal"**
4. Pilih **alasan pembatalan**:
   - Kondisi pasien tidak memungkinkan
   - Pasien meminta batal
   - Dokter tidak bisa
   - Emergency lain
   - Lainnya

5. Isi **keterangan** detail
6. Klik **"Konfirmasi Batal"**
7. Sistem update status: "Batal"
8. Notifikasi ke semua pihak

### Screenshot:
![Batal Operasi](../images/batal-operasi.png)

---

## Tips dan Troubleshooting

### Tips Kerja di OK:

1. **Persiapan Minimal**:
   - Count instrumen lengkap
   - Cek fungsi alat
   - Sterilisasi terverifikasi

2. **Komunikasi**:
   - Time out jangan dilewatkan
   - Konfirmasi setiap tindakan
   - Dokumentasi lengkap

3. **Keamanan**:
   - Identifikasi pasien triple check
   - Site marking jelas
   - Counting x3 (mulai, tambahan, akhir)

### Troubleshooting:

#### 1. Count Tidak Sesuai

**Gejala**: Instrumen/kasa/jarum tidak balance

**Solusi**:
1. STOP operasi
2. Cari benda yang hilang
3. X-ray jika perlu
4. Dokumentasikan
5. Lanjut jika sudah ditemukan

#### 2. Implant Tidak Tersedia

**Gejala**: Implant yang dipesan tidak ada

**Solusi**:
1. Cek stok gudang
2. Koordinasi dengan supplier emergency
3. Pertimbangkan alternatif teknik
4. Reschedule jika tidak bisa diganti

#### 3. Sistem Mati Saat Operasi

**Solusi**:
1. Gunakan lembar manual
2. Dokumentasi lengkap di kertas
3. Input ke sistem setelah nyala
4. Jangan tunda tindakan karena sistem

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Peralatan OK | IPSRS Ext. 7777 |
| Implant | Gudang OK Ext. 6666 |
| Jadwal | Koordinator OK Ext. 5555 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
