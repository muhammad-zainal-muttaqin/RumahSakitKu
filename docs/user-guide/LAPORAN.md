# Panduan Modul Laporan

Panduan lengkap untuk menggunakan modul Laporan dan Dashboard SIMRS RumahSakitKu.

---

## Daftar Isi

1. [Pengenalan Modul Laporan](#pengenalan-modul-laporan)
2. [Cara Melihat Dashboard](#cara-melihat-dashboard)
3. [Cara Memfilter Laporan per Periode](#cara-memfilter-laporan-per-periode)
4. [Cara Membaca RL 1 - Data Dasar](#cara-membaca-rl-1---data-dasar)
5. [Cara Membaca RL 3 - Indikator Pelayanan](#cara-membaca-rl-3---indikator-pelayanan)
6. [Cara Export Laporan ke Excel/PDF](#cara-export-laporan-ke-excelpdf)
7. [Cara Mencetak Laporan](#cara-mencetak-laporan)
8. [Tips dan Troubleshooting](#tips-dan-troubleshooting)

---

## Pengenalan Modul Laporan

Modul Laporan SIMRS RumahSakitKu menyediakan berbagai jenis laporan untuk kebutuhan manajemen rumah sakit, pelaporan ke Dinas Kesehatan, BPJS, dan evaluasi kinerja.

### Jenis-jenis Laporan:

| Kategori | Laporan | Kegunaan |
|----------|---------|----------|
| **RL (Rumah Sakit)** | RL 1, RL 2, RL 3, dll | Pelaporan ke Dinkes |
| **Operasional** | Kunjungan, Rawat Inap | Monitoring harian |
| **Keuangan** | Pendapatan, Piutang | Laporan keuangan |
| **Farmasi** | Penggunaan obat, Stok | Manajemen farmasi |
| **Rekam Medis** | Morbiditas, Mortalitas | Analisis klinis |

### Akses Modul Laporan:

1. Login ke SIMRS
2. Klik menu **"Laporan"** di sidebar
3. Pilih kategori laporan
4. Pilih jenis laporan spesifik

---

## Cara Melihat Dashboard

### Dashboard Utama:

Dashboard menyediakan ringkasan visual kondisi rumah sakit secara real-time.

#### Komponen Dashboard:

```
┌─────────────────────────────────────────────────────────────┐
│                    DASHBOARD HARI INI                       │
│                   08 Februari 2026                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   RAWAT     │  │   RAWAT     │  │     IGD     │         │
│  │   JALAN     │  │   INAP      │  │             │         │
│  │             │  │             │  │             │         │
│  │   1,250     │  │    245      │  │     89      │         │
│  │  pasien     │  │  pasien     │  │  pasien     │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  GRAFIK KUNJUNGAN BULANAN                                   │
│                                                             │
│    ▲                                                        │
│ 150┤                                  ┌───┐                 │
│    │                          ┌───┐   │   │  ┌───┐          │
│ 100┤                  ┌───┐   │   │   │   │  │   │          │
│    │          ┌───┐   │   │   │   │   │   │  │   │          │
│  50┤  ┌───┐   │   │   │   │   │   │   │   │  │   │          │
│    │  │   │   │   │   │   │   │   │   │   │  │   │          │
│   0└──┴───┴───┴───┴───┴───┴───┴───┴───┴───┴──┴───┴──▶       │
│      Jan Feb Mar Apr Mei Jun Jul Agt Sep Okt Nov Des        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  OKUPANSI KAMAR                                             │
│                                                             │
│  VIP    ████████████████████░░░░  85%                       │
│  Kelas I████████████████████░░░░  90%                       │
│  Kelas II█████████████████░░░░░░  75%                       │
│  Kelas III██████████████░░░░░░░░  65%                       │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  ANTRIAN POLIKLINIK                                         │
│                                                             │
│  Poli Umum      ████████████████  45 antrian                │
│  Poli Gigi      ██████████        28 antrian                │
│  Poli Anak      ██████████████    38 antrian                │
│  Poli Jantung   ██████            15 antrian                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Navigasi Dashboard:

| Widget | Keterangan |
|--------|------------|
| **Kunjungan Hari Ini** | Total pasien per unit pelayanan |
| **Okupansi Kamar** | Persentase penggunaan bed |
| **Antrian Poli** | Jumlah pasien menunggu |
| **Grafik Trend** | Perbandingan per periode |
| **Top 10 Penyakit** | Diagnosis terbanyak |
| **Stok Obat Menipis** | Alert stok farmasi |

### Filter Dashboard:

| Filter | Opsi |
|--------|------|
| **Periode** | Hari ini, Minggu ini, Bulan ini, Tahun ini |
| **Unit** | Semua, RJ, RI, IGD |
| **Jenis Pasien** | Umum, BPJS, Asuransi |

### Screenshot:
![Dashboard](../images/dashboard.png)

---

## Cara Memfilter Laporan per Periode

### Filter Umum:

Hampir semua laporan memiliki filter periode:

#### 1. Filter Tanggal:

| Jenis | Kegunaan |
|-------|----------|
| **Hari Ini** | Laporan harian |
| **Kemarin** | Perbandingan |
| **7 Hari Terakhir** | Mingguan |
| **30 Hari Terakhir** | Bulanan |
| **Bulan Ini** | Laporan bulan berjalan |
| **Bulan Lalu** | Perbandingan bulan |
| **Tahun Ini** | Laporan tahunan |
| **Custom Range** | Pilih tanggal spesifik |

#### 2. Cara Menggunakan Filter:

1. Buka laporan yang diinginkan
2. Lihat bagian **"Filter"** di atas laporan
3. Pilih **jenis filter**:
   ```
   Tanggal: [01/02/2026] s/d [08/02/2026]
   
   Atau pilih cepat:
   ☐ Hari Ini
   ☐ Minggu Ini
   ☑ Custom Range
   ```
4. Klik **"Terapkan Filter"**
5. Laporan akan update sesuai periode

### Screenshot:
![Filter Laporan](../images/filter-laporan.png)

### Filter Lanjutan:

| Filter | Opsi |
|--------|------|
| **Unit Layanan** | Poli spesifik, Semua poli |
| **Dokter** | Per dokter, Semua dokter |
| **Cara Bayar** | Umum, BPJS, Asuransi, Semua |
| **Jenis Kelamin** | Laki-laki, Perempuan, Semua |
| **Usia** | Range umur |
| **Diagnosis** | Filter ICD10 |

---

## Cara Membaca RL 1 - Data Dasar

### Pengertian RL:

RL (Rumah Sakit) adalah format laporan wajib yang harus dikirimkan rumah sakit ke Dinas Kesehatan secara periodik.

### RL 1.1 - Data Dasar Rumah Sakit:

```
┌─────────────────────────────────────────┐
│         RL 1.1 DATA DASAR RS            │
│         RS RUMAH SAKITKU                │
│         Periode: Januari 2026           │
├─────────────────────────────────────────┤
│                                         │
│  1. JENIS TEMPAT TIDUR                  │
│                                         │
│  ┌─────────────────┬────────┬─────────┐ │
│  │ Perawatan       │ Jumlah │ Digunakan│ │
│  ├─────────────────┼────────┼─────────┤ │
│  │ Kelas VIP       │   10   │    8    │ │
│  │ Kelas I         │   20   │   18    │ │
│  │ Kelas II        │   30   │   22    │ │
│  │ Kelas III       │   40   │   35    │ │
│  │ ICU             │    8   │    6    │ │
│  │ NICU            │    5   │    4    │ │
│  │ HCU             │    4   │    3    │ │
│  │ PICU            │    4   │    3    │ │
│  │ Isolasi         │    6   │    4    │ │
│  │ Bayi Sehat      │   10   │    8    │ │
│  ├─────────────────┼────────┼─────────┤ │
│  │ TOTAL           │  137   │  111    │ │
│  └─────────────────┴────────┴─────────┘ │
│                                         │
│  2. TENAGA MEDIS                        │
│                                         │
│  Dokter Spesialis: 25 orang             │
│  Dokter Umum      : 15 orang            │
│  Dokter Gigi      : 5 orang             │
│  Perawat          : 120 orang           │
│  Bidan            : 30 orang            │
│  Farmasi          : 15 orang            │
│  Lainnya          : 200 orang           │
│                                         │
└─────────────────────────────────────────┘
```

### RL 1.2 - Indikator Pelayanan:

| Indikator | Formula | Target |
|-----------|---------|--------|
| **BOR** | (Hari Perawatan / (TT x Periode)) x 100% | 60-85% |
| **LOS** | Lama Dirawat / Pasien Keluar | 6-9 hari |
| **BTO** | Pasien Keluar / TT | 40-50x/tahun |
| **TOI** | (TT x Periode - Hari Perawatan) / Pasien Keluar | 1-3 hari |
| **NDR** | Kematian > 48 jam / Pasien Keluar x 1000‰ | < 25‰ |
| **GDR** | Total Kematian / Pasien Keluar x 1000‰ | < 45‰ |

### Penjelasan Indikator:

#### BOR (Bed Occupancy Rate):

```
BOR = (Hari Perawatan / (Jumlah TT × Jumlah Hari)) × 100%

Contoh:
- Hari Perawatan Januari: 3,000 hari
- Jumlah TT: 100
- Jumlah Hari: 31

BOR = (3,000 / (100 × 31)) × 100%
BOR = (3,000 / 3,100) × 100%
BOR = 96.8% (TERLALU TINGGI)
```

**Interpretasi:**
- < 60%: Efisiensi rendah
- 60-85%: Ideal
- > 85%: Overload, pertimbangkan penambahan TT

#### LOS (Length of Stay):

```
LOS = Total Lama Dirawat (hari) / Jumlah Pasien Keluar

Contoh:
- Total hari rawat: 3,000
- Pasien keluar: 500

LOS = 3,000 / 500 = 6 hari (IDEAL)
```

### Screenshot:
![RL 1 Dashboard](../images/rl1-dashboard.png)

---

## Cara Membaca RL 3 - Indikator Pelayanan

### RL 3.1 - Pelayanan Rawat Inap:

```
┌─────────────────────────────────────────┐
│   RL 3.1 PELAYANAN RAWAT INAP           │
│   RS RUMAH SAKITKU                      │
│   Triwulan I 2026                       │
├─────────────────────────────────────────┤
│                                         │
│  GOLONGAN UMUR    L    P    JUMLAH      │
│  0-7 hari         12   15     27        │
│  8-28 hari         8    5     13        │
│  29hr-1th          5    3      8        │
│  1-4 th           10    8     18        │
│  5-14 th           8    6     14        │
│  15-24 th         15   12     27        │
│  25-44 th         45   32     77        │
│  45-64 th         38   42     80        │
│  >65 th           25   18     43        │
│  ─────────────────────────────────      │
│  TOTAL           186  141    327        │
│                                         │
│  CARA PULANG:                           │
│  - Sembuh        : 280                  │
│  - Membaik       : 30                   │
│  - Belum Sembuh  : 10                   │
│  - Meninggal <48j: 3                    │
│  - Meninggal >48j: 2                    │
│  - Pulang Paksa  : 2                    │
│                                         │
└─────────────────────────────────────────┘
```

### RL 3.2 - Pelayanan Rawat Jalan:

```
┌─────────────────────────────────────────┐
│   RL 3.2 PELAYANAN RAWAT JALAN          │
│   RS RUMAH SAKITKU                      │
│   Bulan Januari 2026                    │
├─────────────────────────────────────────┤
│                                         │
│  POLIKLINIK         BARU    LAMA  TOTAL │
│  Poli Umum           450     850   1300 │
│  Poli Gigi           200     400    600 │
│  Poli Anak           300     500    800 │
│  Poli Jantung        150     250    400 │
│  Poli Saraf          100     200    300 │
│  Poli Kulit           80     150    230 │
│  Poli Mata           120     300    420 │
│  Poli THT             90     180    270 │
│  Poli Orthopedi      110     220    330 │
│  Poli Bedah          130     280    410 │
│  Poli Obsgyn         200     400    600 │
│  ─────────────────────────────────      │
│  TOTAL              1930    3730   5660 │
│                                         │
│  CARA BAYAR:                            │
│  - Umum    : 1,200                      │
│  - BPJS    : 4,000                      │
│  - Asuransi:  460                       │
│                                         │
└─────────────────────────────────────────┘
```

### RL 3.3 - Pelayanan Gawat Darurat:

| Kategori | Jumlah | Persentase |
|----------|--------|------------|
| Kecelakaan Lalu Lintas | 120 | 15% |
| Kecelakaan Kerja | 45 | 5.6% |
| Kecelakaan Rumah Tangga | 80 | 10% |
| Keracunan | 25 | 3.1% |
| Luka Bakar | 30 | 3.8% |
| Non Trauma | 500 | 62.5% |
| **Total** | **800** | **100%** |

### Screenshot:
![RL 3 Laporan](../images/rl3-laporan.png)

---

## Cara Export Laporan ke Excel/PDF

### Export ke Excel:

#### 1. Dari Halaman Laporan:

1. Buka laporan yang diinginkan
2. Klik tombol **"Export"** atau icon 📊
3. Pilih **"Excel (.xlsx)"**
4. Pilih opsi:
   - ☑ Sertakan header
   - ☑ Format tabel
   - ☐ Password proteksi
5. Klik **"Download"**
6. Simpan file ke komputer

#### 2. Format Excel:

File Excel akan berisi:
- Sheet 1: Data utama
- Sheet 2: Grafik (jika ada)
- Sheet 3: Keterangan

### Export ke PDF:

#### 1. Dari Halaman Laporan:

1. Buka laporan
2. Klik **"Export"**
3. Pilih **"PDF"**
4. Pilih opsi:
   - **Orientasi**: Portrait/Landscape
   - **Ukuran kertas**: A4/Letter
   - **Margin**: Normal/Sempit
   - ☑ Header setiap halaman
   - ☑ Nomor halaman
5. Klik **"Generate PDF"**
6. Preview PDF
7. Klik **"Download"**

### Screenshot:
![Export Laporan](../images/export-laporan.png)

### Opsi Export Lainnya:

| Format | Ekstensi | Kegunaan |
|--------|----------|----------|
| Excel | .xlsx | Analisis data, formula |
| PDF | .pdf | Arsip, distribusi |
| CSV | .csv | Import ke sistem lain |
| Word | .docx | Edit laporan |
| PowerPoint | .pptx | Presentasi |

---

## Cara Mencetak Laporan

### Print Langsung:

1. Buka laporan
2. Klik icon 🖨️ **"Cetak"**
3. Atur pengaturan print:
   - Printer
   - Ukuran kertas
   - Orientasi
   - Jumlah copy
4. Klik **"Print"**

### Print to PDF (Virtual Printer):

Jika ingin simpan sebagai PDF:
1. Pilih printer: "Microsoft Print to PDF"
2. Klik **"Print"**
3. Pilih lokasi simpan
4. Beri nama file
5. Klik **"Save"**

### Pengaturan Print:

| Laporan | Ukuran | Orientasi | Margin |
|---------|--------|-----------|--------|
| RL 1 | A4 | Landscape | Normal |
| RL 3 | A4 | Portrait | Normal |
| Dashboard | A4/Letter | Landscape | Sempit |
| Laporan Detail | A4 | Portrait | Normal |

### Screenshot:
![Print Laporan](../images/print-laporan.png)

### Header dan Footer:

```
┌─────────────────────────────────────────┐
│ RS RUMAH SAKITKU          Hal: 1 dari 5 │
│ LAPORAN: RL 3.1           Tgl Cetak:    │
│ Periode: Jan 2026         08/02/2026    │
├─────────────────────────────────────────┤
│                                         │
│         [ISI LAPORAN]                   │
│                                         │
├─────────────────────────────────────────┤
│ Dicetak oleh: [Nama User]               │
│ Dari sistem: SIMRS RumahSakitKu v1.0    │
└─────────────────────────────────────────┘
```

---

## Tips dan Troubleshooting

### Tips Membaca Laporan:

1. **Pahami Konteks**:
   - RL untuk laporan Dinkes
   - Dashboard untuk monitoring
   - Laporan detail untuk analisis

2. **Bandingkan Periode**:
   - Bulan ini vs bulan lalu
   - Tahun ini vs tahun lalu
   - Identifikasi tren

3. **Cross-check Data**:
   - Verifikasi angka yang aneh
   - Cek kelengkapan input
   - Koreksi jika ada kesalahan

### Troubleshooting:

#### 1. Data Laporan Tidak Update

**Gejala**: Laporan hari ini masih kosong

**Solusi**:
1. Cek apakah semua unit sudah input
2. Refresh halaman (F5)
3. Tunggu proses aggregasi (jika besar)
4. Hubungi IT jika > 24 jam tidak update

#### 2. Export Excel Error

**Gejala**: File tidak terdownload atau corrupt

**Solusi**:
1. Cek koneksi internet
2. Coba export dengan range tanggal lebih kecil
3. Gunakan format CSV sebagai alternatif
4. Coba browser lain

#### 3. Print Tidak Rapi

**Gejala**: Hasil print terpotong/tidak rata

**Solusi**:
1. Cek pengaturan margin
2. Preview sebelum print
3. Atur skala (fit to page)
4. Ganti orientasi (portrait/landscape)

#### 4. Angka RL Tidak Sesuai

**Gejala**: Hitungan manual beda dengan sistem

**Solusi**:
1. Verifikasi input data harian
2. Cek pasien yang double terhitung
3. Pastikan kategori benar
4. Koordinasi dengan unit terkait

### Jadwal Pelaporan:

| Laporan | Frekuensi | Tenggat |
|---------|-----------|---------|
| RL 1.1 | Bulanan | Tgl 5 bulan berikutnya |
| RL 3.1 | Bulanan | Tgl 10 bulan berikutnya |
| RL 3.2 | Bulanan | Tgl 10 bulan berikutnya |
| RL 3.3 | Bulanan | Tgl 10 bulan berikutnya |
| Laporan Triwulan | 3 Bulan | Akhir bulan ke-3 |
| Laporan Tahunan | Tahunan | Januari tahun berikutnya |

### Kontak Bantuan:

| Masalah | Kontak |
|---------|--------|
| Teknis sistem | IT Support Ext. 8888 |
| Data laporan | Rekam Medis Ext. 7777 |
| Format RL | Komite Rekam Medis Ext. 6666 |
| Pelaporan Dinkes | Kesyar Ext. 5555 |

---

*Versi Dokumen: 1.0*  
*Terakhir Diperbarui: Februari 2026*
