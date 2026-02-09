# Surgery API

This document describes all endpoints for surgery/operating room (OK) management in the SIMRS system.

## Table of Contents

- [List Surgeries](#list-surgeries)
- [Schedule Surgery](#schedule-surgery)
- [Get Surgery Details](#get-surgery-details)
- [Start Surgery](#start-surgery)
- [Complete Surgery](#complete-surgery)
- [Cancel Surgery](#cancel-surgery)

---

## List Surgeries

Retrieve a list of surgeries with optional filtering.

```http
GET /api/surgeries
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status (dijadwalkan, persiapan, berlangsung, selesai, batal) |
| patient_id | integer | No | Filter by patient ID |
| surgery_type | string | No | Filter by type (elektif, urgent, cito) |
| date_from | date | No | Filter from date (YYYY-MM-DD) |
| date_to | date | No | Filter to date (YYYY-MM-DD) |
| operating_room_id | integer | No | Filter by operating room ID |
| surgeon_id | integer | No | Filter by surgeon ID |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 89,
      "surgery_number": "OK-20240115-0089",
      "surgery_type": "elektif",
      "surgery_type_display": "Elektif",
      "patient": {
        "id": 45,
        "name": "Jane Smith",
        "medical_record_number": "20240105-0045",
        "gender": "P",
        "age": 45,
        "blood_type": "A"
      },
      "schedule": {
        "date": "2024-01-15",
        "start_time": "08:00:00",
        "estimated_end_time": "10:00:00",
        "estimated_duration_minutes": 120
      },
      "operating_room": {
        "id": 1,
        "name": "OK 1",
        "code": "OK-01"
      },
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "procedure": {
        "primary": "Appendectomy",
        "icd9_code": "47.0",
        "icd9_description": "Laparoscopic appendectomy"
      },
      "status": "dijadwalkan",
      "inpatient": {
        "id": 78,
        "inpatient_number": "RI-20240114-0078",
        "room": "Melati VIP (301)"
      },
      "created_at": "2024-01-14T16:00:00Z",
      "updated_at": "2024-01-14T16:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 85,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/surgeries?page=1",
    "last": "/api/surgeries?page=5",
    "prev": null,
    "next": "/api/surgeries?page=2"
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
  "message": "You do not have permission to view surgeries",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Schedule Surgery

Schedule a new surgery.

```http
POST /api/surgeries
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
| surgery_type | string | Yes | Surgery type (elektif, urgent, cito) |
| operating_room_id | integer | Yes | Operating room ID |
| schedule_date | date | Yes | Scheduled date (YYYY-MM-DD) |
| schedule_start_time | time | Yes | Scheduled start time (HH:MM:SS) |
| estimated_duration_minutes | integer | Yes | Estimated duration in minutes |
| main_surgeon_id | integer | Yes | Main surgeon ID |
| assistant_surgeon_ids | array | No | List of assistant surgeon IDs |
| anesthesiologist_id | integer | Yes | Anesthesiologist ID |
| procedure_icd9_code | string | Yes | ICD-9-CM procedure code |
| procedure_name | string | Yes | Procedure name |
| diagnosis_pre | string | Yes | Pre-operative diagnosis |
| icd10_code | string | No | ICD-10 diagnosis code |
| anesthesia_type | string | Yes | Anesthesia type |
| inpatient_id | integer | No | Inpatient ID (if patient is admitted) |
| special_equipment | string | No | Special equipment needed |
| implants_needed | array | No | List of implants needed |
| blood_reserve_units | integer | No | Blood units to reserve |
| blood_type_needed | string | No | Blood type needed |
| fasting_required | boolean | No | Whether fasting is required - default: true |
| fasting_hours | integer | No | Fasting duration in hours - default: 8 |
| notes | string | No | Additional notes |
| requested_by | integer | Yes | Doctor ID requesting surgery |

### Surgery Types

| Type | Description |
|------|-------------|
| elektif | Elective - scheduled in advance |
| urgent | Urgent - within 24 hours |
| cito | Emergency - immediate |

### Anesthesia Types

| Type | Description |
|------|-------------|
| general | General anesthesia |
| spinal | Spinal anesthesia |
| epidural | Epidural anesthesia |
| local | Local anesthesia |
| regional | Regional block |

### Request Example

```json
{
  "patient_id": 45,
  "surgery_type": "elektif",
  "operating_room_id": 1,
  "schedule_date": "2024-01-15",
  "schedule_start_time": "08:00:00",
  "estimated_duration_minutes": 120,
  "main_surgeon_id": 12,
  "assistant_surgeon_ids": [13],
  "anesthesiologist_id": 15,
  "procedure_icd9_code": "47.0",
  "procedure_name": "Laparoscopic Appendectomy",
  "diagnosis_pre": "Acute Appendicitis",
  "icd10_code": "K35",
  "anesthesia_type": "general",
  "inpatient_id": 78,
  "special_equipment": "Laparoscopy set",
  "implants_needed": [],
  "blood_reserve_units": 2,
  "blood_type_needed": "A+",
  "fasting_required": true,
  "fasting_hours": 8,
  "notes": "Pasien sudah puasa sejak tengah malam",
  "requested_by": 8
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Surgery scheduled successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "surgery_type": "elektif",
    "surgery_type_display": "Elektif",
    "patient": {
      "id": 45,
      "name": "Jane Smith",
      "medical_record_number": "20240105-0045",
      "gender": "P",
      "age": 45,
      "blood_type": "A"
    },
    "schedule": {
      "date": "2024-01-15",
      "start_time": "08:00:00",
      "estimated_end_time": "10:00:00",
      "estimated_duration_minutes": 120
    },
    "operating_room": {
      "id": 1,
      "name": "OK 1",
      "code": "OK-01"
    },
    "team": {
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "assistant_surgeons": [
        {
          "id": 13,
          "name": "Dr. Assistant Surgeon",
          "specialization": "Bedah Umum"
        }
      ],
      "anesthesiologist": {
        "id": 15,
        "name": "Dr. Anesthesiologist",
        "specialization": "Anestesi"
      },
      "nurses": []
    },
    "procedure": {
      "primary": "Laparoscopic Appendectomy",
      "icd9_code": "47.0",
      "icd9_description": "Laparoscopic appendectomy"
    },
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "icd10_code": "K35",
      "icd10_description": "Acute appendicitis"
    },
    "anesthesia": {
      "type": "general",
      "type_display": "General"
    },
    "preparation": {
      "fasting_required": true,
      "fasting_hours": 8,
      "blood_reserved_units": 2,
      "blood_type": "A+"
    },
    "status": "dijadwalkan",
    "status_display": "Dijadwalkan",
    "inpatient": {
      "id": 78,
      "inpatient_number": "RI-20240114-0078"
    },
    "created_at": "2024-01-14T16:30:00Z"
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

### Response Error (422) - Room Not Available

```json
{
  "success": false,
  "message": "Operating room not available at selected time",
  "error": {
    "code": "ROOM_NOT_AVAILABLE",
    "details": {
      "operating_room_id": 1,
      "room_name": "OK 1",
      "conflicting_surgery": "OK-20240115-0089",
      "time": "2024-01-15 08:00"
    }
  }
}
```

### Response Error (422) - Surgeon Not Available

```json
{
  "success": false,
  "message": "Surgeon not available at selected time",
  "error": {
    "code": "SURGEON_NOT_AVAILABLE",
    "details": {
      "surgeon_id": 12,
      "surgeon_name": "Dr. Surgeon Specialist",
      "conflicting_surgery": "OK-20240115-0085"
    }
  }
}
```

---

## Get Surgery Details

Retrieve detailed information about a specific surgery.

```http
GET /api/surgeries/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Surgery ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "surgery_type": "elektif",
    "surgery_type_display": "Elektif",
    "patient": {
      "id": 45,
      "name": "Jane Smith",
      "medical_record_number": "20240105-0045",
      "gender": "P",
      "age": 45,
      "date_of_birth": "1979-03-15",
      "blood_type": "A",
      "weight_kg": 65,
      "height_cm": 165,
      "allergies": ["Penicillin"]
    },
    "schedule": {
      "date": "2024-01-15",
      "start_time": "08:00:00",
      "estimated_end_time": "10:00:00",
      "estimated_duration_minutes": 120
    },
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z",
      "surgery_end": "2024-01-15T09:45:00Z",
      "patient_out": "2024-01-15T10:00:00Z"
    },
    "operating_room": {
      "id": 1,
      "name": "OK 1",
      "code": "OK-01"
    },
    "team": {
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "assistant_surgeons": [
        {
          "id": 13,
          "name": "Dr. Assistant Surgeon",
          "specialization": "Bedah Umum"
        }
      ],
      "anesthesiologist": {
        "id": 15,
        "name": "Dr. Anesthesiologist",
        "specialization": "Anestesi"
      },
      "nurses": [
        {
          "id": 20,
          "name": "Suster A",
          "role": "Instrument"
        },
        {
          "id": 21,
          "name": "Suster B",
          "role": "Sirkuler"
        }
      ]
    },
    "procedure": {
      "primary": "Laparoscopic Appendectomy",
      "icd9_code": "47.0",
      "icd9_description": "Laparoscopic appendectomy"
    },
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "post": "Acute Suppurative Appendicitis",
      "icd10_code": "K35",
      "icd10_description": "Acute appendicitis"
    },
    "anesthesia": {
      "type": "general",
      "type_display": "General",
      "notes": "Induksi dengan Propofol, maintenance dengan Sevoflurane"
    },
    "safety_checklist": {
      "sign_in": {
        "completed": true,
        "completed_at": "2024-01-15T08:10:00Z",
        "identity_verified": true,
        "procedure_verified": true,
        "consent_signed": true,
        "site_marked": true,
        "allergy_checked": true
      },
      "time_out": {
        "completed": true,
        "completed_at": "2024-01-15T08:15:00Z",
        "team_introduced": true,
        "procedure_confirmed": true,
        "anticipated_problems": "None"
      },
      "sign_out": {
        "completed": true,
        "completed_at": "2024-01-15T09:45:00Z",
        "instrument_count_correct": true,
        "specimen_labeled": true,
        "equipment_problems": false
      }
    },
    "implants_used": [],
    "specimens": [
      {
        "type": "Appendix",
        "sent_to_pathology": true,
        "pathology_number": "PATH-20240115-0045"
      }
    ],
    "complications": null,
    "blood_transfusion": {
      "units_used": 0,
      "blood_loss_ml": 50
    },
    "status": "selesai",
    "status_display": "Selesai",
    "report": "Appendiks yang mengalami inflamasi akut dengan eksudat purulen dihilangkan dengan teknik laparoskopi. Tidak ada komplikasi intraoperatif. Luka ditutup primer.",
    "inpatient": {
      "id": 78,
      "inpatient_number": "RI-20240114-0078",
      "room": "Melati VIP (301)"
    },
    "timeline": [
      {
        "timestamp": "2024-01-14T16:30:00Z",
        "event": "Surgery scheduled",
        "user": "Dr. Surgeon Specialist"
      },
      {
        "timestamp": "2024-01-15T07:45:00Z",
        "event": "Patient entered OR",
        "user": "Suster A"
      },
      {
        "timestamp": "2024-01-15T08:15:00Z",
        "event": "Surgery started",
        "user": "Dr. Surgeon Specialist"
      },
      {
        "timestamp": "2024-01-15T09:45:00Z",
        "event": "Surgery completed",
        "user": "Dr. Surgeon Specialist"
      }
    ],
    "created_at": "2024-01-14T16:30:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
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

### Response Error (404)

```json
{
  "success": false,
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

---

## Start Surgery

Mark surgery as started/in progress.

```http
PUT /api/surgeries/{id}/start
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
| id | integer | Surgery ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_in_at | datetime | No | Patient entered OR time (default: current time) |
| anesthesia_start_at | datetime | No | Anesthesia start time |
| surgery_start_at | datetime | No | Surgery incision time |
| started_by | integer | Yes | User ID starting surgery |
| safety_checklist_sign_in | object | Yes | Sign-in checklist |
| safety_checklist_sign_in.identity_verified | boolean | Yes | Patient identity verified |
| safety_checklist_sign_in.procedure_verified | boolean | Yes | Procedure verified |
| safety_checklist_sign_in.consent_signed | boolean | Yes | Consent form signed |
| safety_checklist_sign_in.site_marked | boolean | Yes | Surgical site marked |
| safety_checklist_sign_in.allergy_checked | boolean | Yes | Allergies checked |

### Request Example

```json
{
  "patient_in_at": "2024-01-15T07:45:00Z",
  "anesthesia_start_at": "2024-01-15T08:00:00Z",
  "surgery_start_at": "2024-01-15T08:15:00Z",
  "started_by": 12,
  "safety_checklist_sign_in": {
    "identity_verified": true,
    "procedure_verified": true,
    "consent_signed": true,
    "site_marked": true,
    "allergy_checked": true
  }
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Surgery started successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "berlangsung",
    "status_display": "Berlangsung",
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z"
    },
    "safety_checklist": {
      "sign_in": {
        "completed": true,
        "completed_at": "2024-01-15T08:10:00Z",
        "identity_verified": true,
        "procedure_verified": true,
        "consent_signed": true,
        "site_marked": true,
        "allergy_checked": true
      }
    },
    "started_by": {
      "id": 12,
      "name": "Dr. Surgeon Specialist"
    },
    "started_at": "2024-01-15T08:15:00Z"
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

### Response Error (404)

```json
{
  "success": false,
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot start surgery with status: selesai",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["dijadwalkan", "persiapan"]
    }
  }
}
```

---

## Complete Surgery

Mark surgery as completed.

```http
PUT /api/surgeries/{id}/complete
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
| id | integer | Surgery ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| surgery_end_at | datetime | No | Surgery end time (default: current time) |
| patient_out_at | datetime | No | Patient exit time |
| diagnosis_post | string | Yes | Post-operative diagnosis |
| procedure_description | string | Yes | Detailed procedure description |
| complications | string | No | Any complications during surgery |
| blood_loss_ml | integer | No | Estimated blood loss in ml |
| blood_transfusion_units | integer | No | Blood units transfused |
| implants_used | array | No | List of implants used |
| specimens | array | No | List of specimens taken |
| safety_checklist_sign_out | object | Yes | Sign-out checklist |
| safety_checklist_sign_out.instrument_count_correct | boolean | Yes | Instrument count correct |
| safety_checklist_sign_out.specimen_labeled | boolean | Yes | Specimens labeled |
| safety_checklist_sign_out.equipment_problems | boolean | Yes | Any equipment problems |
| completed_by | integer | Yes | User ID completing surgery |
| report | string | Yes | Surgery report |

### Request Example

```json
{
  "surgery_end_at": "2024-01-15T09:45:00Z",
  "patient_out_at": "2024-01-15T10:00:00Z",
  "diagnosis_post": "Acute Suppurative Appendicitis",
  "procedure_description": "Laparoscopic appendectomy dengan 3 port. Appendiks dihilangkan menggunakan stapler endoscopic. Abdomen diirrigation dan hemostasis tercapai.",
  "complications": null,
  "blood_loss_ml": 50,
  "blood_transfusion_units": 0,
  "implants_used": [],
  "specimens": [
    {
      "type": "Appendix",
      "sent_to_pathology": true
    }
  ],
  "safety_checklist_sign_out": {
    "instrument_count_correct": true,
    "specimen_labeled": true,
    "equipment_problems": false
  },
  "completed_by": 12,
  "report": "Appendiks yang mengalami inflamasi akut dengan eksudat purulen dihilangkan dengan teknik laparoskopi. Tidak ada komplikasi intraoperatif. Luka ditutup primer."
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Surgery completed successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "selesai",
    "status_display": "Selesai",
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z",
      "surgery_end": "2024-01-15T09:45:00Z",
      "patient_out": "2024-01-15T10:00:00Z"
    },
    "actual_duration_minutes": 90,
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "post": "Acute Suppurative Appendicitis"
    },
    "blood_transfusion": {
      "units_used": 0,
      "blood_loss_ml": 50
    },
    "specimens": [
      {
        "type": "Appendix",
        "sent_to_pathology": true,
        "pathology_number": "PATH-20240115-0045"
      }
    ],
    "safety_checklist": {
      "sign_out": {
        "completed": true,
        "completed_at": "2024-01-15T09:45:00Z",
        "instrument_count_correct": true,
        "specimen_labeled": true,
        "equipment_problems": false
      }
    },
    "completed_by": {
      "id": 12,
      "name": "Dr. Surgeon Specialist"
    },
    "completed_at": "2024-01-15T10:00:00Z"
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

### Response Error (404)

```json
{
  "success": false,
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot complete surgery with status: dijadwalkan",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "dijadwalkan",
      "allowed_statuses": ["berlangsung"]
    }
  }
}
```

---

## Cancel Surgery

Cancel a scheduled surgery.

```http
PUT /api/surgeries/{id}/cancel
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
| id | integer | Surgery ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| cancel_reason | string | Yes | Reason for cancellation |
| cancel_reason_detail | string | No | Detailed explanation |
| cancelled_by | integer | Yes | User ID cancelling surgery |

