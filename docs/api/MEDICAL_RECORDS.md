# Medical Records API

This document describes all endpoints for Electronic Medical Record (EMR) management in the SIMRS system, including SOAP notes, CPPT, assessments, and prescriptions.

## Table of Contents

- [List Medical Records](#list-medical-records)
- [Create Medical Record](#create-medical-record)
- [Get Medical Record](#get-medical-record)
- [Update Medical Record](#update-medical-record)
- [Finalize Medical Record](#finalize-medical-record)
- [CPPT Endpoints](#cppt-endpoints)
- [Assessment Endpoints](#assessment-endpoints)

---

## List Medical Records

Retrieve a paginated list of medical records.

```http
GET /api/medical-records
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
| per_page | integer | No | Items per page (default: 20) |
| patient_id | integer | No | Filter by patient ID |
| visit_id | integer | No | Filter by visit ID |
| doctor_id | integer | No | Filter by doctor ID |
| is_finalized | boolean | No | Filter by finalized status |
| icd10_code | string | No | Filter by ICD-10 code |
| from_date | date | No | Filter from date (YYYY-MM-DD) |
| to_date | date | No | Filter to date (YYYY-MM-DD) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 78,
      "record_number": "RM-20240115-0078",
      "patient": {
        "id": 1,
        "medical_record_number": "20240101-0001",
        "name": "John Doe",
        "gender": "L",
        "age": 34
      },
      "visit": {
        "id": 45,
        "visit_number": "RJ-20240115-0045",
        "visit_date": "2024-01-15"
      },
      "visit_date": "2024-01-15",
      "diagnosis_primary": "Tension Type Headache",
      "icd10_code": "G44.2",
      "is_finalized": true,
      "finalized_at": "2024-01-15T10:00:00Z",
      "finalized_by": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      },
      "created_at": "2024-01-15T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

---

## Create Medical Record

Create a new medical record for a visit.

```http
POST /api/medical-records
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
| visit_id | integer | Yes | Visit ID |
| subjective | string | Yes | Subjective data (S) - Patient complaints |
| objective | string | Yes | Objective data (O) - Physical examination |
| assessment | string | Yes | Assessment (A) - Diagnosis/impression |
| plan | string | Yes | Plan (P) - Treatment plan |
| diagnosis_primary | string | No | Primary diagnosis |
| diagnosis_secondary | string | No | Secondary diagnosis |
| icd10_code | string | No | ICD-10 diagnosis code |
| icd10_description | string | No | ICD-10 description |
| procedure_code | string | No | Procedure code |
| procedure_description | string | No | Procedure description |
| notes | string | No | Additional notes |

### Request Example

```json
{
  "visit_id": 45,
  "subjective": "Pasien mengeluh sakit kepala berdenyut di bagian frontalis sejak 2 hari yang lalu. Nyeri skala 6/10. Disertai mual tanpa muntah. Tidak ada riwayat trauma.",
  "objective": "Keadaan umum: Baik, Kesadaran: Compos mentis. TD: 120/80 mmHg, HR: 88x/menit, RR: 20x/menit, Suhu: 38.2°C. Pemeriksaan neurologis dalam batas normal. Tidak ada defisit neurologis.",
  "assessment": "Tension-type headache dengan fever",
  "plan": "1. Paracetamol 500mg 3x1 hari\n2. Istirahat cukup dan hindari stress\n3. Kompres hangat pada daerah nyeri\n4. Kontrol 3 hari atau segera jika memburuk",
  "diagnosis_primary": "Tension Type Headache",
  "diagnosis_secondary": "Common Cold",
  "icd10_code": "G44.2",
  "icd10_description": "Tension-type headache",
  "notes": "Pasien riwayat sering sakit kepala saat stress"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Medical record created successfully",
  "data": {
    "id": 78,
    "record_number": "RM-20240115-0078",
    "visit_id": 45,
    "patient_id": 1,
    "visit_date": "2024-01-15",
    "subjective": "Pasien mengeluh sakit kepala berdenyut...",
    "objective": "Keadaan umum: Baik...",
    "assessment": "Tension-type headache dengan fever",
    "plan": "1. Paracetamol 500mg 3x1 hari...",
    "diagnosis_primary": "Tension Type Headache",
    "diagnosis_secondary": "Common Cold",
    "icd10_code": "G44.2",
    "icd10_description": "Tension-type headache",
    "is_finalized": false,
    "created_at": "2024-01-15T08:30:00Z",
    "created_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    }
  }
}
```

### Response Error (409) - Medical Record Already Exists

```json
{
  "success": false,
  "message": "Medical record already exists for this visit",
  "error": {
    "code": "MEDICAL_RECORD_EXISTS",
    "details": {
      "existing_record_id": 77
    }
  }
}
```

---

## Get Medical Record

Retrieve detailed information about a specific medical record.

```http
GET /api/medical-records/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Medical Record ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| include | string | No | Related data to include (cppts, assessments, prescriptions) |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 78,
    "record_number": "RM-20240115-0078",
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "gender": "L",
      "age": 34,
      "address": "Jl. Merdeka No. 123, Jakarta",
      "phone": "08123456789"
    },
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "visit_date": "2024-01-15",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      }
    },
    "visit_date": "2024-01-15",
    "subjective": "Pasien mengeluh sakit kepala berdenyut di bagian frontalis...",
    "objective": "Keadaan umum: Baik, Kesadaran: Compos mentis...",
    "assessment": "Tension-type headache dengan fever",
    "plan": "1. Paracetamol 500mg 3x1 hari...",
    "diagnosis_primary": "Tension Type Headache",
    "diagnosis_secondary": "Common Cold",
    "icd10_code": "G44.2",
    "icd10_description": "Tension-type headache",
    "procedure_code": null,
    "procedure_description": null,
    "notes": "Pasien riwayat sering sakit kepala saat stress",
    "is_finalized": true,
    "finalized_at": "2024-01-15T10:00:00Z",
    "finalized_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "cppts": [
      {
        "id": 12,
        "cppt_date": "2024-01-15",
        "subjective": "Update: Nyeri berkurang",
        "objective": "TD: 118/78",
        "is_verified": true
      }
    ],
    "assessments": [
      {
        "id": 8,
        "assessment_type": "general",
        "assessment_date": "2024-01-15",
        "vital_signs": {
          "blood_pressure": "120/80",
          "heart_rate": 88,
          "temperature": 38.2
        }
      }
    ],
    "prescriptions": [
      {
        "id": 32,
        "prescription_number": "RX-20240115-0032",
        "status": "completed"
      }
    ],
    "soap_note": "S: Pasien mengeluh sakit kepala berdenyut...\nO: Keadaan umum: Baik...\nA: Tension-type headache dengan fever\nP: 1. Paracetamol 500mg 3x1 hari...",
    "created_at": "2024-01-15T08:30:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

---

## Update Medical Record

Update medical record information (only if not finalized).

```http
PUT /api/medical-records/{id}
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
| id | integer | Medical Record ID |

### Request Body

Same fields as Create Medical Record (all optional).

### Response Success (200)

```json
{
  "success": true,
  "message": "Medical record updated successfully",
  "data": {
    "id": 78,
    "record_number": "RM-20240115-0078",
    "updated_at": "2024-01-15T09:00:00Z"
  }
}
```

### Response Error (403) - Record Finalized

```json
{
  "success": false,
  "message": "Cannot modify finalized medical record",
  "error": {
    "code": "RECORD_FINALIZED",
    "details": {
      "finalized_at": "2024-01-15T10:00:00Z"
    }
  }
}
```

---

## Finalize Medical Record

Finalize a medical record (lock for editing).

```http
POST /api/medical-records/{id}/finalize
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
| id | integer | Medical Record ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| password | string | Yes | User password for verification |
| notes | string | No | Finalization notes |

### Request Example

```json
{
  "password": "yourpassword",
  "notes": "Finalized after complete examination"
}
```

### Response Success (200)

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

---

## CPPT Endpoints

### List CPPTs

```http
GET /api/medical-records/{medical_record_id}/cppts
```

### Create CPPT

```http
POST /api/medical-records/{medical_record_id}/cppts
```

#### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| cppt_date | date | Yes | CPPT date |
| cppt_time | time | No | CPPT time |
| subjective | string | Yes | Subjective data |
| objective | string | Yes | Objective data |
| assessment | string | Yes | Assessment |
| plan | string | Yes | Plan |
| instruction | string | No | Instructions |
| progress_notes | string | No | Progress notes |

#### Request Example

```json
{
  "cppt_date": "2024-01-15",
  "cppt_time": "14:00",
  "subjective": "Pasien melaporkan nyeri kepala berkurang setelah minum obat",
  "objective": "TD: 118/78, HR: 82, T: 37.5°C. Pasien tampak lebih nyaman.",
  "assessment": "Respons positif terhadap analgesik",
  "plan": "Lanjutkan terapi, observasi",
  "progress_notes": "Kondisi membaik"
}
```

#### Response Success (201)

```json
{
  "success": true,
  "message": "CPPT created successfully",
  "data": {
    "id": 12,
    "medical_record_id": 78,
    "cppt_date": "2024-01-15",
    "cppt_time": "14:00:00",
    "subjective": "Pasien melaporkan nyeri kepala berkurang...",
    "objective": "TD: 118/78, HR: 82...",
    "assessment": "Respons positif terhadap analgesik",
    "plan": "Lanjutkan terapi, observasi",
    "is_verified": false,
    "created_at": "2024-01-15T14:00:00Z",
    "created_by": {
      "id": 8,
      "name": "Nurse Amanda"
    }
  }
}
```

### Verify CPPT

```http
POST /api/cppts/{id}/verify
```

#### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| password | string | Yes | Doctor password for verification |

#### Response Success (200)

```json
{
  "success": true,
  "message": "CPPT verified successfully",
  "data": {
    "id": 12,
    "is_verified": true,
    "verified_at": "2024-01-15T15:00:00Z",
    "verified_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    }
  }
}
```

---

## Assessment Endpoints

### List Assessments

```http
GET /api/medical-records/{medical_record_id}/assessments
```

### Create Assessment

```http
POST /api/medical-records/{medical_record_id}/assessments
```

#### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| assessment_type | string | Yes | Type (general, triage, pre_op, etc) |
| assessment_date | date | Yes | Assessment date |
| chief_complaint | string | Yes | Chief complaint |
| history_of_illness | string | No | History of present illness |
| vital_signs | object | Yes | Vital signs data |
| physical_examination | object | No | Physical examination findings |
| laboratory_results | object | No | Lab results |
| radiology_results | object | No | Radiology results |
| assessment_summary | string | No | Summary assessment |
| plan_of_care | string | No | Care plan |
| triage_category | string | No | Triage category (if applicable) |
| notes | string | No | Additional notes |

#### Request Example

```json
{
  "assessment_type": "general",
  "assessment_date": "2024-01-15",
  "chief_complaint": "Sakit kepala dan demam",
  "history_of_illness": "Nyeri kepala berdenyut seit 2 hari",
  "vital_signs": {
    "blood_pressure": "120/80",
    "heart_rate": 88,
    "respiratory_rate": 20,
    "temperature": 38.2,
    "oxygen_saturation": 98,
    "weight_kg": 70,
    "height_cm": 170
  },
  "physical_examination": {
    "general_condition": "Baik",
    "consciousness": "Compos mentis",
    "head_neck": "Normal",
    "thorax": "Normal",
    "abdomen": "Normal",
    "extremities": "Normal"
  },
  "assessment_summary": "Tension headache with fever",
  "plan_of_care": "Analgesic and antipyretic therapy",
  "notes": "Pasien riwayat migrain"
}
```

#### Response Success (201)

```json
{
  "success": true,
  "message": "Assessment created successfully",
  "data": {
    "id": 8,
    "medical_record_id": 78,
    "assessment_type": "general",
    "assessment_date": "2024-01-15",
    "chief_complaint": "Sakit kepala dan demam",
    "vital_signs": {
      "blood_pressure": "120/80",
      "heart_rate": 88,
      "respiratory_rate": 20,
      "temperature": 38.2,
      "oxygen_saturation": 98,
      "weight_kg": 70,
      "height_cm": 170
    },
    "bmi": 24.22,
    "blood_pressure_status": "normal",
    "created_at": "2024-01-15T08:30:00Z",
    "assessed_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    }
  }
}
```

## ICD-10 Codes

Common ICD-10 codes for reference:

| Code | Description | Category |
|------|-------------|----------|
| G44.2 | Tension-type headache | Neurological |
| J06.9 | Acute upper respiratory infection | Respiratory |
| K29.7 | Gastritis, unspecified | Gastrointestinal |
| I10 | Essential (primary) hypertension | Cardiovascular |
| E11.9 | Type 2 diabetes mellitus | Endocrine |
| M25.5 | Pain in joint | Musculoskeletal |
| N39.0 | Urinary tract infection | Genitourinary |
| R50.9 | Fever, unspecified | General |

## Assessment Types

| Type | Description |
|------|-------------|
| `general` | General medical assessment |
| `triage` | Emergency triage assessment |
| `pre_op` | Pre-operative assessment |
| `post_op` | Post-operative assessment |
| `daily` | Daily ward assessment |
| `discharge` | Discharge assessment |

## Triage Categories

| Category | Color | Description |
|----------|-------|-------------|
| `resuscitation` | Red | Immediate life threat |
| `emergency` | Yellow | Urgent, potential threat |
| `urgent` | Green | Requires care soon |
| `less_urgent` | Blue | Non-urgent |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `MEDICAL_RECORD_NOT_FOUND` | 404 | Medical record not found |
| `MEDICAL_RECORD_EXISTS` | 409 | Record already exists for this visit |
| `RECORD_FINALIZED` | 403 | Cannot modify finalized record |
| `CPPT_NOT_FOUND` | 404 | CPPT not found |
| `ASSESSMENT_NOT_FOUND` | 404 | Assessment not found |
| `INVALID_ICD10_CODE` | 422 | Invalid ICD-10 code format |
| `INSUFFICIENT_PERMISSIONS` | 403 | User cannot perform this action |
