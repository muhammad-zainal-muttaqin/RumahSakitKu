# Panduan Modul Rawat Inap

Panduan lengkap untuk menggunakan modul Rawat Inap SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Rawat Inap](#pengenalan-modul-rawat-inap)
2. [Cara Mengadmissi Pasien Rawat Inap](#cara-mengadmissi-pasien-rawat-inap)
3. [Cara Mengassign Kamar dan Bed](#cara-mengassign-kamar-dan-bed)
4. [Cara Pindah Kamar](#cara-pindah-kamar)
5. [Cara Mengisi EMR Rawat Inap](#cara-mengisi-emr-rawat-inap)
6. [Cara Rencana Pulang](#cara-rencana-pulang)
7. [Cara Proses Pulang Pasien](#cara-proses-pulang-pasien)
8. [Cara Melihat Okupansi Kamar](#cara-melihat-okupansi-kamar)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Rawat Inap

Modul Rawat Inap SIMRS RumahSakitKu mengelola seluruh siklus perawatan pasien rawat inap, mulai dari admisi, penempatan kamar, perawatan harian, hingga pemulangan pasien.

### Komponen Modul Rawat Inap:

| Komponen | Fungsi |
|----------|--------|
| **Admisi** | Pendaftaran pasien masuk |
| **Bed Management** | Pengelolaan kamar dan tempat tidur |
| **EMR Rawat Inap** | Rekam medis elektronik harian |
| **Billing** | Tagihan kamar dan layanan |
| **Pemulangan** | Proses pulang pasien |

### Status Pasien Rawat Inap:

| Status | Warna | Keterangan |
|--------|-------|------------|
| 🟡 Pendaftaran | Kuning | Sedang proses admisi |
| 🔵 Dirawat | Biru | Aktif dirawat |
| 🟠 Rencana Pulang | Orange | Menunggu checkout |
| 🟢 Sudah Pulang | Hijau | Checkout selesai |
| 🔴 Batal | Merah | Admisi dibatalkan |

---

## Cara Mengadmissi Pasien Rawat Inap

### Jalur Admisi:

Pasien rawat inap dapat masuk melalui:
1. **Rawat Jalan** → Rujukan dokter poliklinik
2. **IGD** → Setelah penanganan darurat
3. **Rujukan** → Dari RS/faskes lain
4. **Booking** → Rencana operasi/prosedur

### Langkah Admisi:

#### 1. Pendaftaran Admisi:

1. Login ke SIMRS dengan akun admisi
2. Klik menu **"Rawat Inap"** → **"Admisi"**
3. Klik **"+ Admisi Baru"**

#### 2. Cari/Input Data Pasien:

**Jika pasien sudah terdaftar:**
1. Cari dengan **No. RM** atau **NIK**
2. Pilih data pasien

**Jika pasien baru:**
1. Klik **"Pasien Baru"**
2. Isi data lengkap pasien
3. Simpan dan dapatkan No. RM

#### 3. Isi Form Admisi:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **Tanggal Masuk** | Hari ini (otomatis) | 08/02/2026 |
| **Jam Masuk** | Waktu saat ini | 14:30 |
| **Cara Masuk** | Jalur masuk | Dari Rawat Jalan |
| **Asal** | Unit pengirim | Poli Umum |
| **Dokter Pengirim** | Nama dokter | dr. Budi Santoso |
| **Diagnosa Masuk** | Diagnosis awal | Pneumonia |
| **Indikasi Rawat** | Alasan dirawat | Sesak, perlu oksigen |

#### 4. Data Penanggung Jawab:

| Field | Keterangan |
|-------|------------|
| Nama PJ | Nama penanggung jawab |
| Hubungan | Istri/Suami/Anak/Lainnya |
| No. Telepon | Nomor yang bisa dihubungi |
| Alamat | Alamat lengkap PJ |

#### 5. Data Penjamin:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| Cara Bayar | Umum/BPJS/Asuransi | BPJS |
| No. Kartu | Nomor penjamin | 0001234567890 |
| Kelas | Kelas perawatan | Kelas I |

#### 6. Simpan Admisi:

1. Review semua data
2. Klik **"Simpan Admisi"**
3. Sistem generate **No. Registrasi Rawat Inap**
4. Cetak **Bukti Admisi** (opsional)

### Screenshot:
![Form Admisi](../images/form-admisi.png)

### Formulir Admisi:

```
┌─────────────────────────────────────────┐
│          SURAT ADMISI RAWAT INAP        │
│            RS RUMAH SAKITKU             │
├─────────────────────────────────────────┤
│ No. Registrasi: RI-2026-02-0001         │
│ Tanggal: 08 Februari 2026               │
├─────────────────────────────────────────┤
│ DATA PASIEN:                            │
│ Nama: _________________________         │
│ No. RM: _______________________         │
│ Tgl Lahir: ____________________         │
├─────────────────────────────────────────┤
│ DATA RAWAT INAP:                        │
│ Kelas: ☐ VIP ☐ I ☐ II ☐ III             │
│ Kamar: __________ Bed: _______          │
│ Diagnosa Masuk: ________________        │
│ Dokter Penanggung Jawab: __________     │
├─────────────────────────────────────────┤
│ Tanda tangan:                           │
│ Pasien/Keluarga        Petugas Admisi   │
│ ________________       ______________   │
└─────────────────────────────────────────┘
```

---

## Cara Mengassign Kamar dan Bed

### Pengecekan Ketersediaan:

1. Klik menu **"Rawat Inap"** → **"Bed Management"**
2. Lihat **peta kamar** (floor plan/list)
3. Filter berdasarkan:
   - Kelas (VIP/Kelas I/II/III)
   - Ruangan
   - Status (Kosong/Terisi/Booked)

### Status Bed:

| Status | Warna | Icon | Keterangan |
|--------|-------|------|------------|
| Kosong | Hijau | 🟩 | Tersedia |
| Terisi | Merah | 🟥 | Ditempati pasien |
| Booked | Kuning | 🟨 | Dipesan |
| Perbaikan | Abu-abu | ⬜ | Tidak tersedia |
| Siap Hunian | Biru | 🟦 | Sudah cleaning |

### Langkah Assign Kamar:

#### 1. Dari Halaman Admisi:

1. Setelah form admisi terisi
2. Klik **"Pilih Kamar"**
3. Sistem menampilkan daftar kamar tersedia
4. Pilih **kelas** sesuai kebutuhan dan penjamin
5. Pilih **kamar** dan **nomor bed**
6. Klik **"Assign"**
7. Konfirmasi penempatan

#### 2. Assign Manual:

1. Buka **"Bed Management"**
2. Klik bed yang kosong
3. Pilih **"Assign Pasien"**
4. Cari pasien dengan No. Registrasi
5. Konfirmasi penempatan

### Pertimbangan Pemilihan Kamar:

| Faktor | Pertimbangan |
|--------|--------------|
| **Kelas** | Sesuai hak kelas BPJS/asuransi |
| **Jenis Kelamin** | Kamar perempuan/laki/campur |
| **Isolasi** | Khusus untuk penyakit menular |
| **Fasilitas** | AC, TV, kamar mandi dalam |
| **Dekat Nurse Station** | Untuk pasien kritis |

### Screenshot:
![Bed Management](../images/bed-management.png)

---

## Cara Pindah Kamar

### Alasan Pindah Kamar:

- Perubahan kelas (naik/turun)
- Kebutuhan isolasi
- Permintaan pasien
- Perbaikan fasilitas
- Penggabungan/separasi kamar

### Langkah Pindah Kamar:

#### 1. Proses Pindah:

1. Buka **"Bed Management"**
2. Klik bed pasien yang akan pindah
3. Pilih **"Pindah Kamar"**
4. Isi form pindah:

   | Field | Keterangan |
   |-------|------------|
   | Kamar Tujuan | Pilih kamar baru |
   | Bed Tujuan | Pilih nomor bed |
   | Tanggal Pindah | Hari ini |
   | Jam Pindah | Waktu pindah |
   | Alasan Pindah | Isi alasan |

5. Klik **"Proses Pindah"**

#### 2. Verifikasi:

1. Sistem update lokasi pasien
2. Generate **Surat Pindah Kamar**
3. Update billing (jika beda kelas)
4. Notifikasi ke unit tujuan

### Surat Pindah Kamar:

```
┌─────────────────────────────────────────┐
│          SURAT PINDAH KAMAR             │
├─────────────────────────────────────────┤
│ No. RM: 000123                          │
│ Nama: AHMAD SUSANTO                     │
├─────────────────────────────────────────┤
│ PINDAH DARI:                            │
│ Kamar: Melati 1    Bed: 1               │
│ Kelas: II                               │
├─────────────────────────────────────────┤
│ KE:                                     │
│ Kamar: Mawar 3     Bed: 2               │
│ Kelas: I                                │
├─────────────────────────────────────────┤
│ Alasan: Upgrade kelas atas permintaan   │
│                                         │
│ Tgl/Jam: 08/02/2026 16:00               │
├─────────────────────────────────────────┤
│ Tanda tangan:                           │
│ Perawat Ruangan Asal   Ruangan Tujuan   │
│ ________________       ______________   │
└─────────────────────────────────────────┘
```

### Screenshot:
![Form Pindah Kamar](../images/pindah-kamar.png)

### Dampak Billing:

| Skenario | Dampak |
|----------|--------|
| Naik kelas | Pasien bayar selisih |
| Turun kelas | Refund kelebihan (jika ada) |
| Sesama kelas | Tidak ada perubahan |

---

## Cara Mengisi EMR Rawat Inap

### Dokumentasi Rawat Inap:

#### 1. Asesmen Awal Keperawatan:

Diisi dalam 24 jam pertama:

| Aspek | Parameter |
|-------|-----------|
| **Status Fisik** | TD, Nadi, Suhu, RR, SpO2 |
| **Status Mental** | Kesadaran, orientasi, mood |
| **Status Fungsional** | Mobilitas, ADL |
| **Risiko Jatuh** | Skala Morse/Fall Risk |
| **Status Nutrisi** | Skrining gizi |
| **Riwayat Alergi** | Obat, makanan, lingkungan |

#### 2. CPPT (Catatan Perkembangan Pasien Terintegrasi):

Diisi setiap shift (pagi, sore, malam):

```
Tanggal: 08/02/2026        Shift: PAGI (07:00-14:00)

S (Subjective):
- Pasien mengeluh sesak berkurang
- Tidur cukup nyenyak malam

O (Objective):
- TD: 130/80 mmHg
- Nadi: 80 x/menit
- RR: 20 x/menit
- SpO2: 96% dengan nasal canul 2L
- Pasien tampak lebih nyaman

A (Assessment):
- Sesak berangsur membaik
- Pneumonia Community Acquired

P (Planning):
- Lanjutkan antibiotic
- Monitor saturasi 4 jam
- Mobilisation ringan

I (Implementasi):
- Obat diberikan sesuai jadwal
- Edukasi batuk efektif
- Mobilisation ke kursi 30 menit

E (Evaluasi):
- Pasien toleransi baik
- Saturasi stabil
```

#### 3. Input TTV:

1. Buka EMR pasien
2. Klik **"Input TTV"**
3. Isi:
   - Tanggal dan jam
   - TD, Nadi, Suhu, RR, SpO2
   - Kesadaran (GCS)
   - Output (urine, drain, muntah)
   - Input (oral, infus)
4. Sistem otomatis hitung **Balance Cairan**
5. Klik **"Simpan"**

### Screenshot:
![EMR Rawat Inap](../images/emr-ri.png)

### Grafik TTV:

Sistem menampilkan grafik trend:
- Grafik Suhu
- Grafik TD (sistolik/diastolik)
- Grafik Nadi
- Grafik Balance Cairan

### Edukasi Pasien:

Catat edukasi yang diberikan:
- Diagnosis dan prognosis
- Pengobatan dan efek samping
- Diet dan nutrisi
- Latihan/perawatan luka
- Tanda bahaya (emergency warning signs)

---

## Cara Rencana Pulang

### Indikasi Rencana Pulang:

- Kondisi pasien stabil/membaik
- Tujuan perawatan tercapai
- Atas permintaan pasien/keluarga (DAMA)
- Rujuk ke RS lain
- Meninggal dunia

### Langkah Rencana Pulang:

1. **Dokter DPJP** memutuskan pulang:
   - Isi **"Rencana Pulang"** di EMR
   - Tentukan **tanggal dan jam** pulang
   - Tulis **instruksi pulang**

2. **Perawat** menerima instruksi:
   - Buka **"Rencana Pulang"**
   - Review instruksi dokter
   - Siapkan dokumen:
     - Resume medis
     - Copy resep pulang
     - Surat kontrol/rujukan (jika perlu)

3. **Informasikan ke pasien/keluarga**:
   - Jadwal pulang
   - Obat-obatan yang dibawa pulang
   - Jadwal kontrol
   - Tanda-tanda harus kembali ke RS

4. **Update status** di sistem:
   - Klik **"Rencana Pulang"**
   - Input estimasi waktu
   - Sistem notifikasi ke billing

### Screenshot:
![Rencana Pulang](../images/rencana-pulang.png)

### Instruksi Pulang:

```
┌─────────────────────────────────────────┐
│         INSTRUKSI PULANG                │
│         RS RUMAH SAKITKU                │
├─────────────────────────────────────────┤
│ Nama: AHMAD SUSANTO                     │
│ No. RM: 000123                          │
│ Tgl Masuk: 01/02/2026                   │
│ Tgl Pulang: 08/02/2026                  │
├─────────────────────────────────────────┤
│ DIAGNOSA:                               │
│ Pneumonia Community Acquired            │
├─────────────────────────────────────────┤
│ OBAT PULANG:                            │
│ 1. Amoxicillin 500mg, 3x1, 7 hari       │
│ 2. Paracetamol 500mg, jika demam        │
│ 3. Vitamin C 500mg, 1x1                 │
├─────────────────────────────────────────┤
│ INSTRUKSI:                              │
│ - Minum obat teratur sampai habis       │
│ - Istirahat cukup, hindari kelelahan    │
│ - Diet tinggi protein dan vitamin       │
│ - Kontrol ke Poli Paru 7 hari lagi      │
├─────────────────────────────────────────┤
│ TANDA BAHAYA - SEGERA KE IGD:           │
│ - Sesak napas berat                     │
│ - Demam tinggi tidak turun              │
│ - Nyeri dada                            │
│ - Berkeringat dingin                    │
├─────────────────────────────────────────┤
│ DPJP: dr. Budi Santoso, Sp.P            │
│ Tanda tangan: ______________            │
└─────────────────────────────────────────┘
```

---

## Cara Proses Pulang Pasien

### Tahapan Checkout:

#### 1. Medical Check Out (MCO):

1. Perawat pastikan:
   - Obat sudah diberikan
   - Dokumen lengkap
   - Pasien/keluarga mengerti instruksi

2. Dokter tanda tangan:
   - Resume medis
   - Surat kontrol/rujukan

3. Update status:
   - Klik **"Medical Check Out"**
   - Input waktu keluar
   - Sistem update bed menjadi "Siap Cleaning"

#### 2. Billing Clearance:

1. Billing hitung total tagihan
2. Pasien/bpjs bayar (jika ada)
3. Cetak invoice final
4. Tanda tangan kwitansi

#### 3. Final Checkout:

1. Petugas admisi:
   - Verifikasi semua dokumen
   - Serahkan copy dokumen ke pasien
   - Ambil tanda tangan pasien

2. Update sistem:
   - Klik **"Final Checkout"**
   - Status berubah: "Sudah Pulang"
   - Bed status: "Kosong" setelah cleaning

### Screenshot:
![Checkout Pasien](../images/checkout.png)

### Dokumen yang Diserahkan ke Pasien:

| Dokumen | Keterangan |
|---------|------------|
| Resume Medis | Ringkasan perawatan |
| Copy Resep Pulang | Obat-obatan |
| Surat Kontrol | Jadwal kontrol poliklinik |
| Kwitansi Pembayaran | Bukti pembayaran |
| Surat Rujukan | Jika dirujuk ke RS lain |

---

## Cara Melihat Okupansi Kamar

### Dashboard Okupansi:

1. Klik menu **"Rawat Inap"** → **"Okupansi"**
2. Lihat ringkasan:

```
┌─────────────────────────────────────────┐
│      OKUPANSI KAMAR HARI INI            │
│         08 Februari 2026                │
├─────────────────────────────────────────┤
│ KELAS    │ TOTAL │ TERISI │ KOSONG │ %  │
├──────────┼───────┼────────┼────────┼────┤
│ VIP      │  10   │   8    │   2    │ 80%│
│ Kelas I  │  20   │  18    │   2    │ 90%│
│ Kelas II │  30   │  25    │   5    │ 83%│
│ Kelas III│  40   │  30    │  10    │ 75%│
├──────────┼───────┼────────┼────────┼────┤
│ TOTAL    │ 100   │  81    │  19    │ 81%│
└─────────────────────────────────────────┘
```

### Grafik Okupansi:

- Grafik harian (24 jam)
- Grafik mingguan
- Grafik bulanan
- Trend okupansi per kelas

### Prediksi Okupansi:

Sistem menampilkan prediksi:
- Perkiraan pasien masuk hari ini
- Pasien rencana pulang
- Booking yang akan masuk

### Screenshot:
![Dashboard Okupansi](../images/okupansi.png)

### Laporan Okupansi:

| Jenis Laporan | Isi |
|---------------|-----|
| Bor (Bed Occupancy Rate) | % okupansi per periode |
| LOS (Length of Stay) | Rata-rata lama dirawat |
| BTO (Bed Turn Over) | Frekuensi pemakaian bed |
| TOI (Turn Over Interval) | Waktu bed kosong |

---

## Tips dan Troubleshooting

### Tips Manajemen Rawat Inap:

1. **Shift Handover**:
   - Dokumentasi lengkap tiap shift
   - Serah terima verbal untuk pasien kritis
   - Tandai pasien perhatian khusus

2. **Monitoring Bed**:
   - Cek status bed setiap pagi
   - Prioritaskan cleaning room
   - Update status secara real-time

3. **Koordinasi Tim**:
   - Rapat pagi dengan dokter
   - Koordinasi dengan farmasi (obat)
   - Koordinasi dengan billing (tagihan)

### Troubleshooting Umum:

#### 1. Bed Tidak Tersedia untuk Pasien Urgent

**Gejala**: Pasien perlu segera dirawat tapi semua bed penuh

**Solusi**:
1. Cek status "Siap Cleaning"
2. Prioritaskan cleaning cepat
3. Pertimbangkan upgrade/downgrade kelas
4. Koordinasi dengan RS rujukan jika memang tidak ada bed

#### 2. Pasien Tidak Bisa Checkout

**Gejala**: Tombol checkout tidak aktif

**Solusi**:
1. Cek apakah ada tindakan yang belum diinput
2. Cek apakah billing sudah final
3. Cek apakah sudah MCO
4. Hubungi supervisor IT jika sistem error

#### 3. Data TTV Tidak Muncul di Grafik

**Gejala**: Grafik tidak update dengan input TTV

**Solusi**:
1. Refresh halaman EMR
2. Cek apakah format waktu benar
3. Verifikasi input sudah tersimpan
4. Coba input ulang

#### 4. Salah Assign Kamar

**Gejala**: Pasien salah masuk kamar

**Solusi**:
1. Jangan hapus admisi
2. Gunakan fitur "Pindah Kamar"
3. Pilih kamar yang benar
4. Dokumentasikan perpindahan

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Kamar/bed | Housekeeping Ext. 7777 |
| Billing rawat inap | Keuangan RI Ext. 6666 |
| Emergency | Supervisor Ruangan Ext. 5555 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
