# API Pasien

Dokumen ini menjelaskan semua endpoint untuk manajemen pasien di sistem SIMRS.

## Daftar Isi

- [List Pasien](#list-pasien)
- [Create Pasien](#create-pasien)
- [Get Pasien](#get-pasien)
- [Update Pasien](#update-pasien)
- [Delete Pasien](#delete-pasien)
- [Search Pasien](#search-pasien)
- [Get Pasien Visits](#get-pasien-visits)
- [Get Pasien Medical Records](#get-pasien-medical-records)

---

## List Pasien

Mengambil daftar pasien dengan pagination.

```http
GET /api/patients
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Items per halaman (default: 20, max: 100) |
| search | string | Tidak | Cari berdasarkan nama, RM number, atau NIK |
| is_active | boolean | Tidak | Filter by status aktif |
| insurance_type | string | Tidak | Filter by insurance type (bpjs, umum, asuransi) |
| gender | string | Tidak | Filter by gender (L, P) |
| sort_by | string | Tidak | Sort field (name, created_at, birth_date) |
| sort_order | string | Tidak | Sort direction (asc, desc) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "birth_place": "Jakarta",
      "birth_date": "1990-01-01",
      "gender": "L",
      "blood_type": "O",
      "address": "Jl. Merdeka No. 123, Jakarta",
      "phone": "08123456789",
      "email": "john@example.com",
      "emergency_contact_name": "Jane Doe",
      "emergency_contact_phone": "08198765432",
      "marital_status": "married",
      "occupation": "Pegawai Swasta",
      "insurance_type": "bpjs",
      "insurance_number": "0001234567890",
      "bpjs_card_number": "0001234567890",
      "photo_path": "patients/photos/12345.jpg",
      "is_active": true,
      "registered_at": "2024-01-01T08:00:00Z",
      "created_at": "2024-01-01T08:00:00Z",
      "updated_at": "2024-01-01T08:00:00Z",
      "age": 34
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20
  }
}
```

### Response Error (401)

```json
{
  "success": false,
  "message": "Unauthenticated",
  "error": {
    "code": "UNAUTHENTICATED",
    "details": {}
  }
}
```

---

## Create Pasien

Registrasi pasien baru.

```http
POST /api/patients
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| name | string | Ya | Nama lengkap pasien |
| nik | string | Ya | 16-digit NIK Nasional |
| birth_place | string | Ya | Tempat lahir |
| birth_date | date | Ya | Tanggal lahir (YYYY-MM-DD) |
| gender | string | Ya | Gender (L: Laki-laki, P: Perempuan) |
| blood_type | string | Tidak | Golongan darah (A, B, AB, O) |
| address | string | Ya | Alamat lengkap |
| phone | string | Ya | Nomor telepon |
| email | string | Tidak | Alamat email |
| emergency_contact_name | string | Tidak | Nama kontak darurat |
| emergency_contact_phone | string | Tidak | Nomor telepon kontak darurat |
| marital_status | string | Tidak | Status pernikahan (single, married, divorced, widowed) |
| occupation | string | Tidak | Pekerjaan |
| insurance_type | string | Tidak | Jenis asuransi (bpjs, umum, asuransi) |
| insurance_number | string | Tidak | Nomor asuransi |
| bpjs_card_number | string | Tidak | Nomor kartu BPJS (jika berlaku) |

### Request Example

```json
{
  "name": "John Doe",
  "nik": "1234567890123456",
  "birth_place": "Jakarta",
  "birth_date": "1990-01-01",
  "gender": "L",
  "blood_type": "O",
  "address": "Jl. Merdeka No. 123, Jakarta",
  "phone": "08123456789",
  "email": "john@example.com",
  "emergency_contact_name": "Jane Doe",
  "emergency_contact_phone": "08198765432",
  "marital_status": "married",
  "occupation": "Pegawai Swasta",
  "insurance_type": "bpjs",
  "bpjs_card_number": "0001234567890"
}
```

### Response Sukses (201)

```json
{
  "success": true,
  "message": "Pasien berhasil dibuat",
  "data": {
    "id": 1,
    "medical_record_number": "20240101-0001",
    "name": "John Doe",
    "nik": "1234567890123456",
    "birth_place": "Jakarta",
    "birth_date": "1990-01-01",
    "gender": "L",
    "blood_type": "O",
    "address": "Jl. Merdeka No. 123, Jakarta",
    "phone": "08123456789",
    "email": "john@example.com",
    "emergency_contact_name": "Jane Doe",
    "emergency_contact_phone": "08198765432",
    "marital_status": "married",
    "occupation": "Pegawai Swasta",
    "insurance_type": "bpjs",
    "insurance_number": "0001234567890",
    "bpjs_card_number": "0001234567890",
    "photo_path": null,
    "is_active": true,
    "registered_at": "2024-01-01T08:00:00Z",
    "created_at": "2024-01-01T08:00:00Z",
    "updated_at": "2024-01-01T08:00:00Z",
    "age": 34
  }
}
```

### Response Error (422) - Validasi Error

```json
{
  "success": false,
  "message": "Data yang diberikan tidak valid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "nik": ["NIK harus 16 digit"],
      "name": ["Nama pasien harus diisi"]
    }
  }
}
```

### Response Error (409) - NIK Duplikat

```json
{
  "success": false,
  "message": "Pasien dengan NIK ini sudah terdaftar",
  "error": {
    "code": "DUPLICATE_PATIENT",
    "details": {
      "existing_patient_id": 123,
      "medical_record_number": "20230101-0045"
    }
  }
}
```

---

## Get Pasien

Mengambil informasi detail tentang pasien tertentu.

```http
GET /api/patients/{id}
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| include | string | Related data untuk include (visits, medical_records, invoices) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "medical_record_number": "20240101-0001",
    "name": "John Doe",
    "nik": "1234567890123456",
    "birth_place": "Jakarta",
    "birth_date": "1990-01-01",
    "gender": "L",
    "blood_type": "O",
    "address": "Jl. Merdeka No. 123, Jakarta",
    "phone": "08123456789",
    "email": "john@example.com",
    "emergency_contact_name": "Jane Doe",
    "emergency_contact_phone": "08198765432",
    "marital_status": "married",
    "occupation": "Pegawai Swasta",
    "insurance_type": "bpjs",
    "insurance_number": "0001234567890",
    "bpjs_card_number": "0001234567890",
    "photo_path": "patients/photos/12345.jpg",
    "is_active": true,
    "registered_at": "2024-01-01T08:00:00Z",
    "created_at": "2024-01-01T08:00:00Z",
    "updated_at": "2024-01-01T08:00:00Z",
    "age": 34,
    "statistics": {
      "total_visits": 15,
      "last_visit_date": "2024-01-15",
      "total_medical_records": 12
    }
  }
}
```

---

## Update Pasien

Update informasi pasien.

```http
PUT /api/patients/{id}
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Patient ID |

### Request Body

Semua field optional. Hanya field yang diberikan yang akan diupdate.

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| name | string | Nama lengkap pasien |
| address | string | Alamat lengkap |
| phone | string | Nomor telepon |
| email | string | Alamat email |
| emergency_contact_name | string | Nama kontak darurat |
| emergency_contact_phone | string | Nomor telepon kontak darurat |
| marital_status | string | Status pernikahan |
| occupation | string | Pekerjaan |
| insurance_type | string | Jenis asuransi |
| insurance_number | string | Nomor asuransi |
| bpjs_card_number | string | Nomor kartu BPJS |
| is_active | boolean | Status aktif |

### Request Example

```json
{
  "address": "Jl. Sudirman No. 456, Jakarta",
  "phone": "08123456790",
  "emergency_contact_phone": "08198765433"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Pasien berhasil diperbarui",
  "data": {
    "id": 1,
    "medical_record_number": "20240101-0001",
    "name": "John Doe",
    "nik": "1234567890123456",
    "birth_place": "Jakarta",
    "birth_date": "1990-01-01",
    "gender": "L",
    "blood_type": "O",
    "address": "Jl. Sudirman No. 456, Jakarta",
    "phone": "08123456790",
    "email": "john@example.com",
    "emergency_contact_name": "Jane Doe",
    "emergency_contact_phone": "08198765433",
    "marital_status": "married",
    "occupation": "Pegawai Swasta",
    "insurance_type": "bpjs",
    "insurance_number": "0001234567890",
    "bpjs_card_number": "0001234567890",
    "is_active": true,
    "updated_at": "2024-01-02T10:00:00Z"
  }
}
```

---

## Delete Pasien

Soft delete pasien (tandai sebagai tidak aktif).

```http
DELETE /api/patients/{id}
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| force | boolean | Permanently delete (requires super_admin) |
| reason | string | Alasan penghapusan (untuk audit) |

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Pasien berhasil dihapus"
}
```

### Response Error (422) - Pasien Memiliki Active Visits

```json
{
  "success": false,
  "message": "Tidak dapat menghapus pasien dengan active visits",
  "error": {
    "code": "PATIENT_HAS_ACTIVE_VISITS",
    "details": {
      "active_visits": [
        {
          "visit_id": 45,
          "visit_number": "RJ-20240115-0045",
          "status": "menunggu"
        }
      ]
    }
  }
}
```

---

## Search Pasien

Pencarian lanjutan pasien berdasarkan berbagai kriteria.

```http
GET /api/patients/search
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| nik | string | Tidak | Cari berdasarkan 16-digit NIK |
| rm | string | Tidak | Cari berdasarkan medical record number |
| name | string | Tidak | Cari berdasarkan nama (partial match) |
| phone | string | Tidak | Cari berdasarkan nomor telepon |
| bpjs_card | string | Tidak | Cari berdasarkan nomor kartu BPJS |

> **Catatan:** Minimal satu parameter pencarian diperlukan.

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "birth_date": "1990-01-01",
      "gender": "L",
      "phone": "08123456789",
      "insurance_type": "bpjs",
      "is_active": true,
      "match_score": 0.95
    }
  ],
  "meta": {
    "total": 1,
    "search_criteria": {
      "nik": "1234567890123456"
    }
  }
}
```

---

## Get Pasien Visits

Mengambil semua kunjungan untuk pasien tertentu.

```http
GET /api/patients/{id}/visits
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Items per halaman (default: 20) |
| status | string | Tidak | Filter by visit status |
| from_date | date | Tidak | Filter dari tanggal (YYYY-MM-DD) |
| to_date | date | Tidak | Filter sampai tanggal (YYYY-MM-DD) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "visit_date": "2024-01-15",
      "visit_type": "rawat_jalan",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum",
        "code": "UM"
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson",
        "specialization": "Dokter Umum"
      },
      "status": "selesai",
      "complaint": "Sakit kepala dan demam",
      "bpjs_sep_number": "0123456789012",
      "check_in_at": "2024-01-15T08:30:00Z",
      "check_out_at": "2024-01-15T10:15:00Z",
      "is_completed": true,
      "created_at": "2024-01-15T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  }
}
```

---

## Get Pasien Medical Records

Mengambil semua rekam medis untuk pasien tertentu.

```http
GET /api/patients/{id}/medical-records
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Items per halaman (default: 20) |
| finalized | boolean | Tidak | Filter by finalized status |
| from_date | date | Tidak | Filter dari tanggal (YYYY-MM-DD) |
| to_date | date | Tidak | Filter sampai tanggal (YYYY-MM-DD) |
| icd10_code | string | Tidak | Filter by ICD-10 code |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 78,
      "record_number": "RM-20240115-0078",
      "visit_id": 45,
      "visit_date": "2024-01-15",
      "subjective": "Pasien mengeluh sakit kepala berdenyut sejak 2 hari yang lalu, disertai demam",
      "objective": "TD: 120/80, HR: 88, RR: 20, T: 38.2°C. Kesadaran compos mentis",
      "assessment": "Tension headache with fever",
      "plan": "Paracetamol 500mg 3x1, Istirahat cukup, Kontrol 3 hari",
      "diagnosis_primary": "Tension Type Headache",
      "diagnosis_secondary": "Common Cold",
      "icd10_code": "G44.2",
      "icd10_description": "Tension-type headache",
      "is_finalized": true,
      "finalized_at": "2024-01-15T10:00:00Z",
      "finalized_by": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      },
      "created_at": "2024-01-15T08:30:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 32
  }
}
```

### Response Error (403) - Medical Record Access Denied

```json
{
  "success": false,
  "message": "Anda tidak memiliki permission untuk melihat medical record pasien ini",
  "error": {
    "code": "MEDICAL_RECORD_ACCESS_DENIED",
    "details": {}
  }
}
```

---

## Tipe Data Reference

### Gender Pasien

| Kode | Deskripsi |
|------|-----------|
| L | Laki-laki (Male) |
| P | Perempuan (Female) |

### Golongan Darah

| Kode | Deskripsi |
|------|-----------|
| A | A |
| B | B |
| AB | AB |
| O | O |

### Jenis Asuransi

| Tipe | Deskripsi |
|------|-----------|
| bpjs | BPJS Kesehatan |
| umum | Umum (Self-pay) |
| asuransi | Asuransi Swasta |

### Status Pernikahan

| Status | Deskripsi |
|--------|-----------|
| single | Belum Menikah |
| married | Menikah |
| divorced | Cerai |
| widowed | Janda/Duda |

---

## Kode Error Reference

| Kode | HTTP Status | Deskripsi |
|------|-------------|-----------|
| `PATIENT_NOT_FOUND` | 404 | Pasien dengan ID yang ditentukan tidak ditemukan |
| `DUPLICATE_PATIENT` | 409 | Pasien dengan NIK ini sudah terdaftar |
| `INVALID_NIK_FORMAT` | 422 | NIK harus tepat 16 digit |
| `IMMUTABLE_FIELD` | 403 | Mencoba mengubah field read-only |
| `PATIENT_HAS_ACTIVE_VISITS` | 422 | Tidak dapat menghapus pasien dengan active visits |
| `MEDICAL_RECORD_ACCESS_DENIED` | 403 | User lacks permission untuk melihat medical records |
| `VALIDATION_ERROR` | 422 | Request validation failed |
