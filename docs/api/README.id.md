# Dokumentasi API SIMRS

## Pendahuluan

Selamat datang di dokumentasi API **SIMRS (Sistem Informasi Manajemen Rumah Sakit)**. API komprehensif ini menyediakan akses programatik ke semua fungsi manajemen rumah sakit termasuk manajemen pasien, rekam medis, farmasi, billing, dan integrasi dengan sistem kesehatan nasional seperti BPJS dan Satu Sehat.

## Base URL

```
Production:  https://api.rumahsakitku.com/api
Staging:     https://staging-api.rumahsakitku.com/api
Local:       http://localhost:8000/api
```

## Versi API

Versi API Saat Ini: `v1`

Semua endpoint diawali dengan `/api/v1/`. Untuk kompatibilitas mundur, `/api/` tanpa versi akan mengarah ke versi terbaru.

## Otentikasi

API SIMRS menggunakan otentikasi berbasis token **Laravel Sanctum**. Semua permintaan API harus menyertakan token otentikasi di header.

### Header Otentikasi

```http
Authorization: Bearer {your_access_token}
```

### Mendapatkan Token

Token diperoleh melalui endpoint login:

```http
POST /api/auth/login
```

Lihat [AUTHENTICATION.id.md](./AUTHENTICATION.id.md) untuk alur otentikasi yang detail.

## Format Respons

Semua respons API mengikuti format JSON yang standar:

### Respons Sukses

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "request_id": "req_123456789"
  }
}
```

### Respons Error

```json
{
  "success": false,
  "message": "Error description",
  "error": {
    "code": "ERROR_CODE",
    "details": { ... }
  },
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "request_id": "req_123456789"
  }
}
```

### Respons Paginasi

Endpoint daftar mengembalikan hasil yang dipaginasi:

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/patients?page=1",
    "last": "/api/patients?page=10",
    "prev": null,
    "next": "/api/patients?page=2"
  }
}
```

## Kode Status HTTP

| Kode | Deskripsi | Penggunaan |
|------|-----------|------------|
| 200 | OK | Permintaan GET, PUT, PATCH yang berhasil |
| 201 | Created | Permintaan POST yang berhasil |
| 204 | No Content | Permintaan DELETE yang berhasil |
| 400 | Bad Request | Format atau parameter permintaan tidak valid |
| 401 | Unauthorized | Otentikasi tidak ada atau tidak valid |
| 403 | Forbidden | Izin tidak mencukupi |
| 404 | Not Found | Resource tidak ada |
| 422 | Unprocessable Entity | Kesalahan validasi |
| 429 | Too Many Requests | Batas rate limit terlampaui |
| 500 | Internal Server Error | Kesalahan sisi server |
| 503 | Service Unavailable | Layanan eksternal tidak tersedia |

## Penanganan Error

### Kode Error Umum

| Kode | Deskripsi | Solusi |
|------|-----------|--------|
| `INVALID_CREDENTIALS` | Username atau password salah | Periksa kredensial dan coba lagi |
| `TOKEN_EXPIRED` | Token otentikasi telah kedaluwarsa | Segarkan atau dapatkan token baru |
| `INSUFFICIENT_PERMISSIONS` | Pengguna tidak memiliki peran yang diperlukan | Hubungi administrator untuk akses |
| `RESOURCE_NOT_FOUND` | Resource yang diminta tidak ditemukan | Verifikasi ID resource |
| `VALIDATION_ERROR` | Validasi input gagal | Periksa parameter permintaan |
| `RATE_LIMIT_EXCEEDED` | Terlalu banyak permintaan | Tunggu dan coba lagi |
| `BPJS_SERVICE_ERROR` | Layanan BPJS tidak tersedia | Coba lagi atau hubungi dukungan |
| `SATU_SEHAT_ERROR` | Layanan Satu Sehat tidak tersedia | Coba lagi atau hubungi dukungan |

### Kesalahan Validasi