### Cancel Reasons

| Reason | Description |
|--------|-------------|
| kondisi_pasien | Patient condition |
| permintaan_pasien | Patient request |
| alasan_medis | Medical reason |
| kamar_tidak_tersedia | Room not available |
| dokter_tidak_tersedia | Doctor not available |
| jadwal_ulang | Rescheduled |
| administrasi | Administrative |

### Request Example

```json
{
  "cancel_reason": "kondisi_pasien",
  "cancel_reason_detail": "Pasien mengalami demam tinggi, operasi ditunda hingga kondisi stabil",
  "cancelled_by": 12
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Surgery cancelled successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "batal",
    "status_display": "Dibatalkan",
    "cancellation": {
      "reason": "kondisi_pasien",
      "reason_display": "Kondisi Pasien",
      "detail": "Pasien mengalami demam tinggi, operasi ditunda hingga kondisi stabil",
      "cancelled_at": "2024-01-14T20:00:00Z",
      "cancelled_by": {
        "id": 12,
        "name": "Dr. Surgeon Specialist"
      }
    },
    "reschedule_info": {
      "can_reschedule": true,
      "new_schedule_link": "/api/surgeries?reschedule_from=90"
    }
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

### Response Error (404)

```json
{
  "success": false,
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot cancel surgery with status: selesai",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["dijadwalkan", "persiapan"]
    }
  }
}
```

## Data Types Reference

### Surgery Type

| Type | Description |
|------|-------------|
| elektif | Elective - scheduled in advance |
| urgent | Urgent - within 24 hours |
| cito | Emergency - immediate |

### Surgery Status

| Status | Description |
|--------|-------------|
| dijadwalkan | Scheduled |
| persiapan | Preparation |
| berlangsung | In progress |
| selesai | Completed |
| batal | Cancelled |

### Anesthesia Type

| Type | Description |
|------|-------------|
| general | General anesthesia |
| spinal | Spinal anesthesia |
| epidural | Epidural anesthesia |
| local | Local anesthesia |
| regional | Regional block |

### Cancel Reasons

| Reason | Description |
|--------|-------------|
| kondisi_pasien | Patient condition |
| permintaan_pasien | Patient request |
| alasan_medis | Medical reason |
| kamar_tidak_tersedia | Room not available |
| dokter_tidak_tersedia | Doctor not available |
| jadwal_ulang | Rescheduled |
| administrasi | Administrative |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `SURGERY_NOT_FOUND` | 404 | Surgery with specified ID not found |
| `ROOM_NOT_AVAILABLE` | 422 | Operating room not available at selected time |
| `SURGEON_NOT_AVAILABLE` | 422 | Surgeon not available at selected time |
| `INVALID_SURGERY_STATUS` | 422 | Cannot perform action with current status |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission |
| `VALIDATION_ERROR` | 422 | Request validation failed |
