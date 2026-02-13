# BPJS Integration API

Dokumen ini mendeskripsikan semua endpoint untuk integrasi BPJS (Badan Penyelenggara Jaminan Sosial) dalam sistem SIMRS.

## Daftar Isi

- [Endpoint Peserta](#endpoint-peserta)
- [Endpoint SEP (Surat Eligibilitas Peserta)](#endpoint-sep)
- [Endpoint Rujukan](#endpoint-rujukan)
- [Endpoint E-Klaim](#endpoint-e-klaim)
- [Endpoint PCare](#endpoint-pcare)

---

## Gambaran Umum

Sistem SIMRS terintegrasi dengan BPJS Kesehatan melalui layanan berikut:

- **VClaim**: Untuk klaim dan verifikasi eligibilitas
- **PCare**: Untuk layanan primer (rawat jalan)
- **E-Klaim**: Untuk pengajuan klaim elektronik

Semua endpoint BPJS memerlukan izin khusus (`bpjs.manage`, `sep.create`, dll).

---

## Endpoint Peserta

### Get Peserta by NIK

Mengambil informasi peserta BPJS menggunakan NIK.

```http
GET /api/bpjs/peserta/nik/{nik}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| nik | string | 16-digit National ID (NIK) |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "peserta": {
      "noKartu": "0001234567890",
      "nik": "1234567890123456",
      "nama": "John Doe",
      "pisa": "1",
      "sex": "L",
      "tglLahir": "1990-01-01",
      "tglTAT": "2025-12-31",
      "tglTMT": "2020-01-01",
      "statusPeserta": {
        "keterangan": "AKTIF",
        "kode": "0"
      },
      "jenisPeserta": {
        "keterangan": "Pekerja Penerima Upah",
        "kode": "1"
      },
      "kelasTanggungan": {
        "keterangan": "Kelas 1",
        "kode": "1"
      },
      "provUmum": {
        "kdProvider": "0123B001",
        "nmProvider": "KLINIK MAKMUR"
      },
      "statusAktif": {
        "keterangan": "AKTIF",
        "kode": "0"
      }
    }
  },
  "meta": {
    "bpjs_code": "200",
    "bpjs_message": "OK"
  }
}
```

### Response Error (404) - Peserta Tidak Ditemukan

```json
{
  "success": false,
  "message": "Peserta tidak ditemukan",
  "error": {
    "code": "BPJS_PESERTA_NOT_FOUND",
    "details": {
      "nik": "1234567890123456"
    }
  }
}
```

---

### Get Peserta by No Kartu

Mengambil informasi peserta BPJS menggunakan nomor kartu BPJS.

```http
GET /api/bpjs/peserta/nokartu/{no_kartu}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| no_kartu | string | 13-digit BPJS card number |

### Response Success (200)

Sama dengan response Get Peserta by NIK.

---

## Endpoint SEP

### Create SEP

Membuat Surat Eligibilitas Peserta (SEP) baru.

```http
POST /api/bpjs/sep
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| noKartu | string | Yes | Nomor kartu BPJS |
| tglSep | string | Yes | Tanggal SEP (Y-m-d) |
| ppkPelayanan | string | Yes | Kode provider |
| jnsPelayanan | string | Yes | Jenis pelayanan (1: Rawat Inap, 2: Rawat Jalan) |
| klsRawat | string | Yes | Kelas perawatan (1: Kelas 1, 2: Kelas 2, 3: Kelas 3) |
| noMR | string | Yes | Nomor rekam medis |
| rujukan | object | Yes | Data rujukan |
| catatan | string | No | Catatan |
| diagAwal | string | Yes | Diagnosis awal (ICD-10) |
| poli | object | Yes | Data poliklinik |
| cob | object | No | Data COB |
| katarak | object | No | Data katarak |
|jaminan | object | No | Data jaminan |
|skdp | object | No | Data SKDP |
| noTelp | string | No | Nomor telepon |
| user | string | Yes | Pengguna yang membuat SEP |

### Request Example

```json
{
  "noKartu": "0001234567890",
  "tglSep": "2024-01-15",
  "ppkPelayanan": "0123B001",
  "jnsPelayanan": "2",
  "klsRawat": "1",
  "noMR": "20240101-0001",
  "rujukan": {
    "asalRujukan": "1",
    "tglRujukan": "2024-01-15",
    "noRujukan": "0123B0010124A000001",
    "ppkRujukan": "0123B002"
  },
  "catatan": "Pasien rujukan",
  "diagAwal": "G44.2",
  "poli": {
    "tujuan": "UMU",
    "eksekutif": "0"
  },
  "cob": {
    "cob": "0"
  },
  "katarak": {
    "katarak": "0"
  },
  "jaminan": {
    "lakaLantas": "0",
    "penjamin": {
      "penjamin": "",
      "tglKejadian": "",
      "keterangan": "",
      "suplesi": {
        "suplesi": "0",
        "noSepSuplesi": "",
        "lokasiLaka": {
          "kdPropinsi": "",
          "kdKabupaten": "",
          "kdKecamatan": ""
        }
      }
    }
  },
  "skdp": {
    "noSurat": "0123B0010124A000001",
    "kodeDPJP": "12345"
  },
  "noTelp": "08123456789",
  "user": "admin"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "SEP created successfully",
  "data": {
    "sep": {
      "noSep": "0123B0010124A000001",
      "tglSep": "2024-01-15",
      "noKartu": "0001234567890",
      "nama": "John Doe",
      "jnsPelayanan": "Rawat Jalan",
      "poli": "Poli Umum",
      "diagnosa": "Tension-type headache",
      "noRujukan": "0123B0010124A000001",
      "ppkRujukan": "KLINIK SEHAT"
    }
  },
  "meta": {
    "bpjs_code": "200",
    "bpjs_message": "SEP berhasil dibuat"
  }
}
```

### Response Error (422) - Data Tidak Valid

```json
{
  "success": false,
  "message": "Data SEP tidak valid",
  "error": {
    "code": "BPJS_INVALID_DATA",
    "details": {
      "bpjs_code": "201",
      "bpjs_message": "Peserta tidak aktif"
    }
  }
}
```

---

### Get SEP

Mengambil informasi SEP.

```http
GET /api/bpjs/sep/{no_sep}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| no_sep | string | Nomor SEP |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "sep": {
      "noSep": "0123B0010124A000001",
      "tglSep": "2024-01-15",
      "noKartu": "0001234567890",
      "nama": "John Doe",
      "kelasRawat": "Kelas 1",
      "jnsPelayanan": "Rawat Jalan",
      "poli": "Poli Umum",
      "diagnosa": "Tension-type headache",
      "catatan": "Pasien rujukan",
      "noRujukan": "0123B0010124A000001",
      "tglRujukan": "2024-01-15",
      "ppkRujukan": "KLINIK SEHAT"
    }
  }
}
```

---

### Delete SEP

Menghapus/membatalkan SEP.

```http
DELETE /api/bpjs/sep/{no_sep}
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user | string | Yes | Pengguna yang membatalkan SEP |

### Response Success (200)

```json
{
  "success": true,
  "message": "SEP deleted successfully"
}
```

---

## Endpoint Rujukan

### Get Referral by Number

```http
GET /api/bpjs/rujukan/{no_rujukan}
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "rujukan": {
      "noRujukan": "0123B0010124A000001",
      "tglRujukan": "2024-01-15",
      "noKartu": "0001234567890",
      "nama": "John Doe",
      "ppkRujukan": {
        "kode": "0123B002",
        "nama": "KLINIK SEHAT"
      },
      "poliRujukan": {
        "kode": "UMU",
        "nama": "Poli Umum"
      },
      "diagnosa": {
        "kode": "G44.2",
        "nama": "Tension-type headache"
      },
      "keluhan": "Sakit kepala",
      "tglKunjungan": "2024-01-15"
    }
  }
}
```

---

### List Referral History

```http
GET /api/bpjs/rujukan/history/{no_kartu}
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start | string | Yes | Tanggal mulai (Y-m-d) |
| end | string | Yes | Tanggal akhir (Y-m-d) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "noRujukan": "0123B0010124A000001",
      "tglRujukan": "2024-01-15",
      "noKartu": "0001234567890",
      "nama": "John Doe",
      "ppkRujukan": "KLINIK SEHAT",
      "poliRujukan": "Poli Umum",
      "diagnosa": "Tension-type headache",
      "status": "Aktif"
    }
  ]
}
```

---

## Endpoint E-Klaim

### Submit Individual Claim

```http
POST /api/bpjs/eklaim/submit
```

### Request Body

```json
{
  "tgl_pelayanan": "2024-01-15",
  "no_sep": "0123B0010124A000001",
  "no_kartu": "0001234567890",
  "no_rm": "20240101-0001",
  "nama_pasien": "John Doe",
  "tgl_lahir": "1990-01-01",
  "gender": "L",
  "kelas_rawat": "1",
  "adl_sub_acute": "",
  "adl_chronic": "",
  "diagnosa": [
    {
      "kode": "G44.2",
      "level": "1"
    }
  ],
  "procedure": [
    {
      "kode": "99.99",
      "level": "1"
    }
  ],
  "tarif_rs": {
    "prosedur_non_bedah": "100000",
    "prosedur_bedah": "0",
    "konsultasi": "50000",
    "tenaga_ahli": "0",
    "keperawatan": "25000",
    "penunjang": "50000",
    "radiologi": "0",
    "laboratorium": "75000",
    "pelayanan_darah": "0",
    "rehabilitasi": "0",
    "kamar": "0",
    "rawat_intensif": "0",
    "obat": "50000",
    "alkes": "10000",
    "bmhp": "0",
    "sewa_alat": "0"
  }
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Claim submitted successfully",
  "data": {
    "claim_id": "CLAIM-2024-001234",
    "status": "Submitted",
    "grouper": {
      "code": "I-10-01",
      "description": "Tension-type headache",
      "tarif": "360000"
    }
  }
}
```

---

### Get Claim Status

```http
GET /api/bpjs/eklaim/status/{no_sep}
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "claim_id": "CLAIM-2024-001234",
    "no_sep": "0123B0010124A000001",
    "status": "Processed",
    "status_detail": "Klaim sudah di-grouping",
    "grouper": {
      "code": "I-10-01",
      "description": "Tension-type headache",
      "tarif": "360000",
      "tarif_inacbg": "350000"
    },
    "topup": {
      "individu": "0",
      "jaminan": "0"
    }
  }
}
```

---

## Endpoint PCare

### Get PCare Peserta

```http
GET /api/bpjs/pcare/peserta/{no_kartu}
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "noKartu": "0001234567890",
    "nama": "John Doe",
    "nik": "1234567890123456",
    "tglLahir": "1990-01-01",
    "sex": "L",
    "kdProvider": "0123B001",
    "nmProvider": "KLINIK MAKMUR",
    "kdProviderGigi": "",
    "nmProviderGigi": "",
    "kelasTanggungan": "Kelas 1",
    "jenisPeserta": "Pekerja Penerima Upah",
    "statusPeserta": "AKTIF",
    "tglTMT": "2020-01-01",
    "tglTAT": "2025-12-31"
  }
}
```

---

### Get PCare Provider

```http
GET /api/bpjs/pcare/provider
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start | integer | No | Indeks mulai (default: 0) |
| limit | integer | No | Batas hasil (default: 10) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "kdProvider": "0123B001",
      "nmProvider": "KLINIK MAKMUR",
      "alamat": "Jl. Sudirman No. 123",
      "noTelp": "0211234567",
      "jenisFaskes": "1"
    }
  ],
  "meta": {
    "total": 150
  }
}
```

---

### PCare Pendaftaran (Registrasi)

```http
POST /api/bpjs/pcare/pendaftaran
```

### Request Body

```json
{
  "kdProviderPeserta": "0123B001",
  "tglDaftar": "15-01-2024",
  "noKartu": "0001234567890",
  "kdPoli": "001",
  "keluhan": "Sakit kepala dan demam",
  "kunjSakit": "true",
  "sistole": "120",
  "diastole": "80",
  "beratBadan": "70",
  "tinggiBadan": "170",
  "heartRate": "88",
  "respRate": "20",
  "lingkarPerut": "85",
  "rujukBalik": "0",
  "kdTkp": "10"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "noUrut": "123",
    "noKartu": "0001234567890",
    "nama": "John Doe",
    "kdPoli": "001",
    "nmPoli": "Poli Umum",
    "tglDaftar": "15-01-2024"
  }
}
```

---

## Endpoint Konfigurasi BPJS

### Get BPJS Logs

Mengambil log interaksi API BPJS.

```http
GET /api/bpjs/logs
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| service_type | string | No | Filter berdasarkan layanan (vclaim, pcare, eklaim) |
| from_date | date | No | Filter dari tanggal |
| to_date | date | No | Filter sampai tanggal |
| status | string | No | Filter berdasarkan status (success, failed) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "service_type": "vclaim",
      "endpoint": "peserta/nik/1234567890123456",
      "method": "GET",
      "http_status": 200,
      "status": "success",
      "execution_time_ms": 456,
      "executed_at": "2024-01-15T08:00:00Z",
      "user": {
        "id": 1,
        "name": "Admin"
      }
    }
  ]
}
```

## Jenis Layanan BPJS

| Layanan | Kode | Deskripsi |
|---------|------|-------------|
| Rawat Jalan | 2 | Layanan rawat jalan |
| Rawat Inap | 1 | Layanan rawat inap |

## Kelas Perawatan BPJS

| Kelas | Kode | Deskripsi |
|-------|------|-------------|
| Kelas 1 | 1 | Kelas pertama |
| Kelas 2 | 2 | Kelas kedua |
| Kelas 3 | 3 | Kelas ketiga |

## Status Peserta BPJS

| Status | Kode | Deskripsi |
|--------|------|-------------|
| AKTIF | 0 | Peserta aktif |
| TIDAK AKTIF | 1 | Peserta tidak aktif |

## Jenis Faskes BPJS

| Jenis | Kode | Deskripsi |
|------|------|-------------|
| Faskes 1 | 1 | Pelayanan kesehatan primer |
| Faskes 2 | 2 | Pelayanan kesehatan sekunder |

## Referensi Kode Error

| Kode | HTTP Status | Deskripsi |
|------|-------------|-------------|
| `BPJS_PESERTA_NOT_FOUND` | 404 | Peserta BPJS tidak ditemukan |
| `BPJS_SEP_NOT_FOUND` | 404 | SEP tidak ditemukan |
| `BPJS_INVALID_DATA` | 422 | Data BPJS tidak valid |
| `BPJS_SERVICE_UNAVAILABLE` | 503 | Layanan BPJS tidak tersedia |
| `BPJS_RATE_LIMIT_EXCEEDED` | 429 | Batas rate limit API BPJS terlampaui |
| `BPJS_INVALID_CREDENTIALS` | 401 | Kredensial BPJS tidak valid |
| `BPJS_CLAIM_NOT_FOUND` | 404 | Klaim tidak ditemukan |
| `BPJS_CLAIM_ALREADY_PROCESSED` | 422 | Klaim sudah diproses |

## Kode Error Umum BPJS

| Kode BPJS | Deskripsi |
|-----------|-------------|
| 200 | Sukses |
| 201 | Data tidak ditemukan / Peserta tidak aktif |
| 202 | Timestamp tidak valid |
| 203 | Signature tidak valid |
| 204 | Kredensial tidak valid |
| 205 | User key tidak valid |
| 206 | Format data tidak valid |
| 207 | Data duplikat |
| 208 | Data terkunci |
| 500 | Error internal server BPJS |
