# Panduan Modul IGD (Instalasi Gawat Darurat)

Panduan lengkap untuk menggunakan modul IGD SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul IGD](#pengenalan-modul-igd)
2. [Cara Mendaftarkan Pasien IGD](#cara-mendaftarkan-pasien-igd)
3. [Cara Melakukan Triase](#cara-melakukan-triase)
4. [Panduan Kategori Triase](#panduan-kategori-triase)
5. [Cara Mengisi TTV Cepat](#cara-mengisi-ttv-cepat)
6. [Cara Mengassign Dokter Jaga](#cara-mengassign-dokter-jaga)
7. [Cara Transfer ke Rawat Inap](#cara-transfer-ke-rawat-inap)
8. [Cara Discharge dari IGD](#cara-discharge-dari-igd)
9. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul IGD

IGD (Instalasi Gawat Darurat) adalah unit yang menangani pasien dalam kondisi darurat memerlukan penanganan segera. Modul IGD SIMRS RumahSakitKu dirancang untuk:

- Pendaftaran cepat pasien gawat darurat
- Sistem triase untuk prioritas penanganan
- Monitoring pasien di IGD
- Manajemen transfer ke rawat inap atau discharge

### Alur Pasien IGD:

```
Pasien Datang → Triase → Assessment → Penanganan → Disposition
     ↓                              ↓
   Resusitasi                  Stabil → Pulang
     ↓                              ↓
  Emergency                    Transfer ke RI
```

### Status Pasien IGD:

| Status | Warna | Keterangan |
|--------|-------|------------|
| 🔴 Triase Merah | Merah | Resusitasi - Segera |
| 🟡 Triase Kuning | Kuning | Emergency - < 15 menit |
| 🟢 Triase Hijau | Hijau | Urgent - < 60 menit |
| ⚫ Triase Hitam | Hitam | Expectant/DOA |
| 🔵 Dirawat | Biru | Sedang dalam perawatan IGD |
| 🟠 Rencana Pulang | Orange | Menunggu discharge |

---

## Cara Mendaftarkan Pasien IGD

### Kecepatan adalah Prioritas:

Di IGD, pendaftaran harus cepat dan efisien. Data minimal yang diperlukan:
- Nama (bisa nama sementara untuk unknown)
- Perkiraan umur
- Jenis kelamin
- Keluhan utama

### Langkah Pendaftaran IGD:

#### 1. Pendaftaran Cepat:

1. Login ke SIMRS
2. Klik menu **"IGD"** → **"Admisi IGD"**
3. Klik **"+ Pasien IGD"**

#### 2. Isi Data Minimal:

| Field | Keterangan | Contoh |
|-------|------------|--------|
| **Waktu Datang** | Jam masuk IGD | 14:35 |
| **Cara Datang** | Sendiri/dibawa | Dibawa ambulance |
| **Nama** | Nama pasien (bila diketahui) | AHMAD |
| **Umur/DOB** | Perkiraan umur | 45 tahun |
| **Jenis Kelamin** | L/P | Laki-laki |
| **Keluhan Utama** | Keluhan singkat | Sesak napas |
| **Penjamin** | Umum/BPJS/Asuransi | BPJS |

#### 3. Unknown Patient (Tidak Dikenal):

Jika pasien tidak membawa identitas:
1. Beri **nama sementara**: UNKNOWN (laki-laki/perempuan)
2. Estimasi **umur** (dewasa/anak/lansia)
3. Input **cara datang** dan **penemu**
4. Catat **ciri-ciri fisik**:
   - Tinggi badan
   - Berat badan
   - Tanda lahir/ciri khas
   - Pakaian yang dikenakan

#### 4. Simpan dan Dapatkan:

- **No. Registrasi IGD** (format: IGD-YYYYMMDD-XXXX)
- **Nomor Antrian Triase**
- **Gelang Pasien** (dicetak)

### Screenshot:
![Form Admisi IGD](../images/igd-admisi.png)

### Formulir Identifikasi:

```
┌─────────────────────────────────────────┐
│      GELANG IDENTITAS PASIEN IGD        │
├─────────────────────────────────────────┤
│ No. Reg: IGD-20260208-001               │
│ Nama: AHMAD SUSANTO                     │
│ Tgl Lahir: 15/08/1985                   │
│ No. RM: 000123                          │
├─────────────────────────────────────────┤
│ ALERGI: ☐ Tidak Ada ☐ Ada: _______      │
├─────────────────────────────────────────┤
│ [BARCODE]                               │
└─────────────────────────────────────────┘
```

---

## Cara Melakukan Triase

### Pengertian Triase:

Triase adalah proses penentuan prioritas penanganan pasien berdasarkan:
- Tingkat kegawatdaruratan
- Kebutuhan sumber daya
- Prognosis

### Langkah Triase:

#### 1. Assessment Triase:

1. Pilih pasien dari antrian
2. Klik **"Proses Triase"**
3. Isi form triase:

| Parameter | Input |
|-----------|-------|
| **Keluhan Utama** | Sesak napas sejak 2 jam |
| **Riwayat Singkat** | DM hipertensi, tidak rutin minum obat |
| **Alergi** | Tidak ada / Ada: ____ |
| **Nyeri** | Skala 0-10: ____ |

#### 2. Pemeriksaan TTV Cepat:

| Parameter | Nilai |
|-----------|-------|
| Kesadaran | A (Alert) / V / P / U |
| Tekanan Darah | ___/___ mmHg |
| Nadi | ___ x/menit |
| Pernapasan | ___ x/menit |
| Suhu | ___ °C |
| SpO2 | ___ % |

#### 3. Penentuan Kategori:

Berdasarkan assessment, pilih kategori triase:
- 🔴 **Merah** - Resusitasi
- 🟡 **Kuning** - Emergency
- 🟢 **Hijau** - Urgent
- 🔵 **Biru** - Less urgent
- ⚫ **Hitam** - Expectant/DOA

#### 4. Simpan Triase:

1. Klik **"Simpan Triase"**
2. Sistem akan:
   - Assign nomor antrian sesuai prioritas
   - Notifikasi ke dokter/perawat
   - Masukkan ke monitor antrian

### Screenshot:
![Form Triase](../images/igd-triase.png)

### Lembar Triase:

```
┌─────────────────────────────────────────┐
│            LEMBAR TRIASE                │
│              IGD RS RUMAH SAKITKU       │
├─────────────────────────────────────────┤
│ No. RM: _______  Tgl/Jam: ___________   │
│ Nama: ______________________________    │
├─────────────────────────────────────────┤
│ VITAL SIGN:                             │
│ TD: _______ Nadi: _______ Suhu: _______ │
│ RR: _______ SpO2: _______ GCS: _______  │
├─────────────────────────────────────────┤
│ KATEGORI TRIASE:                        │
│ ☐ Merah (Resusitasi)    ☐ Hijau (Urgent)│
│ ☐ Kuning (Emergency)    ☐ Biru (Non-urg)│
│ ☐ Hitam (Expectant)                     │
├─────────────────────────────────────────┤
│ KELUHAN UTAMA: _______________________  │
│                                         │
├─────────────────────────────────────────┤
│ TRIASE OLEH: _____________ JAM: ______  │
└─────────────────────────────────────────┘
```

---

## Panduan Kategori Triase

### 1. Triase MERAH (Resusitasi) - Prioritas 1

**Kriteria:**
- Gangguan jalan napas yang mengancam jiwa
- Henti napas/henti jantung
- Shock berat
- Pendarahan masif
- Trauma kepala berat dengan GCS < 9

**Respon Waktu:** Segera (0 menit)

**Tindakan:**
- Langsung ke resusitasi room
- Tim medis merespon dalam 1 menit
- Semua sumber daya tersedia

**Contoh Kasus:**
- Cardiac arrest
- Trauma berat
- Anafilaksis
- Asma berat status asthmaticus
- Pendarahan aktif

---

### 2. Triase KUNING (Emergency) - Prioritas 2

**Kriteria:**
- Gangguan jalan napas potensial
- Nyeri dada dengan risiko iskemi
- Gangguan kesadaran (GCS 9-12)
- Trauma tanpa hemodinamik stabil
- Dehidrasi berat
- Luka bakar 10-20% BSA

**Respon Waktu:** < 15 menit

**Tindakan:**
- Ruang perawatan monitoring
- Dokter segera evaluasi
- Monitoring intensif

**Contoh Kasus:**
- Chest pain syndrome
- Stroke akut
- Pneumonia berat
- Gastroenteritis dehidrasi
- Luka bakar sedang

---

### 3. Triase HIJAU (Urgent) - Prioritas 3

**Kriteria:**
- Kondisi stabil tapi memerlukan intervensi
- Nyeri moderat (skala 4-7)
- Minor trauma
- Demam tanpa toksisitas
- Muntah/diare ringan

**Respon Waktu:** < 60 menit

**Tindakan:**
- Ruang perawatan umum
- Monitoring berkala
- Tindakan sesuai protokol

**Contoh Kasus:**
- Fraktur ekstremitas
- Abses
- ISPA ringan
- Diare tanpa dehidrasi
- Alergi ringan

---

### 4. Triase BIRU (Less Urgent) - Prioritas 4

**Kriteria:**
- Kondisi stabil
- Nyeri ringan (skala 1-3)
- Kasus non-urgent
- Kontrol luka/post op

**Respon Waktu:** < 120 menit

**Tindakan:**
- Ruang tunggu
- Pelayanan sesuai urutan
- Edukasi self-care jika memungkinkan

**Contoh Kasus:**
- Luka lecet minor
- Batuk pilek
- Kontrol jahitan
- Bisa ringan

---

### 5. Triase HITAM (Expectant/DOA) - Prioritas 5

**Kriteria:**
- Kematian telah terjadi (DOA)
- Cedekan tidak mungkin bertahan
- Sumber daya terbatas, fokus ke pasien selamat

**Respon Waktu:** Sesuai kondisi

**Tindakan:**
- Verifikasi kehidupan
- Dokumentasi
- Protokol kematian

**Contoh Kasus:**
- Dead on arrival (DOA)
- Decapitation
- Rigor mortis
- Livor mortis

---

### Tabel Ringkasan:

| Kategori | Warna | Waktu Respon | Ruang | Contoh |
|----------|-------|--------------|-------|--------|
| Resusitasi | 🔴 Merah | Segera | Resus Room | Cardiac arrest |
| Emergency | 🟡 Kuning | < 15 menit | High Care | Chest pain |
| Urgent | 🟢 Hijau | < 60 menit | General | Fraktur |
| Less Urgent | 🔵 Biru | < 120 menit | General | Luka laceration |
| Expectant | ⚫ Hitam | Flexible | - | DOA |

---

## Cara Mengisi TTV Cepat

### Form TTV IGD:

| Parameter | Normal | Input |
|-----------|--------|-------|
| **Kesadaran** | CM | A/V/P/U |
| **GCS** | 15 | E___V___M___ = ___ |
| **TD** | 120/80 | ___/___ mmHg |
| **Nadi** | 60-100 | ___ x/menit |
| **RR** | 16-20 | ___ x/menit |
| **Suhu** | 36.5-37.5 | ___ °C |
| **SpO2** | >95% | ___ % |

### Tanda Vital Pediatric (Anak):

| Umur | Nadi | RR | TD Sistol |
|------|------|-----|-----------|
| Neonatus | 100-160 | 30-60 | 60-90 |
| 1-12 bulan | 80-140 | 25-40 | 70-100 |
| 1-2 tahun | 80-130 | 20-30 | 80-100 |
| 2-5 tahun | 80-120 | 20-25 | 80-110 |
| 5-12 tahun | 70-110 | 15-25 | 90-110 |
| >12 tahun | 60-100 | 12-20 | 100-120 |

### Pediatric Early Warning Score (PEWS):

Skoring untuk deteksi dini deteriorasi anak:

| Parameter | 0 | 1 | 2 | 3 |
|-----------|---|---|---|---|
| Behavior | Normal | Irritable | Lethargic | Unresponsive |
| Cardiovasc | Pink | Pale | Grey/CRT>4s | Grey/CRT>5s |
| Respiratory | Normal | Increased | Decreased | Gasping |

---

## Cara Mengassign Dokter Jaga

### Penjadwalan Dokter Jaga IGD:

1. Klik menu **"IGD"** → **"Jadwal Dokter"**
2. Lihat jadwal dokter jaga saat ini
3. Untuk assign pasien ke dokter:
   - Buka data pasien IGD
   - Klik **"Assign Dokter"**
   - Pilih dokter dari daftar
   - Simpan

### Status Dokter Jaga:

| Status | Warna | Keterangan |
|--------|-------|------------|
| Available | 🟢 | Siap menerima pasien |
| On Duty | 🔵 | Sedang menangani pasien |
| Break | 🟡 | Istirahat |
| Off | 🔴 | Tidak jaga |

### Notifikasi ke Dokter:

- Pop-up di layar dokter
- SMS/push notification (jika diaktifkan)
- Suara alarm untuk triase merah/kuning

### Screenshot:
![Jadwal Dokter](../images/jadwal-dokter.png)

---

## Cara Transfer ke Rawat Inap

### Indikasi Transfer ke RI:

- Pasien memerlukan observasi > 24 jam
- Kondisi stabil tapi perlu perawatan intensif
- Memerlukan tindakan operatif
- Perlu rehabilitasi medis

### Langkah Transfer:

#### 1. Order Masuk Rawat Inap:

1. Buka EMR pasien IGD
2. Klik **"Order Rawat Inap"**
3. Isi data:
   - Indikasi rawat inap
   - Kelas yang diminta
   - DPJP yang diinginkan
   - Unit tujuan

#### 2. Cari Kamar:

1. Sistem cek ketersediaan kamar
2. Tampilkan pilihan kamar kosong
3. Pilih kamar dan bed
4. Tentukan estimasi waktu pindah

#### 3. Proses Transfer:

1. Notifikasi ke admisi rawat inap
2. Persiapkan:
   - Resume IGD
   - Copy hasil lab/radiologi
   - Obat-obatan (jika ada)
   - TTV terakhir

3. Serah terima:
   - Perawat IGD → Perawat RI
   - Dokter IGD → DPJP
   - Tanda tangan berita acara

4. Update sistem:
   - Status IGD: "Transfer RI"
   - Generate No. Registrasi RI
   - Buka EMR Rawat Inap

### Formulir Transfer:

```
┌─────────────────────────────────────────┐
│      SURAT RUJUKAN INTERNAL IGD → RI    │
├─────────────────────────────────────────┤
│ No. RM: _______  Nama: _____________    │
│ IGD Masuk: _______  Transfer: _______   │
├─────────────────────────────────────────┤
│ DIAGNOSA: ___________________________   │
│ TERAPI DI IGD: ______________________   │
│ TINDAKAN: ___________________________   │
├─────────────────────────────────────────┤
│ ALASAN RAWAT INAP: __________________   │
│ KAMAR TUJUAN: _______ BED: _______      │
├─────────────────────────────────────────┤
│ Dokter IGD: _________ Ttd: _______      │
│ DPJP: _________ Ttd: _______            │
└─────────────────────────────────────────┘
```

### Screenshot:
![Order Rawat Inap](../images/order-ri.png)

---

## Cara Discharge dari IGD

### Jenis Discharge:

| Jenis | Keterangan |
|-------|------------|
| **Pulang Sembuh** | Kondisi membaik, boleh pulang |
| **Pulang DAMA** | Atas permintaan sendiri |
| **Rujuk RI** | Transfer ke rawat inap |
| **Rujuk RS Lain** | Dirujuk ke RS lebih tinggi |
| **Meninggal** | Pasien meninggal |

### Langkah Discharge:

#### 1. Pulang Sembuh/DAMA:

1. Dokter isi **"Order Pulang"** di EMR
2. Perawat:
   - Siapkan surat kontrol (jika perlu)
   - Copy resep
   - Edukasi pulang
3. Billing:
   - Hitung tagihan
   - Proses pembayaran
4. Petugas:
   - Tanda tangan discharge
   - Update status: "Sudah Pulang"

#### 2. Rujuk ke RS Lain:

1. Isi **Surat Rujukan Eksternal**
2. Dokumen yang dilampirkan:
   - Resume IGD
   - Hasil lab/radiologi (copy)
   - Copy resep
   - Surat rujukan
3. Koordinasi dengan ambulance (jika perlu)
4. Update status: "Rujuk Keluar"

#### 3. Meninggal:

1. Dokter:
   - Konfirmasi kematian
   - Isi **Surat Kematian**
   - Beritahu keluarga
2. Petugas:
   - Protokol kematian
   - Koordinasi dengan kamar jenazah
   - Dokumentasi
3. Billing:
   - Final tagihan
4. Update status: "Meninggal"

### Screenshot:
![Discharge IGD](../images/igd-discharge.png)

### Ringkasan Pulang:

```
┌─────────────────────────────────────────┐
│       RINGKASAN PULANG DARI IGD         │
├─────────────────────────────────────────┤
│ Nama: _____________________________     │
│ No. RM: _______  Tgl: ______________    │
├─────────────────────────────────────────┤
│ DIAGNOSA AKHIR: ___________________     │
│                                         │
├─────────────────────────────────────────┤
│ TINDAKAN: _________________________     │
│                                         │
├─────────────────────────────────────────┤
│ KEADAAN PULANG:                         │
│ ☐ Sembuh  ☐ Membaik  ☐ Belum Sembuh    │
│ ☐ Meninggal  ☐ Rujuk                    │
├─────────────────────────────────────────┤
│ KONTROL: __________________________     │
│                                         │
├─────────────────────────────────────────┤
│ TANDA BAHAYA - SEGERA KE RS:            │
│ ___________________________________     │
├─────────────────────────────────────────┤
│ Dokter: _______________ Ttd: ______     │
└─────────────────────────────────────────┘
```

---

## Tips dan Troubleshooting

### Tips Kerja di IGD:

1. **Selalu Siap**:
   - TTV set lengkap tersedia
   - Ambu bag siap
   - Oksigen tersedia
   - Defibrillator siap

2. **Dokumentasi Cepat**:
   - Gunakan template
   - Singkat tapi lengkap
   - Prioritaskan tindakan

3. **Komunikasi**:
   - Informasikan ke keluarga
   - Koordinasi dengan tim
   - Update status berkala

### Checklist IGD:

- [ ] Peralatan resusitasi lengkap
- [ ] Obat emergency tersedia
- [ ] Ambulance siap
- [ ] Dokter jaga standby
- [ ] Perawat jaga cukup
- [ ] Sistem SIMRS online

### Troubleshooting Umum:

#### 1. Sistem Lambat saat Emergency

**Gejala**: Pasien kritis tapi sistem loading lama

**Solusi**:
1. Tindakan medis dulu, dokumentasi menyusul
2. Gunakan lembar manual sementara
3. Input ke sistem setelah stabil
4. Jangan tunda resusitasi karena sistem

#### 2. Triase Salah Kategori

**Gejala**: Pasien hijau ternyata memburuk

**Solusi**:
1. Re-triase segera
2. Naikkan kategori jika perlu
3. Re-allocate sumber daya
4. Dokumentasikan alasan re-triase

#### 3. Tidak Ada Bed RI untuk Rujukan

**Gejala**: Pasien perlu RI tapi penuh

**Solusi**:
1. Koordinasi dengan bed management
2. Pertimbangkan ICU/HCU jika perlu
3. Diskusi dengan supervisor
4. Jika benar-benar penuh, koordinasi RS rujukan

#### 4. Pasien Unknown Tidak Teridentifikasi

**Gejala**: Tidak ada yang kenal pasien

**Solusi**:
1. Hubungi polisi untuk identifikasi
2. Cek barang bawaan
3. Foto dan dokumentasikan ciri-ciri
4. Simpan dengan nama UNKNOWN sementara

### Kontak Penting:

| Instansi | Kontak |
|----------|--------|
| IT Support | Ext. 8888 |
| Bed Management | Ext. 7777 |
| Ambulance | Ext. 6666 |
| Polsek terdekat | 110 |
| Rumah Sakit Rujukan | Sesuai daerah |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
