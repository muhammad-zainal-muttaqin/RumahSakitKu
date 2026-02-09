# BPJS Integration API

This document describes all endpoints for BPJS (Badan Penyelenggara Jaminan Sosial) integration in the SIMRS system.

## Table of Contents

- [Peserta (Participant) Endpoints](#peserta-endpoints)
- [SEP (Surat Eligibilitas Peserta) Endpoints](#sep-endpoints)
- [Rujukan (Referral) Endpoints](#rujukan-endpoints)
- [E-Klaim Endpoints](#e-klaim-endpoints)
- [PCare Endpoints](#pcare-endpoints)

---

## Overview

The SIMRS system integrates with BPJS Kesehatan through the following services:

- **VClaim**: For claims and eligibility verification
- **PCare**: For primary care services
- **E-Klaim**: For electronic claim submission

All BPJS endpoints require special permissions (`bpjs.manage`, `sep.create`, etc.).

---

## Peserta Endpoints

### Get Peserta by NIK

Retrieve BPJS participant information using NIK.

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

### Response Error (404) - Peserta Not Found

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

Retrieve BPJS participant information using BPJS card number.

```http
GET /api/bpjs/peserta/nokartu/{no_kartu}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| no_kartu | string | 13-digit BPJS card number |

### Response Success (200)

Same as Get Peserta by NIK response.

---

## SEP Endpoints

### Create SEP

Create a new Surat Eligibilitas Peserta (SEP).

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
| noKartu | string | Yes | BPJS card number |
| tglSep | string | Yes | SEP date (Y-m-d) |
| ppkPelayanan | string | Yes | Provider code |
| jnsPelayanan | string | Yes | Service type (1: Rawat Inap, 2: Rawat Jalan) |
| klsRawat | string | Yes | Care class (1: Kelas 1, 2: Kelas 2, 3: Kelas 3) |
| noMR | string | Yes | Medical record number |
| rujukan | object | Yes | Referral data |
| catatan | string | No | Notes |
| diagAwal | string | Yes | Initial diagnosis (ICD-10) |
| poli | object | Yes | Polyclinic data |
| cob | object | No | COB data |
| katarak | object | No | Cataract data |
|jaminan | object | No | Guarantee data |
|skdp | object | No | SKDP data |
| noTelp | string | No | Phone number |
| user | string | Yes | User creating SEP |

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

### Response Error (422) - Invalid Data

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

Retrieve SEP information.

```http
GET /api/bpjs/sep/{no_sep}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| no_sep | string | SEP number |

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

Delete/cancel a SEP.

```http
DELETE /api/bpjs/sep/{no_sep}
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user | string | Yes | User cancelling SEP |

### Response Success (200)

```json
{
  "success": true,
  "message": "SEP deleted successfully"
}
```

---

## Rujukan Endpoints

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
| start | string | Yes | Start date (Y-m-d) |
| end | string | Yes | End date (Y-m-d) |

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

## E-Klaim Endpoints

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

## PCare Endpoints

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
| start | integer | No | Start index (default: 0) |
| limit | integer | No | Limit results (default: 10) |

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

### PCare Pendaftaran (Registration)

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

## BPJS Configuration Endpoints

### Get BPJS Logs

Retrieve BPJS API interaction logs.

```http
GET /api/bpjs/logs
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| service_type | string | No | Filter by service (vclaim, pcare, eklaim) |
| from_date | date | No | Filter from date |
| to_date | date | No | Filter to date |
| status | string | No | Filter by status (success, failed) |

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

## BPJS Service Types

| Service | Code | Description |
|---------|------|-------------|
| Rawat Jalan | 2 | Outpatient services |
| Rawat Inap | 1 | Inpatient services |

## BPJS Care Classes

| Class | Code | Description |
|-------|------|-------------|
| Kelas 1 | 1 | First class |
| Kelas 2 | 2 | Second class |
| Kelas 3 | 3 | Third class |

## BPJS Participant Status

| Status | Code | Description |
|--------|------|-------------|
| AKTIF | 0 | Active participant |
| TIDAK AKTIF | 1 | Inactive participant |

## BPJS Faskes Types

| Type | Code | Description |
|------|------|-------------|
| Faskes 1 | 1 | Primary healthcare |
| Faskes 2 | 2 | Secondary healthcare |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `BPJS_PESERTA_NOT_FOUND` | 404 | BPJS participant not found |
| `BPJS_SEP_NOT_FOUND` | 404 | SEP not found |
| `BPJS_INVALID_DATA` | 422 | Invalid BPJS data |
| `BPJS_SERVICE_UNAVAILABLE` | 503 | BPJS service unavailable |
| `BPJS_RATE_LIMIT_EXCEEDED` | 429 | BPJS API rate limit exceeded |
| `BPJS_INVALID_CREDENTIALS` | 401 | Invalid BPJS credentials |
| `BPJS_CLAIM_NOT_FOUND` | 404 | Claim not found |
| `BPJS_CLAIM_ALREADY_PROCESSED` | 422 | Claim already processed |

## Common BPJS Error Codes

| BPJS Code | Description |
|-----------|-------------|
| 200 | Success |
| 201 | Data not found / Participant inactive |
| 202 | Invalid timestamp |
| 203 | Invalid signature |
| 204 | Invalid credentials |
| 205 | Invalid user key |
| 206 | Invalid data format |
| 207 | Duplicate data |
| 208 | Data locked |
| 500 | BPJS internal server error |
