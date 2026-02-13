# API Rekam Medis (Medical Records)

Dokumen ini menjelaskan semua endpoint untuk manajemen rekam medis (EMR) di sistem SIMRS.

## Daftar Isi

- [List Medical Records](#list-medical-records)
- [Create Medical Record](#create-medical-record)
- [Get Medical Record](#get-medical-record)
- [Update Medical Record](#update-medical-record)
- [Finalize Medical Record](#finalize-medical-record)
- [Medical Record Templates](#medical-record-templates)
- [SOAP Notes](#soap-notes)
- [CPPT Notes](#cppt-notes)

---

## List Medical Records

Mengambil daftar rekam medis dengan pagination.

```http
GET /api/medical-records
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
| visit_id | integer | Tidak | Filter by visit ID |
| doctor_id | integer | Tidak | Filter by doctor ID |
| is_finalized | boolean | Tidak | Filter by finalized status |
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
      "patient": {
        "id": 1,
        "medical_record_number": "20240101-0001",
        "name": "John Doe",
        "gender": "L",
        "age": 34
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      },
      "subjective": "Pasien mengeluh sakit kepala berdenyut sejak 2 hari...",
      "objective": "TD: 120/80, HR: 88, RR: 20, T: 38.2°C...",
      "assessment": "Tension headache with fever",
      "plan": "Paracetamol 500mg 3x1...",
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
    "last_page": 10,
    "per_page": 20,
    "total": 185
  }
}
```

---

## Create Medical Record

Membuat rekam medis baru untuk kunjungan.

```http
POST /api/medical-records
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
| visit_id | integer | Ya | Visit ID |
| doctor_id | integer | Ya |Doctor ID yang menulis |
| subjective | string | Ya | Subjective (keluhan pasien) |
| objective | string | Ya | Objective (temuan pemeriksaan) |
| assessment | string | Tidak | Assessment (diagnosis) |
| plan | string | Tidak | Plan (rencana tindakan) |
| diagnosis_primary | string | Ya | Diagnosis utama |
| diagnosis_secondary | array | Tidak | Diagnosis sekunder (array) |
| icd10_code | string | Ya | ICD-10 code |
| icd10_description | string | Ya | Deskripsi ICD-10 |
| is_finalized | boolean | Tidak | Apakah sudah final (default: false) |
| notes | string | Tidak | Additional notes |

### Request Example

```json
{
  "visit_id": 45,
  "doctor_id": 5,
  "subjective": "Pasien mengeluh sakit kepala berdenyut sejak 2 hari yang lalu, disertai demam. Tidak ada mual atau muntah.",
  "objective": "TD: 120/80 mmHg, N: 88x/menit, RR: 20x/menit, T: 38.2°C. Status generalis baik, compos mentis. Kepala: tenderness pada frontal. Throat: no redness.",
  "assessment": "Tension-type headache dengan infeksi virus",
  "plan": "1. Paracetamol 500mg 3x1 setelah makan\n2. Istirahat cukup\n3. Minum banyak air putih\n4. Kontrol 3 hari jika belum membaik",
  "diagnosis_primary": "Tension Type Headache",
  "diagnosis_secondary": ["Viral infection, unspecified"],
  "icd10_code": "G44.2",
  "icd10_description": "Tension-type headache",
  "is_finalized": false,
  "notes": "Pasien sudah diberi penjelasan tentang obat"
}
```

### Response Sukses (201)

```json
{
  "success": true,
  "message": "Medical record berhasil dibuat",
  "data": {
    "id": 78,
    "record_number": "RM-20240115-0078",
    "visit_id": 45,
    "doctor_id": 5,
    "subjective": "Pasien mengeluh sakit kepala berdenyut sejak 2 hari...",
    "objective": "TD: 120/80, HR: 88, RR: 20, T: 38.2°C...",
    "assessment": "Tension headache with fever",
    "plan": "Paracetamol 500mg 3x1...",
    "diagnosis_primary": "Tension Type Headache",
    "diagnosis_secondary": ["Viral infection, unspecified"],
    "icd10_code": "G44.2",
    "icd10_description": "Tension-type headache",
    "is_finalized": false,
    "finalized_at": null,
    "finalized_by": null,
    "created_at": "2024-01-15T08:30:00Z",
    "updated_at": "2024-01-15T08:30:00Z"
  }
}
```

---

## Get Medical Record

Mengambil rekam medis berdasarkan ID.

```http
GET /api/medical-records/{id}
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Medical Record ID |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 78,
    "record_number": "RM-20240115-0078",
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "visit_date": "2024-01-15",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      }
    },
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "gender": "L",
      "age": 34
    },
    "doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson",
      "specialization": "Dokter Umum"
    },
    "subjective": "Pasien mengeluh sakit kepala...",
    "objective": "TD: 120/80, HR: 88...",
    "assessment": "Tension headache with fever",
    "plan": "Paracetamol 500mg 3x1...",
    "diagnosis_primary": "Tension Type Headache",
    "diagnosis_secondary": ["Viral infection, unspecified"],
    "icd10_code": "G44.2",
    "icd10_description": "Tension-type headache",
    "is_finalized": true,
    "finalized_at": "2024-01-15T10:00:00Z",
    "finalized_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "prescription": {
      "id": 32,
      "prescription_number": "RX-20240115-0032",
      "status": "completed"
    },
    "created_at": "2024-01-15T08:30:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

---

## Update Medical Record

Update rekam medis (hanya jika belum finalized).

```http
PUT /api/medical-records/{id}
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Medical Record ID |

### Request Body

Semua field optional. Hanya field yang diberikan yang akan diupdate.

### Response Error (422) - Already Finalized

```json
{
  "success": false,
  "message": "Cannot update finalized medical record",
  "error": {
    "code": "MEDICAL_RECORD_FINALIZED",
    "details": {}
  }
}
```

---

## Finalize Medical Record

Finalisasi rekam medis (lock dokumen).

```http
POST /api/medical-records/{id}/finalize
```

### URL Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| id | integer | Medical Record ID |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| password | string | Ya | Password user untuk konfirmasi |

### Request Example

```json
{
  "password": "userpassword"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Medical record finalized successfully",
  "data": {
    "id": 78,
    "is_finalized": true,
    "finalized_at": "2024-01-15T10:00:00Z",
    "finalized_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    }
  }
}
```

### Response Error (403) - Invalid Password

```json
{
  "success": false,
  "message": "Invalid password",
  "error": {
    "code": "INVALID_PASSWORD",
    "details": {}
  }
}
```

---

## CPPT Notes

### List CPPT

```http
GET /api/medical-records/{id}/cppt
```

### Create CPPT

```http
POST /api/medical-records/{id}/cppt
```

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| date | date | Ya | Tanggal CPPT |
| shift | string | Ya | Shift (pagi, sore, malam) |
| s_subjective | string | Tidak | Subjective (dari perawat) |
| o_objective | string | Tidak | Objective (TTV dari perawat) |
| a_assessment | string | Tidak | Assessment (dari dokter) |
| p_planning | string | Tidak | Planning (dari dokter) |
| i_implementation | string | Tidak | Implementasi (dari perawat) |
| e_evaluation | string | Tidak | Evaluation (dari dokter/perawat) |
| recorded_by | integer | Ya | User ID (perawat/dokter) |

---

## SOAP Notes

### Format SOAP

SOAP adalah format standar dokumentasi medis:

- **S** (Subjective): Keluhan pasien, riwayat penyakit
- **O** (Objective): Temuan fisik, hasil pemeriksaan, TTV
- **A** (Assessment): Diagnosis/assessment
- **P** (Plan): Rencana tindakan, obat, kontrol

### Template SOAP

Gunakan template untuk konsistensi:

```http
GET /api/medical-records/templates/soap
```

### Apply Template

```http
POST /api/medical-records/{id}/apply-template
```

---

## ICD-10 Integration

### Search ICD-10

```http
GET /api/icd10/search
```

### Query Parameters

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| q | string | Search query (kode atau deskripsi) |
| limit | integer | Max results (default: 10) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "code": "G44.2",
      "description": "Tension-type headache",
      "category": "G44",
      "block": "G44-G47",
      "is_active": true
    }
  ]
}
```

---

## Best Practices

1. **Finalisasi EMR**: Setelah EMR difinalisasi, data tidak bisa diubah. Pastikan data lengkap sebelum finalisasi.
2. **ICD-10 Coding**: Gunakan kode ICD-10 yang paling spesifik sesuai dokumentasi medis.
3. **SOAP Structure**: Ikuti struktur SOAP untuk konsistensi dokumentasi.
4. **Tanda Tangan Elektronik**: Gunakan digital signature untuk finalisasi.
5. **Audit Trail**: Semua CREATE/UPDATE/DELETE tercatat di audit log.

---

## Tipe Kunjungan yang Didukung

| Visit Type | Deskripsi | EMR Required? |
|------------|-----------|---------------|
| `rawat_jalan` | Rawat Jalan | Ya |
| `rawat_inap` | Rawat Inap | Ya (harus diisi per shift) |
| `igd` | IGD | Ya (triase + assessment) |
| `mcu` | Medical Check Up | Ya |

---

## Kode Error Reference

| Kode | HTTP Status | Deskripsi |
|------|-------------|-----------|
| `MEDICAL_RECORD_NOT_FOUND` | 404 | Medical record tidak ditemukan |
| `VISIT_NOT_FOUND` | 404 | Visit tidak ditemukan |
| `MEDICAL_RECORD_FINALIZED` | 422 | Medical record sudah finalized |
| `INVALID_ICD10_CODE` | 422 | ICD-10 code tidak valid |
| `DOCTOR_NOT_FOUND` | 404 | Doctor ID tidak ditemukan |
| `INSUFFICIENT_PERMISSIONS` | 403 | Permission insufficient |
| `MUST_BE_DOCTOR` | 403 | Hanya dokter yang bisa create/finalize MR |
