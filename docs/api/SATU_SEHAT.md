# Satu Sehat Integration API

This document describes all endpoints for Satu Sehat (Indonesia's national health interoperability platform) FHIR integration in the SIMRS system.

## Table of Contents

- [Overview](#overview)
- [Patient (IHS) Endpoints](#patient-ihs-endpoints)
- [Encounter Endpoints](#encounter-endpoints)
- [Observation (TTV) Endpoints](#observation-ttv-endpoints)
- [Condition (Diagnosis) Endpoints](#condition-diagnosis-endpoints)
- [Medication Endpoints](#medication-endpoints)
- [Organization Endpoints](#organization-endpoints)

---

## Overview

Satu Sehat is Indonesia's national health data interoperability platform using FHIR (Fast Healthcare Interoperability Resources) standard. The SIMRS system integrates with Satu Sehat to:

- Synchronize patient data (Patient/IHS - Indonesia Health Services)
- Report encounters (visits)
- Submit vital signs (TTV - Tanda-Tanda Vital)
- Report diagnoses (Condition)
- Manage medication data

### FHIR Resource Types

| Resource | Description | FHIR Version |
|----------|-------------|--------------|
| Patient | Patient demographics | R4 |
| Encounter | Visit/encounter data | R4 |
| Observation | Vital signs, lab results | R4 |
| Condition | Diagnoses | R4 |
| Medication | Medication catalog | R4 |
| MedicationRequest | Prescription orders | R4 |
| Organization | Healthcare facilities | R4 |
| Location | Facility locations | R4 |
| Practitioner | Healthcare providers | R4 |
| Composition | Clinical documents | R4 |

### Base URLs

| Environment | URL |
|-------------|-----|
| Production | https://api-satusehat.kemkes.go.id/fhir-r4/v1 |
| Staging | https://api-satusehat-stg.kemkes.go.id/fhir-r4/v1 |
| Auth | https://api-satusehat.kemkes.go.id/oauth2/v1 |

---

## Patient (IHS) Endpoints

### Search Patient by NIK

Search for an existing patient in Satu Sehat by NIK.

```http
GET /api/satusehat/patient/search
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| nik | string | Yes | 16-digit NIK |

### Response Success (200) - Patient Found

```json
{
  "success": true,
  "data": {
    "resourceType": "Bundle",
    "type": "searchset",
    "total": 1,
    "entry": [
      {
        "resource": {
          "resourceType": "Patient",
          "id": "P0000000001",
          "identifier": [
            {
              "system": "https://fhir.kemkes.go.id/id/nik",
              "value": "1234567890123456",
              "use": "official"
            }
          ],
          "active": true,
          "name": [
            {
              "use": "official",
              "text": "John Doe",
              "family": "Doe",
              "given": ["John"]
            }
          ],
          "gender": "male",
          "birthDate": "1990-01-01",
          "address": [
            {
              "use": "home",
              "line": ["Jl. Merdeka No. 123"],
              "city": "Jakarta",
              "postalCode": "10110",
              "country": "ID"
            }
          ]
        }
      }
    ]
  }
}
```

### Response Success (200) - Patient Not Found

```json
{
  "success": true,
  "data": {
    "resourceType": "Bundle",
    "type": "searchset",
    "total": 0,
    "entry": []
  }
}
```

---

### Create Patient

Create a new patient in Satu Sehat.

```http
POST /api/satusehat/patient
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| nik | string | Yes | 16-digit NIK |
| name | string | Yes | Patient full name |
| gender | string | Yes | male, female, other, unknown |
| birth_date | date | Yes | Birth date (YYYY-MM-DD) |
| birth_place | string | No | Birth place |
| address | string | No | Full address |
| city | string | No | City |
| postal_code | string | No | Postal code |
| phone | string | No | Phone number |
| ihs_number | string | No | Existing IHS number (if known) |

### Request Example

```json
{
  "nik": "1234567890123456",
  "name": "John Doe",
  "gender": "male",
  "birth_date": "1990-01-01",
  "birth_place": "Jakarta",
  "address": "Jl. Merdeka No. 123",
  "city": "Jakarta",
  "postal_code": "10110",
  "phone": "08123456789"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Patient created successfully in Satu Sehat",
  "data": {
    "resourceType": "Patient",
    "id": "P0000000001",
    "identifier": [
      {
        "system": "https://fhir.kemkes.go.id/id/nik",
        "value": "1234567890123456",
        "use": "official"
      }
    ],
    "active": true,
    "name": [
      {
        "use": "official",
        "text": "John Doe"
      }
    ],
    "gender": "male",
    "birthDate": "1990-01-01",
    "meta": {
      "versionId": "1",
      "lastUpdated": "2024-01-15T08:00:00Z"
    }
  },
  "ihs_number": "P0000000001"
}
```

### Response Error (422) - Patient Already Exists

```json
{
  "success": false,
  "message": "Patient with this NIK already exists in Satu Sehat",
  "error": {
    "code": "SATU_SEHAT_PATIENT_EXISTS",
    "details": {
      "existing_ihs_number": "P0000000001"
    }
  }
}
```

---

### Get Patient by IHS

```http
GET /api/satusehat/patient/{ihs_number}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| ihs_number | string | Satu Sehat Patient ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "resourceType": "Patient",
    "id": "P0000000001",
    "identifier": [
      {
        "system": "https://fhir.kemkes.go.id/id/nik",
        "value": "1234567890123456"
      }
    ],
    "active": true,
    "name": [
      {
        "use": "official",
        "text": "John Doe"
      }
    ],
    "gender": "male",
    "birthDate": "1990-01-01"
  }
}
```

---

## Encounter Endpoints

### Create Encounter

Report a visit/encounter to Satu Sehat.

```http
POST /api/satusehat/encounter
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_ihs | string | Yes | Patient IHS number |
| status | string | Yes | planned, arrived, in-progress, finished, cancelled |
| class | string | Yes | AMB (ambulatory), IMP (inpatient), EMER (emergency), VR (virtual) |
| period_start | datetime | Yes | Start time (ISO 8601) |
| period_end | datetime | No | End time (if finished) |
| location_id | string | Yes | Location/Facility IHS ID |
| practitioner_ihs | string | No | Practitioner IHS ID |
| diagnosis | array | No | Array of diagnosis codes |

### Request Example

```json
{
  "patient_ihs": "P0000000001",
  "status": "finished",
  "class": "AMB",
  "period_start": "2024-01-15T08:30:00+07:00",
  "period_end": "2024-01-15T10:15:00+07:00",
  "location_id": "L0000000001",
  "practitioner_ihs": "P0000000100",
  "diagnosis": [
    {
      "system": "http://hl7.org/fhir/sid/icd-10",
      "code": "G44.2",
      "display": "Tension-type headache"
    }
  ]
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Encounter created successfully",
  "data": {
    "resourceType": "Encounter",
    "id": "E0000000001",
    "identifier": [
      {
        "system": "http://sys-ids.kemkes.go.id/encounter/{organization_id}",
        "value": "RJ-20240115-0045"
      }
    ],
    "status": "finished",
    "class": {
      "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
      "code": "AMB",
      "display": "ambulatory"
    },
    "subject": {
      "reference": "Patient/P0000000001",
      "display": "John Doe"
    },
    "participant": [
      {
        "type": [
          {
            "coding": [
              {
                "system": "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                "code": "ATND",
                "display": "attender"
              }
            ]
          }
        ],
        "individual": {
          "reference": "Practitioner/P0000000100",
          "display": "Dr. Sarah Johnson"
        }
      }
    ],
    "period": {
      "start": "2024-01-15T08:30:00+07:00",
      "end": "2024-01-15T10:15:00+07:00"
    },
    "location": [
      {
        "location": {
          "reference": "Location/L0000000001",
          "display": "Poli Umum"
        }
      }
    ],
    "diagnosis": [
      {
        "condition": {
          "reference": "Condition/C0000000001",
          "display": "Tension-type headache"
        },
        "use": {
          "coding": [
            {
              "system": "http://terminology.hl7.org/CodeSystem/diagnosis-role",
              "code": "DD",
              "display": "Discharge diagnosis"
            }
          ]
        },
        "rank": 1
      }
    ]
  },
  "encounter_id": "E0000000001"
}
```

---

### Get Encounter

```http
GET /api/satusehat/encounter/{encounter_id}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| encounter_id | string | Satu Sehat Encounter ID |

### Response Success (200)

Same format as Create Encounter response.

---

## Observation (TTV) Endpoints

### Create Observation

Submit vital signs (TTV) to Satu Sehat.

```http
POST /api/satusehat/observation
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_ihs | string | Yes | Patient IHS number |
| encounter_id | string | Yes | Encounter ID |
| observation_type | string | Yes | vital-signs, laboratory, etc |
| effective_date | datetime | Yes | Observation timestamp |
| components | array | Yes | Observation components |

### Request Example - Vital Signs

```json
{
  "patient_ihs": "P0000000001",
  "encounter_id": "E0000000001",
  "observation_type": "vital-signs",
  "effective_date": "2024-01-15T08:30:00+07:00",
  "components": [
    {
      "code": "8480-6",
      "display": "Systolic blood pressure",
      "value": 120,
      "unit": "mmHg",
      "system": "http://loinc.org"
    },
    {
      "code": "8462-4",
      "display": "Diastolic blood pressure",
      "value": 80,
      "unit": "mmHg",
      "system": "http://loinc.org"
    },
    {
      "code": "8867-4",
      "display": "Heart rate",
      "value": 88,
      "unit": "beats/minute",
      "system": "http://loinc.org"
    },
    {
      "code": "8310-5",
      "display": "Body temperature",
      "value": 38.2,
      "unit": "Cel",
      "system": "http://loinc.org"
    },
    {
      "code": "9279-1",
      "display": "Respiratory rate",
      "value": 20,
      "unit": "breaths/minute",
      "system": "http://loinc.org"
    },
    {
      "code": "2708-6",
      "display": "Oxygen saturation",
      "value": 98,
      "unit": "%",
      "system": "http://loinc.org"
    }
  ]
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Observation created successfully",
  "data": {
    "resourceType": "Observation",
    "id": "O0000000001",
    "identifier": [
      {
        "system": "http://sys-ids.kemkes.go.id/observation/{organization_id}",
        "value": "TTV-20240115-001"
      }
    ],
    "status": "final",
    "category": [
      {
        "coding": [
          {
            "system": "http://terminology.hl7.org/CodeSystem/observation-category",
            "code": "vital-signs",
            "display": "Vital Signs"
          }
        ]
      }
    ],
    "code": {
      "coding": [
        {
          "system": "http://loinc.org",
          "code": "85354-9",
          "display": "Blood pressure panel"
        }
      ]
    },
    "subject": {
      "reference": "Patient/P0000000001"
    },
    "encounter": {
      "reference": "Encounter/E0000000001"
    },
    "effectiveDateTime": "2024-01-15T08:30:00+07:00",
    "component": [
      {
        "code": {
          "coding": [
            {
              "system": "http://loinc.org",
              "code": "8480-6",
              "display": "Systolic blood pressure"
            }
          ]
        },
        "valueQuantity": {
          "value": 120,
          "unit": "mmHg",
          "system": "http://unitsofmeasure.org",
          "code": "mm[Hg]"
        }
      }
    ]
  },
  "observation_id": "O0000000001"
}
```

---

## Condition (Diagnosis) Endpoints

### Create Condition

Submit diagnosis to Satu Sehat.

```http
POST /api/satusehat/condition
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_ihs | string | Yes | Patient IHS number |
| encounter_id | string | Yes | Encounter ID |
| code | string | Yes | ICD-10 code |
| display | string | Yes | Diagnosis description |
| category | string | Yes | encounter-diagnosis, problem-list-item |
| onset_date | date | No | Onset date |
| clinical_status | string | Yes | active, resolved |

### Request Example

```json
{
  "patient_ihs": "P0000000001",
  "encounter_id": "E0000000001",
  "code": "G44.2",
  "display": "Tension-type headache",
  "category": "encounter-diagnosis",
  "clinical_status": "active",
  "onset_date": "2024-01-15"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Condition created successfully",
  "data": {
    "resourceType": "Condition",
    "id": "C0000000001",
    "identifier": [
      {
        "system": "http://sys-ids.kemkes.go.id/condition/{organization_id}",
        "value": "DX-20240115-001"
      }
    ],
    "clinicalStatus": {
      "coding": [
        {
          "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
          "code": "active",
          "display": "Active"
        }
      ]
    },
    "category": [
      {
        "coding": [
          {
            "system": "http://terminology.hl7.org/CodeSystem/condition-category",
            "code": "encounter-diagnosis",
            "display": "Encounter Diagnosis"
          }
        ]
      }
    ],
    "code": {
      "coding": [
        {
          "system": "http://hl7.org/fhir/sid/icd-10",
          "code": "G44.2",
          "display": "Tension-type headache"
        }
      ]
    },
    "subject": {
      "reference": "Patient/P0000000001",
      "display": "John Doe"
    },
    "encounter": {
      "reference": "Encounter/E0000000001"
    },
    "onsetDateTime": "2024-01-15"
  },
  "condition_id": "C0000000001"
}
```

---

## Medication Endpoints

### Create Medication

Register medication to Satu Sehat.

```http
POST /api/satusehat/medication
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| code | string | Yes | Medication code (KFA - Kode Farmasi dan Alkes) |
| display | string | Yes | Medication name |
| form | string | Yes | Dosage form (tablet, capsule, syrup, etc) |
| content | object | No | Medication content details |

### Request Example

```json
{
  "code": "93001001",
  "display": "Paracetamol 500 mg Tablet",
  "form": "tablet",
  "content": {
    "itemCode": "93001001",
    "itemName": "Paracetamol",
    "amount": 500,
    "unit": "mg"
  }
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Medication created successfully",
  "data": {
    "resourceType": "Medication",
    "id": "M0000000001",
    "identifier": [
      {
        "system": "http://sys-ids.kemkes.go.id/medication/{organization_id}",
        "value": "MED-001"
      }
    ],
    "code": {
      "coding": [
        {
          "system": "http://terminology.kemkes.go.id/CodeSystem/kfa",
          "code": "93001001",
          "display": "Paracetamol 500 mg Tablet"
        }
      ]
    },
    "status": "active",
    "form": {
      "coding": [
        {
          "system": "http://terminology.kemkes.go.id/CodeSystem/kfa-form",
          "code": "tablet",
          "display": "Tablet"
        }
      ]
    }
  },
  "medication_id": "M0000000001"
}
```

---

### Create Medication Request

Submit prescription to Satu Sehat.

```http
POST /api/satusehat/medication-request
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_ihs | string | Yes | Patient IHS number |
| encounter_id | string | Yes | Encounter ID |
| medication_id | string | Yes | Medication IHS ID |
| status | string | Yes | active, completed, stopped |
| intent | string | Yes | proposal, plan, order, option |
| authored_on | datetime | Yes | Prescription timestamp |
| requester_ihs | string | Yes | Practitioner IHS ID |
| dosage | array | Yes | Dosage instructions |

### Request Example

```json
{
  "patient_ihs": "P0000000001",
  "encounter_id": "E0000000001",
  "medication_id": "M0000000001",
  "status": "active",
  "intent": "order",
  "authored_on": "2024-01-15T09:00:00+07:00",
  "requester_ihs": "P0000000100",
  "dosage": [
    {
      "sequence": 1,
      "text": "3x1 tablet sehari",
      "timing": {
        "repeat": {
          "frequency": 3,
          "period": 1,
          "periodUnit": "d"
        }
      },
      "route": {
        "coding": [
          {
            "system": "http://www.whocc.no/atc",
            "code": "N02BE01",
            "display": "Paracetamol"
          }
        ]
      },
      "doseQuantity": {
        "value": 1,
        "unit": "tablet"
      }
    }
  ]
}
```

---

## Organization Endpoints

### Get Organization

```http
GET /api/satusehat/organization/{organization_id}
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "resourceType": "Organization",
    "id": "ORG0000001",
    "identifier": [
      {
        "system": "http://sys-ids.kemkes.go.id/organization",
        "value": "0123B001"
      }
    ],
    "active": true,
    "name": "RSUD Makmur",
    "type": [
      {
        "coding": [
          {
            "system": "http://terminology.hl7.org/CodeSystem/organization-type",
            "code": "prov",
            "display": "Healthcare Provider"
          }
        ]
      }
    ],
    "address": [
      {
        "use": "work",
        "line": ["Jl. Sudirman No. 123"],
        "city": "Jakarta",
        "postalCode": "10110",
        "country": "ID"
      }
    ]
  }
}
```

---

## Satu Sehat Logs

### Get Integration Logs

```http
GET /api/satusehat/logs
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| resource_type | string | No | Filter by resource type |
| status | string | No | Filter by status (success, failed) |
| from_date | date | No | Filter from date |
| to_date | date | No | Filter to date |
| local_type | string | No | Filter by local model type |
| local_id | integer | No | Filter by local model ID |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 567,
      "resource_type": "Patient",
      "local_type": "Patient",
      "local_id": 1,
      "fhir_id": "P0000000001",
      "action": "POST",
      "status": "success",
      "request_data": { ... },
      "response_data": { ... },
      "created_at": "2024-01-15T08:00:00Z",
      "created_by": {
        "id": 1,
        "name": "Admin"
      }
    }
  ],
  "meta": {
    "total": 156,
    "success_count": 150,
    "failed_count": 6
  }
}
```

## LOINC Codes for Vital Signs

| Vital Sign | LOINC Code | Description |
|------------|------------|-------------|
| Systolic BP | 8480-6 | Systolic blood pressure |
| Diastolic BP | 8462-4 | Diastolic blood pressure |
| Heart Rate | 8867-4 | Heart rate |
| Temperature | 8310-5 | Body temperature |
| Respiratory Rate | 9279-1 | Respiratory rate |
| SpO2 | 2708-6 | Oxygen saturation |
| Weight | 29463-7 | Body weight |
| Height | 8302-2 | Body height |
| BMI | 39156-5 | Body mass index |

## Encounter Classes

| Code | Description |
|------|-------------|
| AMB | Ambulatory (Rawat Jalan) |
| IMP | Inpatient (Rawat Inap) |
| EMER | Emergency (IGD) |
| VR | Virtual (Telemedicine) |
| HH | Home Health |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `SATU_SEHAT_AUTH_FAILED` | 401 | Satu Sehat authentication failed |
| `SATU_SEHAT_PATIENT_EXISTS` | 422 | Patient already exists |
| `SATU_SEHAT_PATIENT_NOT_FOUND` | 404 | Patient not found in Satu Sehat |
| `SATU_SEHAT_INVALID_FHIR` | 422 | Invalid FHIR resource |
| `SATU_SEHAT_SERVICE_UNAVAILABLE` | 503 | Satu Sehat service unavailable |
| `SATU_SEHAT_RATE_LIMIT` | 429 | Rate limit exceeded |
| `SATU_SEHAT_INVALID_ICD10` | 422 | Invalid ICD-10 code |
| `SATU_SEHAT_INVALID_LOINC` | 422 | Invalid LOINC code |
| `SATU_SEHAT_MISSING_IHS` | 422 | IHS number required but not provided |