Ketika validasi gagal (422), respons menyertakan pesan error yang detail:

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "nik": ["The NIK field is required", "The NIK must be 16 digits"],
      "name": ["The name field is required"]
    }
  }
}
```

## Rate Limiting

Permintaan API dibatasi rate-nya untuk memastikan penggunaan yang adil:

| Tipe Endpoint | Batas | Jendela Waktu |
|---------------|-------|---------------|
| Otentikasi | 10 permintaan | 1 menit |
| API Standar | 100 permintaan | 1 menit |
| Operasi Batch | 20 permintaan | 1 menit |
| Integrasi BPJS | 60 permintaan | 1 menit |
| Integrasi Satu Sehat | 120 permintaan | 1 menit |

Header rate limit disertakan dalam semua respons:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Header Permintaan/Respons

### Header yang Diwajibkan

| Header | Nilai | Deskripsi |
|--------|-------|-----------|
| `Authorization` | `Bearer {token}` | Token otentikasi |
| `Accept` | `application/json` | Format respons yang diharapkan |
| `Content-Type` | `application/json` | Format body permintaan (untuk POST/PUT) |

### Header Opsional

| Header | Nilai | Deskripsi |
|--------|-------|-----------|
| `X-Request-ID` | UUID | Pengidentifikasi unik untuk pelacakan permintaan |
| `X-Client-Version` | Semver | Versi aplikasi klien |
| `Accept-Language` | `id`, `en` | Bahasa yang diutamakan untuk pesan |

## Modul API

| Modul | Deskripsi | Dokumentasi |
|-------|-----------|-------------|
| Otentikasi | Login pengguna, logout, manajemen token | [AUTHENTICATION.id.md](./AUTHENTICATION.id.md) |
| Pasien | Registrasi dan manajemen pasien | [PATIENTS.id.md](./PATIENTS.id.md) |
| Kunjungan | Manajemen kunjungan/visit | [VISITS.id.md](./VISITS.id.md) |
| Rekam Medis | EMR, catatan SOAP, CPPT | [MEDICAL_RECORDS.id.md](./MEDICAL_RECORDS.id.md) |
| BPJS | API integrasi BPJS | [BPJS.id.md](./BPJS.id.md) |
| Satu Sehat | Integrasi FHIR Satu Sehat | [SATU_SEHAT.id.md](./SATU_SEHAT.id.md) |
| Farmasi | Resep dan manajemen obat | [PHARMACY.id.md](./PHARMACY.id.md) |
| Billing | Faktur dan pembayaran | [BILLING.id.md](./BILLING.id.md) |
| Webhooks | Notifikasi event | [WEBHOOKS.id.md](./WEBHOOKS.id.md) |

## Format Tanggal/Waktu

Semua tanggal dan waktu dalam format **ISO 8601** dan zona waktu **UTC** kecuali ditentukan lain:

```
YYYY-MM-DDTHH:mm:ssZ
2024-01-01T12:00:00Z
```

Untuk field tanggal saja:

```
YYYY-MM-DD
2024-01-01
```

## Tipe Data Umum

| Tipe | Format | Contoh |
|------|--------|--------|
| `date` | ISO 8601 Date | `2024-01-01` |
| `datetime` | ISO 8601 DateTime | `2024-01-01T12:00:00Z` |
| `decimal` | Numerik dengan 2 desimal | `150000.00` |
| `nik` | 16 digit | `1234567890123456` |
| `bpjs_card` | 13 digit | `0001234567890` |
| `phone` | Format E.164 | `+628123456789` |
| `uuid` | UUID v4 | `550e8400-e29b-41d4-a716-446655440000` |

## SDK dan Tools

### SDK Resmi

- PHP: `composer require rumahsakitku/simrs-sdk`
- JavaScript/Node.js: `npm install @rumahsakitku/simrs-sdk`
- Python: `pip install rumahsakitku-simrs`

### Koleksi Postman

Unduh koleksi Postman kami: [Koleksi Postman API SIMRS](https://api.rumahsakitku.com/docs/postman)

## Dukungan

Untuk dukungan teknis dan pertanyaan:

- **Dokumentasi**: https://docs.rumahsakitku.com
- **Email Dukungan**: api-support@rumahsakitku.com
- **Pelacakan Isu**: https://github.com/rumahsakitku/simrs-api/issues
- **Status Layanan**: https://status.rumahsakitku.com

## Changelog

### v1.0.0 (2024-01-01)

- Rilis API awal
- Endpoint manajemen pasien
- Endpoint manajemen kunjungan
- Endpoint rekam medis (EMR)
- Integrasi BPJS
- Integrasi Satu Sehat
- Manajemen farmasi
- Endpoint billing dan pembayaran
