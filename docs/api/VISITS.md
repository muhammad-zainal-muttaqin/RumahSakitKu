# Visits API

This document describes all endpoints for visit (kunjungan) management in the SIMRS system.

## Table of Contents

- [List Visits](#list-visits)
- [Create Visit](#create-visit)
- [Get Visit](#get-visit)
- [Update Visit](#update-visit)
- [Delete Visit](#delete-visit)
- [Update Visit Status](#update-visit-status)
- [Get Queue Status](#get-queue-status)
- [Check-in Patient](#check-in-patient)
- [Check-out Patient](#check-out-patient)

---

## List Visits

Retrieve a paginated list of all visits.

```http
GET /api/visits
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
| polyclinic_id | integer | No | Filter by polyclinic ID |
| doctor_id | integer | No | Filter by doctor ID |
| visit_type | string | No | Filter by visit type (rawat_jalan, rawat_inap, igd, mcu) |
| status | string | No | Filter by status (pendaftaran, menunggu, proses, selesai, batal) |
| from_date | date | No | Filter from date (YYYY-MM-DD) |
| to_date | date | No | Filter to date (YYYY-MM-DD) |
| is_completed | boolean | No | Filter by completion status |
| priority | string | No | Filter by priority (normal, urgent, emergency) |

### Response Success (200)

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

## Create Visit

Register a new patient visit.

```http
POST /api/visits
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
| patient_id | integer | Yes | Patient ID |
| polyclinic_id | integer | Yes | Polyclinic ID |
| doctor_id | integer | No | Doctor ID (if known) |
| visit_date | date | Yes | Visit date (YYYY-MM-DD) |
| visit_type | string | Yes | Visit type (rawat_jalan, rawat_inap, igd, mcu) |
| registration_type | string | Yes | Registration type (baru, lama, rujukan) |
| priority | string | No | Priority (normal, urgent, emergency) - default: normal |
| complaint | string | Yes | Chief complaint |
| referral_from | string | No | Referral from (if applicable) |
| referral_number | string | No | Referral number (if applicable) |
| notes | string | No | Additional notes |

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

### Response Success (201)

```json
{
  "success": true,
  "message": "Visit created successfully",
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

### Response Error (422) - Patient Already Has Active Visit

```json
{
  "success": false,
  "message": "Patient already has an active visit",
  "error": {
    "code": "ACTIVE_VISIT_EXISTS",
    "details": {
      "active_visit": {
        "id": 44,
        "visit_number": "RJ-20240115-0044",
        "status": "menunggu",
        "polyclinic": "Poli Umum"
      }
    }
  }
}
```

---

## Get Visit

Retrieve detailed information about a specific visit.

```http
GET /api/visits/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Visit ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| include | string | No | Related data to include (medical_record, prescription, invoice, queue) |

### Response Success (200)

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

### Response Error (404)

```json
{
  "success": false,
  "message": "Visit not found",
  "error": {
    "code": "VISIT_NOT_FOUND",
    "details": {}
  }
}
```

---

## Update Visit

Update visit information.

```http
PUT /api/visits/{id}
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
| id | integer | Visit ID |

### Request Body

| Parameter | Type | Description |
|-----------|------|-------------|
| polyclinic_id | integer | Polyclinic ID |
| doctor_id | integer | Doctor ID |
| priority | string | Priority level |
| complaint | string | Chief complaint |
| notes | string | Additional notes |

### Request Example

```json
{
  "doctor_id": 6,
  "priority": "urgent",
  "notes": "Pasien memerlukan perhatian khusus"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Visit updated successfully",
  "data": {
    "id": 45,
    "visit_number": "RJ-20240115-0045",
    "doctor": {
      "id": 6,
      "name": "Dr. Michael Chen"
    },
    "priority": "urgent",
    "notes": "Pasien memerlukan perhatian khusus",
    "updated_at": "2024-01-15T08:05:00Z"
  }
}
```

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

## Delete Visit

Cancel/delete a visit.

```http
DELETE /api/visits/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Visit ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| reason | string | Yes | Reason for cancellation |

### Response Success (200)

```json
{
  "success": true,
  "message": "Visit cancelled successfully"
}
```

### Response Error (422) - Visit Already Completed

```json
{
  "success": false,
  "message": "Cannot cancel completed visit",
  "error": {
    "code": "VISIT_ALREADY_COMPLETED",
    "details": {}
  }
}
```

---

## Update Visit Status

Update the status of a visit.

```http
PATCH /api/visits/{id}/status
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
| id | integer | Visit ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | Yes | New status (pendaftaran, menunggu, proses, selesai, batal) |
| notes | string | No | Status change notes |

### Request Example

```json
{
  "status": "proses",
  "notes": "Pasien masuk ruangan pemeriksaan"
}
```

### Response Success (200)

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

### Response Error (400) - Invalid Status Transition

```json
{
  "success": false,
  "message": "Invalid status transition from 'selesai' to 'proses'",
  "error": {
    "code": "INVALID_STATUS_TRANSITION",
    "details": {
      "allowed_transitions": []
    }
  }
}
```

---

## Get Queue Status

Get current queue status for a polyclinic.

```http
GET /api/visits/queue/status
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| polyclinic_id | integer | No | Filter by polyclinic |
| date | date | No | Date (default: today) |

### Response Success (200)

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

## Check-in Patient

Check-in a patient for their visit.

```http
POST /api/visits/{id}/check-in
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
| id | integer | Visit ID |

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient checked in successfully",
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

## Check-out Patient

Check-out a patient after their visit is complete.

```http
POST /api/visits/{id}/check-out
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
| id | integer | Visit ID |

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient checked out successfully",
  "data": {
    "id": 45,
    "status": "selesai",
    "is_completed": true,
    "check_out_at": "2024-01-15T10:15:00Z",
    "duration_minutes": 105
  }
}
```

## Visit Types

| Type | Description |
|------|-------------|
| `rawat_jalan` | Rawat Jalan (Outpatient) |
| `rawat_inap` | Rawat Inap (Inpatient) |
| `igd` | IGD (Emergency Department) |
| `mcu` | Medical Check Up |

## Visit Statuses

| Status | Description | Allowed Transitions |
|--------|-------------|---------------------|
| `pendaftaran` | Registration | menunggu, batal |
| `menunggu` | Waiting | proses, batal |
| `proses` | In Progress | selesai, batal |
| `selesai` | Completed | - |
| `batal` | Cancelled | - |

## Priority Levels

| Priority | Description | Color |
|----------|-------------|-------|
| `normal` | Normal | Green |
| `urgent` | Urgent | Yellow |
| `emergency` | Emergency/UGD | Red |

## Registration Types

| Type | Description |
|------|-------------|
| `baru` | New patient (first visit) |
| `lama` | Existing patient |
| `rujukan` | Referred from another facility |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VISIT_NOT_FOUND` | 404 | Visit with specified ID not found |
| `ACTIVE_VISIT_EXISTS` | 422 | Patient already has an active visit |
| `VISIT_COMPLETED` | 422 | Cannot modify completed visit |
| `VISIT_ALREADY_COMPLETED` | 422 | Visit is already completed |
| `INVALID_STATUS_TRANSITION` | 400 | Status change not allowed |
| `PATIENT_NOT_FOUND` | 404 | Patient ID not found |
| `POLYCLINIC_NOT_FOUND` | 404 | Polyclinic ID not found |
| `DOCTOR_NOT_FOUND` | 404 | Doctor ID not found |
| `INSUFFICIENT_PERMISSIONS` | 403 | User cannot perform this action |
