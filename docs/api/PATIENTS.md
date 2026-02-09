# Patients API

This document describes all endpoints for patient management in the SIMRS system.

## Table of Contents

- [List Patients](#list-patients)
- [Create Patient](#create-patient)
- [Get Patient](#get-patient)
- [Update Patient](#update-patient)
- [Delete Patient](#delete-patient)
- [Search Patients](#search-patients)
- [Get Patient Visits](#get-patient-visits)
- [Get Patient Medical Records](#get-patient-medical-records)

---

## List Patients

Retrieve a paginated list of all patients.

```http
GET /api/patients
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |
| search | string | No | Search by name, RM number, or NIK |
| is_active | boolean | No | Filter by active status |
| insurance_type | string | No | Filter by insurance type (bpjs, umum, asuransi) |
| gender | string | No | Filter by gender (L, P) |
| sort_by | string | No | Sort field (name, created_at, birth_date) |
| sort_order | string | No | Sort direction (asc, desc) |

### Response Success (200)

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
  },
  "links": {
    "first": "/api/patients?page=1",
    "last": "/api/patients?page=10",
    "prev": null,
    "next": "/api/patients?page=2"
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

### Response Error (403)

```json
{
  "success": false,
  "message": "You do not have permission to view patients",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Create Patient

Register a new patient in the system.

```http
POST /api/patients
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
| name | string | Yes | Patient full name |
| nik | string | Yes | 16-digit National ID (NIK) |
| birth_place | string | Yes | Place of birth |
| birth_date | date | Yes | Date of birth (YYYY-MM-DD) |
| gender | string | Yes | Gender (L: Laki-laki, P: Perempuan) |
| blood_type | string | No | Blood type (A, B, AB, O) |
| address | string | Yes | Full address |
| phone | string | Yes | Phone number |
| email | string | No | Email address |
| emergency_contact_name | string | No | Emergency contact name |
| emergency_contact_phone | string | No | Emergency contact phone |
| marital_status | string | No | Marital status (single, married, divorced, widowed) |
| occupation | string | No | Occupation |
| insurance_type | string | No | Insurance type (bpjs, umum, asuransi) |
| insurance_number | string | No | Insurance number |
| bpjs_card_number | string | No | BPJS card number (if applicable) |

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

### Response Success (201)

```json
{
  "success": true,
  "message": "Patient created successfully",
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

### Response Error (422) - Validation Error

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "nik": ["The NIK has already been taken"],
      "name": ["The name field is required"],
      "birth_date": ["The birth date must be a date before today"]
    }
  }
}
```

### Response Error (409) - Duplicate NIK

```json
{
  "success": false,
  "message": "Patient with this NIK already exists",
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

## Get Patient

Retrieve detailed information about a specific patient.

```http
GET /api/patients/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Patient ID |

### Response Success (200)

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

### Response Error (404)

```json
{
  "success": false,
  "message": "Patient not found",
  "error": {
    "code": "PATIENT_NOT_FOUND",
    "details": {}
  }
}
```

---

## Update Patient

Update patient information.

```http
PUT /api/patients/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Patient ID |

### Request Body

All fields are optional. Only provided fields will be updated.

| Parameter | Type | Description |
|-----------|------|-------------|
| name | string | Patient full name |
| address | string | Full address |
| phone | string | Phone number |
| email | string | Email address |
| emergency_contact_name | string | Emergency contact name |
| emergency_contact_phone | string | Emergency contact phone |
| marital_status | string | Marital status |
| occupation | string | Occupation |
| insurance_type | string | Insurance type |
| insurance_number | string | Insurance number |
| bpjs_card_number | string | BPJS card number |
| is_active | boolean | Active status |

### Request Example

```json
{
  "address": "Jl. Sudirman No. 456, Jakarta",
  "phone": "08123456790",
  "emergency_contact_phone": "08198765433"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient updated successfully",
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

### Response Error (403) - Immutable Field

```json
{
  "success": false,
  "message": "Cannot modify immutable field: nik",
  "error": {
    "code": "IMMUTABLE_FIELD",
    "details": {
      "field": "nik"
    }
  }
}
```

---

## Delete Patient

Soft delete a patient (mark as inactive).

```http
DELETE /api/patients/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| force | boolean | No | Permanently delete (requires super_admin) |
| reason | string | No | Reason for deletion (audit purposes) |

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient deleted successfully"
}
```

### Response Error (422) - Has Active Visits

```json
{
  "success": false,
  "message": "Cannot delete patient with active visits",
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

## Search Patients

Advanced search for patients by various criteria.

```http
GET /api/patients/search
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| nik | string | No | Search by 16-digit NIK |
| rm | string | No | Search by medical record number |
| name | string | No | Search by name (partial match) |
| phone | string | No | Search by phone number |
| bpjs_card | string | No | Search by BPJS card number |

> **Note:** At least one search parameter is required.

### Response Success (200)

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

## Get Patient Visits

Retrieve all visits for a specific patient.

```http
GET /api/patients/{id}/visits
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20) |
| status | string | No | Filter by visit status |
| from_date | date | No | Filter from date (YYYY-MM-DD) |
| to_date | date | No | Filter to date (YYYY-MM-DD) |

### Response Success (200)

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

## Get Patient Medical Records

Retrieve all medical records for a specific patient.

```http
GET /api/patients/{id}/medical-records
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Patient ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20) |
| finalized | boolean | No | Filter by finalized status |
| from_date | date | No | Filter from date (YYYY-MM-DD) |
| to_date | date | No | Filter to date (YYYY-MM-DD) |
| icd10_code | string | No | Filter by ICD-10 code |

### Response Success (200)

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
      "objective": "TD: 120/80, HR: 88, RR: 20, T: 38.2°C. Keadaan umum baik, kesadaran compos mentis",
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
  "message": "You do not have permission to view medical records for this patient",
  "error": {
    "code": "MEDICAL_RECORD_ACCESS_DENIED",
    "details": {}
  }
}
```

## Data Types Reference

### Patient Gender

| Code | Description |
|------|-------------|
| L | Laki-laki (Male) |
| P | Perempuan (Female) |

### Blood Type

| Code | Description |
|------|-------------|
| A | A |
| B | B |
| AB | AB |
| O | O |

### Insurance Type

| Type | Description |
|------|-------------|
| bpjs | BPJS Kesehatan |
| umum | Umum (Self-pay) |
| asuransi | Asuransi Swasta |

### Marital Status

| Status | Description |
|--------|-------------|
| single | Belum Menikah |
| married | Menikah |
| divorced | Cerai |
| widowed | Janda/Duda |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `PATIENT_NOT_FOUND` | 404 | Patient with specified ID not found |
| `DUPLICATE_PATIENT` | 409 | Patient with this NIK already exists |
| `INVALID_NIK_FORMAT` | 422 | NIK must be exactly 16 digits |
| `IMMUTABLE_FIELD` | 403 | Attempted to modify a read-only field |
| `PATIENT_HAS_ACTIVE_VISITS` | 422 | Cannot delete patient with active/in-progress visits |
| `MEDICAL_RECORD_ACCESS_DENIED` | 403 | User lacks permission to view medical records |
| `VALIDATION_ERROR` | 422 | Request validation failed |
