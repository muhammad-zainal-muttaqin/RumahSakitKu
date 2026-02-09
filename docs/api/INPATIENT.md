# Inpatient API

This document describes all endpoints for inpatient (rawat inap) management in the SIMRS system.

## Table of Contents

- [List Inpatients](#list-inpatients)
- [Admit Patient](#admit-patient)
- [Transfer Room/Bed](#transfer-roombed)
- [Discharge Patient](#discharge-patient)
- [Get Patient Bill](#get-patient-bill)

---

## List Inpatients

Retrieve a list of all inpatients with optional filtering.

```http
GET /api/inpatients
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status (aktif, dipindahkan, pulang, dirujuk, meninggal) |
| room_id | integer | No | Filter by room ID |
| doctor_id | integer | No | Filter by doctor ID |
| admission_date_from | date | No | Filter by admission from date (YYYY-MM-DD) |
| admission_date_to | date | No | Filter by admission to date (YYYY-MM-DD) |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 125,
      "inpatient_number": "RI-20240115-0125",
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001",
        "nik": "1234567890123456",
        "gender": "L",
        "age": 34,
        "phone": "08123456789"
      },
      "admission": {
        "date": "2024-01-10",
        "time": "14:30:00",
        "type": "igd",
        "referring_doctor": null
      },
      "current_location": {
        "room": {
          "id": 1,
          "room_number": "301",
          "room_name": "Melati VIP",
          "room_class": "vip",
          "room_class_display": "VIP"
        },
        "bed": {
          "id": 1,
          "bed_number": "301-A"
        },
        "floor": 3,
        "building": "Gedung A"
      },
      "attending_doctor": {
        "id": 8,
        "name": "Dr. Michael Chen",
        "specialization": "Penyakit Dalam"
      },
      "diagnosis": {
        "primary": "Demam Berdarah Dengue",
        "secondary": "Dehidrasi",
        "icd10_code": "A90",
        "icd10_description": "Dengue fever [classical dengue]"
      },
      "status": "aktif",
      "length_of_stay_days": 5,
      "estimated_discharge_date": "2024-01-15",
      "visit_references": [
        {
          "visit_type": "igd",
          "visit_number": "IGD-20240110-0089",
          "visit_date": "2024-01-10"
        }
      ],
      "insurance": {
        "type": "bpjs",
        "number": "0001234567890",
        "sep_number": "0123456789012"
      },
      "created_at": "2024-01-10T14:30:00Z",
      "updated_at": "2024-01-14T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 8,
    "per_page": 20,
    "total": 155,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/inpatients?page=1",
    "last": "/api/inpatients?page=8",
    "prev": null,
    "next": "/api/inpatients?page=2"
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
  "message": "You do not have permission to view inpatients",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Admit Patient

Admit a new patient for inpatient care.

```http
POST /api/inpatients/admit
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
| bed_id | integer | Yes | Bed ID to assign |
| doctor_id | integer | Yes | Attending doctor ID |
| admission_date | date | No | Admission date (default: today) |
| admission_time | time | No | Admission time (default: current time) |
| admission_type | string | Yes | Admission type (igd, poliklinik, rujukan, rawat_jalan) |
| referring_doctor | string | No | Referring doctor name (if applicable) |
| referral_letter | string | No | Referral letter number |
| primary_diagnosis | string | Yes | Primary diagnosis |
| secondary_diagnosis | string | No | Secondary diagnosis |
| icd10_code | string | No | ICD-10 diagnosis code |
| complaint | string | Yes | Patient's chief complaint |
| history_of_illness | string | No | History of present illness |
| visit_id | integer | No | Reference visit ID (if from IGD/poli) |
| companion_name | string | No | Companion name |
| companion_relation | string | No | Companion relationship |
| companion_phone | string | No | Companion phone |
| estimated_stay_days | integer | No | Estimated length of stay |
| deposit_amount | decimal | No | Initial deposit amount |

### Request Example

```json
{
  "patient_id": 23,
  "bed_id": 2,
  "doctor_id": 8,
  "admission_type": "igd",
  "primary_diagnosis": "Demam Berdarah Dengue",
  "secondary_diagnosis": "Dehidrasi",
  "icd10_code": "A90",
  "complaint": "Demam tinggi sejak 3 hari, muntah, nyeri otot",
  "history_of_illness": "Pasien datang dengan keluhan demam tinggi sejak 3 hari yang lalu, muntah 4x, perdarahan gusi",
  "visit_id": 89,
  "companion_name": "Jane Doe",
  "companion_relation": "Istri",
  "companion_phone": "08198765432",
  "estimated_stay_days": 5,
  "deposit_amount": 5000000
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Patient admitted successfully",
  "data": {
    "id": 126,
    "inpatient_number": "RI-20240115-0126",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "admission": {
      "date": "2024-01-15",
      "time": "10:30:00",
      "type": "igd",
      "referring_doctor": null
    },
    "location": {
      "room": {
        "id": 2,
        "room_number": "201",
        "room_name": "Mawar Kelas I",
        "room_class": "i"
      },
      "bed": {
        "id": 2,
        "bed_number": "201-A"
      }
    },
    "attending_doctor": {
      "id": 8,
      "name": "Dr. Michael Chen",
      "specialization": "Penyakit Dalam"
    },
    "diagnosis": {
      "primary": "Demam Berdarah Dengue",
      "secondary": "Dehidrasi",
      "icd10_code": "A90"
    },
    "status": "aktif",
    "length_of_stay_days": 0,
    "estimated_discharge_date": "2024-01-20",
    "created_at": "2024-01-15T10:30:00Z"
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

### Response Error (422) - Bed Not Available

```json
{
  "success": false,
  "message": "Selected bed is not available",
  "error": {
    "code": "BED_NOT_AVAILABLE",
    "details": {
      "bed_id": 2,
      "bed_number": "201-A",
      "current_status": "terisi"
    }
  }
}
```

### Response Error (422) - Patient Already Inpatient

```json
{
  "success": false,
  "message": "Patient already has active inpatient admission",
  "error": {
    "code": "ACTIVE_INPATIENT_EXISTS",
    "details": {
      "current_admission_id": 125,
      "inpatient_number": "RI-20240110-0125",
      "admission_date": "2024-01-10"
    }
  }
}
```

---

## Transfer Room/Bed

Transfer an inpatient to another room or bed.

```http
POST /api/inpatients/{id}/transfer
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
| id | integer | Inpatient ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| bed_id | integer | Yes | New bed ID |
| transfer_reason | string | Yes | Reason for transfer |
| transfer_reason_detail | string | No | Detailed explanation |
| approved_by | integer | Yes | Doctor ID approving the transfer |
| notes | string | No | Additional notes |

### Transfer Reason Options

| Reason | Description |
|--------|-------------|
| naik_kelas | Upgrade room class |
| turun_kelas | Downgrade room class |
| isolasi | Move to isolation room |
| kondisi_kritis | Critical condition requiring ICU |
| permintaan_pasien | Patient request |
| kamar_penuh | Room full |
| perbaikan | Room under maintenance |
| kondisi_medis | Medical condition requirement |

### Request Example

```json
{
  "bed_id": 15,
  "transfer_reason": "naik_kelas",
  "transfer_reason_detail": "Pasien meminta upgrade ke kelas VIP",
  "approved_by": 8,
  "notes": "Upgrade disetujui setelah kondisi stabil"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient transferred successfully",
  "data": {
    "id": 125,
    "inpatient_number": "RI-20240110-0125",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "previous_location": {
      "room": {
        "id": 2,
        "room_number": "201",
        "room_name": "Mawar Kelas I",
        "room_class": "i"
      },
      "bed": {
        "id": 2,
        "bed_number": "201-A"
      }
    },
    "new_location": {
      "room": {
        "id": 1,
        "room_number": "301",
        "room_name": "Melati VIP",
        "room_class": "vip"
      },
      "bed": {
        "id": 15,
        "bed_number": "301-B"
      }
    },
    "transfer": {
      "date": "2024-01-14",
      "time": "09:00:00",
      "reason": "naik_kelas",
      "reason_display": "Naik Kelas",
      "approved_by": {
        "id": 8,
        "name": "Dr. Michael Chen"
      }
    },
    "transfer_history": [
      {
        "transfer_date": "2024-01-14",
        "from_bed": "201-A",
        "to_bed": "301-B",
        "reason": "naik_kelas"
      }
    ],
    "status": "aktif",
    "updated_at": "2024-01-14T09:00:00Z"
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - New Bed Not Available

```json
{
  "success": false,
  "message": "Target bed is not available",
  "error": {
    "code": "BED_NOT_AVAILABLE",
    "details": {
      "bed_id": 15,
      "bed_number": "301-B",
      "current_status": "terisi"
    }
  }
}
```

---

## Discharge Patient

Discharge an inpatient from the hospital.

```http
POST /api/inpatients/{id}/discharge
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
| id | integer | Inpatient ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| discharge_date | date | No | Discharge date (default: today) |
| discharge_time | time | No | Discharge time (default: current time) |
| discharge_type | string | Yes | Discharge type (pulang, rujuk, meninggal, kabur) |
| discharge_condition | string | Yes | Patient condition at discharge (sembuh, membaik, belum_sembuh, mati) |
| discharge_notes | string | No | Additional discharge notes |
| final_diagnosis | string | Yes | Final diagnosis at discharge |
| icd10_code | string | No | ICD-10 code for final diagnosis |
| follow_up_plan | string | No | Follow-up care plan |
| follow_up_date | date | No | Follow-up appointment date |
| follow_up_doctor_id | integer | No | Follow-up doctor ID |
| referral_hospital | string | No | Referral hospital name (if rujuk) |
| referral_reason | string | No | Referral reason (if rujuk) |
| death_certificate_number | string | No | Death certificate number (if meninggal) |
| death_cause | string | No | Cause of death (if meninggal) |
| death_time | time | No | Time of death (if meninggal) |
| discharged_by | integer | Yes | Doctor ID discharging the patient |

### Discharge Types

| Type | Description |
|------|-------------|
| pulang | Regular discharge |
| rujuk | Referred to another hospital |
| meninggal | Patient deceased |
| kabur | Patient left against medical advice |

### Discharge Conditions

| Condition | Description |
|-----------|-------------|
| sembuh | Fully recovered |
| membaik | Improved |
| belum_sembuh | Not fully recovered |
| mati | Deceased |

### Request Example

```json
{
  "discharge_type": "pulang",
  "discharge_condition": "membaik",
  "final_diagnosis": "Demam Berdarah Dengue, Sembuh",
  "icd10_code": "A90",
  "discharge_notes": "Kondisi membaik, demam turun, makan dan minum normal",
  "follow_up_plan": "Kontrol ulang 1 minggu, minum obat teratur",
  "follow_up_date": "2024-01-22",
  "follow_up_doctor_id": 8,
  "discharged_by": 8
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Patient discharged successfully",
  "data": {
    "id": 125,
    "inpatient_number": "RI-20240110-0125",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "admission": {
      "date": "2024-01-10",
      "time": "14:30:00"
    },
    "discharge": {
      "date": "2024-01-15",
      "time": "10:00:00",
      "type": "pulang",
      "type_display": "Pulang",
      "condition": "membaik",
      "condition_display": "Membaik",
      "final_diagnosis": "Demam Berdarah Dengue, Sembuh",
      "icd10_code": "A90",
      "length_of_stay_days": 5,
      "discharged_by": {
        "id": 8,
        "name": "Dr. Michael Chen"
      }
    },
    "location_history": [
      {
        "room": "Mawar Kelas I (201-A)",
        "from": "2024-01-10 14:30",
        "to": "2024-01-14 09:00",
        "days": 3
      },
      {
        "room": "Melati VIP (301-B)",
        "from": "2024-01-14 09:00",
        "to": "2024-01-15 10:00",
        "days": 1
      }
    ],
    "follow_up": {
      "date": "2024-01-22",
      "doctor": {
        "id": 8,
        "name": "Dr. Michael Chen"
      },
      "plan": "Kontrol ulang 1 minggu, minum obat teratur"
    },
    "status": "pulang",
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot discharge patient with status: pulang",
  "error": {
    "code": "INVALID_INPATIENT_STATUS",
    "details": {
      "current_status": "pulang",
      "allowed_statuses": ["aktif", "dipindahkan"]
    }
  }
}
```

---

## Get Patient Bill

Retrieve the bill for an inpatient.

```http
GET /api/inpatients/{id}/bill
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Inpatient ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "inpatient": {
      "id": 125,
      "inpatient_number": "RI-20240110-0125",
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001"
      },
      "admission_date": "2024-01-10",
      "discharge_date": "2024-01-15",
      "length_of_stay": 5
    },
    "summary": {
      "total_amount": 8750000,
      "discount_amount": 0,
      "tax_amount": 0,
      "grand_total": 8750000,
      "deposit_amount": 5000000,
      "remaining_amount": 3750000,
      "payment_status": "partial"
    },
    "breakdown": [
      {
        "category": "room",
        "category_display": "Kamar",
        "items": [
          {
            "description": "Kamar Mawar Kelas I (201-A)",
            "quantity": 3,
            "unit": "hari",
            "unit_price": 500000,
            "total": 1500000
          },
          {
            "description": "Kamar Melati VIP (301-B)",
            "quantity": 1,
            "unit": "hari",
            "unit_price": 750000,
            "total": 750000
          }
        ],
        "subtotal": 2250000
      },
      {
        "category": "doctor",
        "category_display": "Dokter",
        "items": [
          {
            "description": "Visite Dokter Spesialis",
            "quantity": 5,
            "unit": "kali",
            "unit_price": 200000,
            "total": 1000000
          },
          {
            "description": "Konsultasi",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 150000,
            "total": 150000
          }
        ],
        "subtotal": 1150000
      },
      {
        "category": "laboratory",
        "category_display": "Laboratorium",
        "items": [
          {
            "description": "Darah Lengkap",
            "quantity": 2,
            "unit": "kali",
            "unit_price": 150000,
            "total": 300000
          },
          {
            "description": "Dengue IgG/IgM",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 250000,
            "total": 250000
          }
        ],
        "subtotal": 550000
      },
      {
        "category": "pharmacy",
        "category_display": "Farmasi",
        "items": [
          {
            "description": "Obat-obatan selama rawat inap",
            "quantity": 1,
            "unit": "paket",
            "unit_price": 1500000,
            "total": 1500000
          }
        ],
        "subtotal": 1500000
      },
      {
        "category": "medical_supplies",
        "category_display": "BHP",
        "items": [
          {
            "description": "Infus set, dll",
            "quantity": 1,
            "unit": "paket",
            "unit_price": 800000,
            "total": 800000
          }
        ],
        "subtotal": 800000
      },
      {
        "category": "administration",
        "category_display": "Administrasi",
        "items": [
          {
            "description": "Biaya Administrasi",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 500000,
            "total": 500000
          }
        ],
        "subtotal": 500000
      }
    ],
    "payments": [
      {
        "date": "2024-01-10",
        "amount": 5000000,
        "method": "cash",
        "method_display": "Tunai",
        "description": "Deposit awal"
      }
    ],
    "generated_at": "2024-01-15T10:00:00Z"
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (403)

```json
{
  "success": false,
  "message": "You do not have permission to view patient bills",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Data Types Reference

### Admission Type

| Type | Description |
|------|-------------|
| igd | Emergency Department |
| poliklinik | Polyclinic |
| rujukan | Referral from another facility |
| rawat_jalan | Outpatient transfer |

### Inpatient Status

| Status | Description |
|--------|-------------|
| aktif | Active admission |
| dipindahkan | Transferred |
| pulang | Discharged home |
| dirujuk | Referred out |
| meninggal | Deceased |

### Transfer Reason

| Reason | Description |
|--------|-------------|
| naik_kelas | Upgrade room class |
| turun_kelas | Downgrade room class |
| isolasi | Move to isolation |
| kondisi_kritis | Critical condition |
| permintaan_pasien | Patient request |
| kamar_penuh | Room full |
| perbaikan | Room maintenance |
| kondisi_medis | Medical requirement |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `INPATIENT_NOT_FOUND` | 404 | Inpatient with specified ID not found |
| `BED_NOT_AVAILABLE` | 422 | Selected bed is not available |
| `ACTIVE_INPATIENT_EXISTS` | 422 | Patient already has active admission |
| `INVALID_INPATIENT_STATUS` | 422 | Cannot perform action with current status |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission |
| `VALIDATION_ERROR` | 422 | Request validation failed |
