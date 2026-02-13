# API Kunjungan (Visits)

Dokumen ini menjelaskan semua endpoint untuk manajemen kunjungan (visits) di sistem SIMRS.

## Daftar Isi

- [List Kunjungan](#list-kunjungan)
- [Create Kunjungan](#create-kunjungan)
- [Get Kunjungan](#get-kunjungan)
- [Update Kunjungan](#update-kunjungan)
- [Delete Kunjungan](#delete-kunjungan)
- [Update Status Kunjungan](#update-status-kunjungan)
- [Get Queue Status](#get-queue-status)
- [Check-in Pasien](#check-in-pasien)
- [Check-out Pasien](#check-out-pasien)

---

## List Kunjungan

Mengambil daftar kunjungan dengan pagination.

```http
GET /api/visits
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
| per_page | integer | Tidak | Items per halaman (default: 20) |
| patient_id | integer | Tidak | Filter by patient ID |
| polyclinic_id | integer | Tidak | Filter by polyclinic ID |
| doctor_id | integer | Tidak | Filter by doctor ID |
| visit_type | string | Tidak | Filter by visit type (rawat_jalan, rawat_inap, igd, mcu) |
| status | string | Tidak | Filter by status (pendaftaran, menunggu, proses, selesai, batal) |
| from_date | date | Tidak | Filter dari tanggal (YYYY-MM-DD) |
| to_date | date | Tidak | Filter sampai tanggal (YYYY-MM-DD) |
| is_completed | boolean | Tidak | Filter by completion status |
| priority | string | Tidak | Filter by priority (normal, urgent, emergency) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "patient": {
        "id": 1,
        "medical_record_number": "20240101-0001",
        "name": "John Doe",
        "nik": "1234567890123456",
        "gender": "L",
        "age": 34
      },
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
      "visit_date": "2024-01-15",
      "visit_type": "rawat_jalan",
      "registration_type": "baru",
      "priority": "normal",
      "status": "selesai",
      "complaint": "Sakit kepala dan demam",
      "referral_from": null,
      "referral_number": null,
      "bpjs_sep_number": "0123456789012",
      "check_in_at": "2024-01-15T08:30:00Z",
      "check_out_at": "2024-01-15T10:15:00Z",
      "is_completed": true,
      "notes": null,
      "queue": {
        "queue_number": 15,
        "display_number": "A-015",
        "status": "completed",
        "counter_number": "1"
      },
      "created_at": "2024-01-15T08:00:00Z",
      "updated_at": "2024-01-15T10:15:00Z",
      "duration_minutes": 105
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 85,
    "summary": {
      "total_today": 45,
      "waiting": 12,
      "in_progress": 8,
      "completed": 20,
      "cancelled": 5
    }
  }
}
```

---

## Create Kunjungan

Registrasi kunjungan baru.

```http
POST /api/visits
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
| patient_id | integer | Ya | Patient ID |
| polyclinic_id | integer | Ya | Polyclinic ID |
| doctor_id | integer | Tidak | Doctor ID (jika diketahui) |
| visit_date | date | Ya | Visit date (YYYY-MM-DD) |
| visit_type | string | Ya | Visit type (rawat_jalan, rawat_inap, igd, mcu) |
| registration_type | string | Ya | Registration type (baru, lama, rujukan) |
| priority | string | Tidak | Priority (normal, urgent, emergency) - default: normal |
| complaint | string | Ya | Chief complaint |
| referral_from | string | Tidak | Referral from (jika berlaku) |
| referral_number | string | Tidak | Referral number (jika berlaku) |
| notes | string | Tidak | Additional notes |

### Request Example

```json
{
  "patient_id": 1,
  "polyclinic_id": 1,
  "doctor_id": 5,
  "visit_date": "2024-01-15",
  "visit_type": "rawat_jalan",
  "registration_type": "lama",
  "priority": "normal",
  "complaint": "Sakit kepala dan demam sejak 2 hari yang lalu",
  "notes": "Pasien merujuk diri"
}
```

### Response Sukses (201)

```json
{
  "success": true,
  "message": "Kunjungan berhasil dibuat",
  "data": {
    "id": 45,
    "visit_number": "RJ-20240115-0045",
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "gender": "L",
      "age": 34
    },
    "polyclinic": {
      "id": 1,
      "name": "Poli Umum",
      "code": "UM"
    },
    "doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "visit_date": "2024-01-15",
    "visit_type": "rawat_jalan",
    "registration_type": "lama",
    "priority": "normal",
    "status": "pendaftaran",
    "complaint": "Sakit kepala dan demam sejak 2 hari yang lalu",
    "notes": "Pasien merujuk diri",
    "queue": {
      "queue_number": 15,
      "display_number": "A-015",
      "status": "waiting"
    },
    "created_at": "2024-01-15T08:00:00Z"
  }
}
```

---

## Get Kunjungan

Mengambil informasi detail tentang kunjungan tertentu.

```http
GET /api/visits/{id}
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Query Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| include | string | Related data untuk include (medical_record, prescription, invoice, queue) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 45,
    "visit_number": "RJ-20240115-0045",
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "birth_date": "1990-01-01",
      "gender": "L",
      "phone": "08123456789",
      "address": "Jl. Merdeka No. 123, Jakarta",
      "insurance_type": "bpjs",
      "bpjs_card_number": "0001234567890",
      "age": 34
    },
    "polyclinic": {
      "id": 1,
      "name": "Poli Umum",
      "code": "UM"
    },
    "doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson",
      "employee_number": "EMP005",
      "specialization": "Dokter Umum"
    },
    "visit_date": "2024-01-15",
    "visit_type": "rawat_jalan",
    "registration_type": "lama",
    "priority": "normal",
    "status": "selesai",
    "complaint": "Sakit kepala dan demam sejak 2 hari yang lalu",
    "referral_from": null,
    "referral_number": null,
    "bpjs_sep_number": "0123456789012",
    "check_in_at": "2024-01-15T08:30:00Z",
    "check_out_at": "2024-01-15T10:15:00Z",
    "is_completed": true,
    "notes": "Pasien merujuk diri",
    "queue": {
      "id": 45,
      "queue_number": 15,
      "display_number": "A-015",
      "status": "completed",
      "called_at": "2024-01-15T08:30:00Z",
      "completed_at": "2024-01-15T10:15:00Z",
      "counter_number": "1",
      "waiting_time_minutes": 30
    },
    "medical_record": {
      "id": 78,
      "record_number": "RM-20240115-0078",
      "is_finalized": true,
      "diagnosis_primary": "Tension Type Headache"
    },
    "prescription": {
      "id": 32,
      "prescription_number": "RX-20240115-0032",
      "status": "completed"
    },
    "invoice": {
      "id": 45,
      "invoice_number": "INV-20240115-0045",
      "total_amount": 150000.00,
      "status": "paid"
    },
    "created_at": "2024-01-15T08:00:00Z",
    "updated_at": "2024-01-15T10:15:00Z"
  }
}
```

---

## Update Kunjungan

Update informasi kunjungan.

```http
PUT /api/visits/{id}
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Request Body

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| polyclinic_id | integer | Polyclinic ID |
| doctor_id | integer | Doctor ID |
| priority | string | Priority level |
| complaint | string | Chief complaint |
| notes | string | Additional notes |

### Response Error (422) - Cannot Update Completed Visit

```json
{
  "success": false,
  "message": "Cannot update completed visit",
  "error": {
    "code": "VISIT_COMPLETED",
    "details": {}
  }
}
```

---

## Delete Kunjungan

Cancel/delete a visit.

```http
DELETE /api/visits/{id}
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| reason | string | Ya | Alasan pembatalan |

### Response Error (422) - Visit Already Completed

```json
{
  "success": false,
  "message": "Tidak dapat membatalkan visit yang sudah selesai",
  "error": {
    "code": "VISIT_ALREADY_COMPLETED",
    "details": {}
  }
}
```

---

## Update Status Kunjungan

Update status kunjungan.

```http
PATCH /api/visits/{id}/status
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| status | string | Ya | New status (pendaftaran, menunggu, proses, selesai, batal) |
| notes | string | Tidak | Notes untuk status change |

### Request Example

```json
{
  "status": "proses",
  "notes": "Pasien masuk ruangan pemeriksaan"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Visit status updated to 'proses'",
  "data": {
    "id": 45,
    "status": "proses",
    "previous_status": "menunggu",
    "updated_at": "2024-01-15T08:30:00Z"
  }
}
```

---

## Check-in Pasien

Check-in pasien untuk kunjungannya.

```http
POST /api/visits/{id}/check-in
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Pasien check-in berhasil",
  "data": {
    "id": 45,
    "status": "menunggu",
    "check_in_at": "2024-01-15T08:30:00Z",
    "queue": {
      "queue_number": 15,
      "display_number": "A-015",
      "status": "waiting"
    }
  }
}
```

---

## Check-out Pasien

Check-out pasien setelah kunjungan selesai.

```http
POST /api/visits/{id}/check-out
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Visit ID |

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Pasien check-out berhasil",
  "data": {
    "id": 45,
    "status": "selesai",
    "is_completed": true,
    "check_out_at": "2024-01-15T10:15:00Z",
    "duration_minutes": 105
  }
}
```

---

## Get Queue Status

Mengambil status antrian saat ini untuk poliklinik.

```http
GET /api/visits/queue/status
```

### Query Parameters

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| polyclinic_id | integer | Tidak | Filter by poliklinik |
| date | date | Tidak | Tanggal (default: hari ini) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "date": "2024-01-15",
    "polyclinics": [
      {
        "id": 1,
        "name": "Poli Umum",
        "current_queue": {
          "number": 15,
          "display_number": "A-015",
          "counter": "1"
        },
        "statistics": {
          "total": 45,
          "waiting": 12,
          "called": 3,
          "in_progress": 8,
          "completed": 20,
          "cancelled": 2
        },
        "average_waiting_time_minutes": 25
      }
    ],
    "overall": {
      "total_visits": 156,
      "total_waiting": 45,
      "total_in_progress": 23,
      "total_completed": 78
    }
  }
}
```

---

## Jenis Kunjungan (Visit Types)

| Tipe | Deskripsi |
|------|-----------|
| `rawat_jalan` | Rawat Jalan (Outpatient) |
| `rawat_inap` | Rawat Inap (Inpatient) |
| `igd` | IGD (Emergency Department) |
| `mcu` | Medical Check Up |

---

## Status Kunjungan (Visit Statuses)

| Status | Deskripsi | Allowed Transitions |
|--------|-----------|---------------------|
| `pendaftaran` | Registration | menunggu, batal |
| `menunggu` | Waiting | proses, batal |
| `proses` | In Progress | selesai, batal |
| `selesai` | Completed | - |
| `batal` | Cancelled | - |

---

## Prioritas (Priority Levels)

| Prioritas | Deskripsi | Warna |
|-----------|-----------|-------|
| `normal` | Normal | Hijau |
| `urgent` | Urgent | Kuning |
| `emergency` | Emergency/UGD | Merah |

---

## Tipe Registrasi (Registration Types)

| Tipe | Deskripsi |
|------|-----------|
| `baru` | Pasien baru (first visit) |
| `lama` | Existing patient |
| `rujukan` | Referred from another facility |

---

## Kode Error Reference

| Kode | HTTP Status | Deskripsi |
|------|-------------|-----------|
| `VISIT_NOT_FOUND` | 404 | Visit dengan ID yang ditentukan tidak ditemukan |
| `ACTIVE_VISIT_EXISTS` | 422 | Pasien sudah memiliki active visit |
| `VISIT_COMPLETED` | 422 | Cannot modify completed visit |
| `VISIT_ALREADY_COMPLETED` | 422 | Visit sudah completed |
| `INVALID_STATUS_TRANSITION` | 400 | Status change tidak diizinkan |
| `PATIENT_NOT_FOUND` | 404 | Patient ID tidak ditemukan |
| `POLYCLINIC_NOT_FOUND` | 404 | Polyclinic ID tidak ditemukan |
| `DOCTOR_NOT_FOUND` | 404 | Doctor ID tidak ditemukan |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki akses untuk melakukan aksi ini |
