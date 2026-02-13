# Integrasi API Satu Sehat

Dokumen ini menjelaskan semua endpoint untuk integrasi FHIR Satu Sehat (platform interoperabilitas kesehatan nasional Indonesia) dalam sistem SIMRS.

## Daftar Isi

- [Ikhtisar](#ikhtisar)
- [Endpoint Pasien (IHS)](#endpoint-pasien-ihs)
- [Endpoint Encounter](#endpoint-encounter)
- [Endpoint Observation (TTV)](#endpoint-observation-ttv)
- [Endpoint Condition (Diagnosis)](#endpoint-condition-diagnosis)
- [Endpoint Medication](#endpoint-medication)
- [Endpoint Organization](#endpoint-organization)

---

## Ikhtisar

Satu Sehat adalah platform interoperabilitas data kesehatan nasional Indonesia yang menggunakan standar FHIR (Fast Healthcare Interoperability Resources). Sistem SIMRS mengintegrasikan dengan Satu Sehat untuk:

- Mensinkronisasi data pasien (Patient/IHS - Indonesia Health Services)
- Melaporkan encounter (kunjungan)
- Mengirimkan tanda-tanda vital (TTV)
- Melaporkan diagnosis (Condition)
- Mengelola data obat

### Tipe Resource FHIR

| Resource | Deskripsi | Versi FHIR |
|----------|-----------|------------|
| Patient | Demografi pasien | R4 |
| Encounter | Data kunjungan | R4 |
| Observation | Tanda-tanda vital, hasil lab | R4 |
| Condition | Diagnosis | R4 |
| Medication | Katalog obat | R4 |
| MedicationRequest | Resep obat | R4 |
| Organization | Fasilitas kesehatan | R4 |
| Location | Lokasi fasilitas | R4 |
| Practitioner | Penyedia layanan kesehatan | R4 |
| Composition | Dokumen klinis | R4 |

### URL Dasar

| Lingkungan | URL |
|------------|-----|
| Produksi | https://api-satusehat.kemkes.go.id/fhir-r4/v1 |
| Staging | https://api-satusehat-stg.kemkes.go.id/fhir-r4/v1 |
| Auth | https://api-satusehat.kemkes.go.id/oauth2/v1 |

---

## Endpoint Pasien (IHS)

### Cari Pasien berdasarkan NIK

Mencari pasien yang sudah ada di Satu Sehat berdasarkan NIK.

```http
GET /api/satusehat/patient/search
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| nik | string | Ya | NIK 16 digit |

### Respons Sukses (200) - Pasien Ditemukan

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

### Respons Sukses (200) - Pasien Tidak Ditemukan

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

### Buat Pasien

Membuat pasien baru di Satu Sehat.

```http
POST /api/satusehat/patient
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| nik | string | Ya | NIK 16 digit |
| name | string | Ya | Nama lengkap pasien |
| gender | string | Ya | male, female, other, unknown |
| birth_date | date | Ya | Tanggal lahir (YYYY-MM-DD) |
| birth_place | string | Tidak | Tempat lahir |
| address | string | Tidak | Alamat lengkap |
| city | string | Tidak | Kota |
| postal_code | string | Tidak | Kode pos |
| phone | string | Tidak | Nomor telepon |
| ihs_number | string | Tidak | Nomor IHS yang sudah ada (jika diketahui) |

### Contoh Permintaan

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

### Respons Sukses (201)

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

### Respons Error (422) - Pasien Sudah Ada

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

### Dapatkan Pasien berdasarkan IHS

```http
GET /api/satusehat/patient/{ihs_number}
```

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| ihs_number | string | ID Pasien Satu Sehat |

### Respons Sukses (200)

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

## Endpoint Encounter

### Buat Encounter

Melaporkan kunjungan/encounter ke Satu Sehat.

```http
POST /api/satusehat/encounter
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| patient_ihs | string | Ya | Nomor IHS pasien |
| status | string | Ya | planned, arrived, in-progress, finished, cancelled |
| class | string | Ya | AMB (rawat jalan), IMP (rawat inap), EMER (gawat darurat), VR (virtual) |
| period_start | datetime | Ya | Waktu mulai (ISO 8601) |
| period_end | datetime | Tidak | Waktu selesai (jika sudah selesai) |
| location_id | string | Ya | ID IHS Lokasi/Fasilitas |
| practitioner_ihs | string | Tidak | ID IHS Practitioner |
| diagnosis | array | Tidak | Array kode diagnosis |

### Contoh Permintaan

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

### Respons Sukses (201)

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

### Dapatkan Encounter

```http
GET /api/satusehat/encounter/{encounter_id}
```

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| encounter_id | string | ID Encounter Satu Sehat |

### Respons Sukses (200)

Format sama dengan respons Buat Encounter.

---

## Endpoint Observation (TTV)

### Buat Observation

Mengirimkan tanda-tanda vital (TTV) ke Satu Sehat.

```http
POST /api/satusehat/observation
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| patient_ihs | string | Ya | Nomor IHS pasien |
| encounter_id | string | Ya | ID Encounter |
| observation_type | string | Ya | vital-signs, laboratory, dll |
| effective_date | datetime | Ya | Timestamp observasi |
| components | array | Ya | Komponen observasi |

### Contoh Permintaan - Tanda-Tanda Vital

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

### Respons Sukses (201)

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

## Endpoint Condition (Diagnosis)

### Buat Condition

Mengirimkan diagnosis ke Satu Sehat.

```http
POST /api/satusehat/condition
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| patient_ihs | string | Ya | Nomor IHS pasien |
| encounter_id | string | Ya | ID Encounter |
| code | string | Ya | Kode ICD-10 |
| display | string | Ya | Deskripsi diagnosis |
| category | string | Ya | encounter-diagnosis, problem-list-item |
| onset_date | date | Tidak | Tanggal onset |
| clinical_status | string | Ya | active, resolved |

### Contoh Permintaan

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

### Respons Sukses (201)

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

## Endpoint Medication

### Buat Medication

Mendaftarkan obat ke Satu Sehat.

```http
POST /api/satusehat/medication
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| code | string | Ya | Kode obat (KFA - Kode Farmasi dan Alkes) |
| display | string | Ya | Nama obat |
| form | string | Ya | Bentuk sediaan (tablet, kapsul, sirup, dll) |
| content | object | Tidak | Detail kandungan obat |

### Contoh Permintaan

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

### Respons Sukses (201)

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

### Buat Medication Request

Mengirimkan resep ke Satu Sehat.

```http
POST /api/satusehat/medication-request
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| patient_ihs | string | Ya | Nomor IHS pasien |
| encounter_id | string | Ya | ID Encounter |
| medication_id | string | Ya | ID IHS Medication |
| status | string | Ya | active, completed, stopped |
| intent | string | Ya | proposal, plan, order, option |
| authored_on | datetime | Ya | Timestamp resep |
| requester_ihs | string | Ya | ID IHS Practitioner |
| dosage | array | Ya | Instruksi dosis |

### Contoh Permintaan

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

## Endpoint Organization

### Dapatkan Organization

```http
GET /api/satusehat/organization/{organization_id}
```

### Respons Sukses (200)

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

## Log Satu Sehat

### Dapatkan Log Integrasi

```http
GET /api/satusehat/logs
```

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| resource_type | string | Tidak | Filter berdasarkan tipe resource |
| status | string | Tidak | Filter berdasarkan status (success, failed) |
| from_date | date | Tidak | Filter dari tanggal |
| to_date | date | Tidak | Filter sampai tanggal |
| local_type | string | Tidak | Filter berdasarkan tipe model lokal |
| local_id | integer | Tidak | Filter berdasarkan ID model lokal |

### Respons Sukses (200)

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

## Kode LOINC untuk Tanda-Tanda Vital

| Tanda-Tanda Vital | Kode LOINC | Deskripsi |
|-------------------|------------|-----------|
| Systolic BP | 8480-6 | Systolic blood pressure |
| Diastolic BP | 8462-4 | Diastolic blood pressure |
| Heart Rate | 8867-4 | Heart rate |
| Temperature | 8310-5 | Body temperature |
| Respiratory Rate | 9279-1 | Respiratory rate |
| SpO2 | 2708-6 | Oxygen saturation |
| Weight | 29463-7 | Body weight |
| Height | 8302-2 | Body height |
| BMI | 39156-5 | Body mass index |

## Kelas Encounter

| Kode | Deskripsi |
|------|-----------|
| AMB | Ambulatory (Rawat Jalan) |
| IMP | Inpatient (Rawat Inap) |
| EMER | Emergency (IGD) |
| VR | Virtual (Telemedicine) |
| HH | Home Health |

## Referensi Kode Error

| Kode | Status HTTP | Deskripsi |
|------|-------------|-----------|
| `SATU_SEHAT_AUTH_FAILED` | 401 | Autentikasi Satu Sehat gagal |
| `SATU_SEHAT_PATIENT_EXISTS` | 422 | Pasien sudah ada |
| `SATU_SEHAT_PATIENT_NOT_FOUND` | 404 | Pasien tidak ditemukan di Satu Sehat |
| `SATU_SEHAT_INVALID_FHIR` | 422 | Resource FHIR tidak valid |
| `SATU_SEHAT_SERVICE_UNAVAILABLE` | 503 | Layanan Satu Sehat tidak tersedia |
| `SATU_SEHAT_RATE_LIMIT` | 429 | Batas rate terlampaui |
| `SATU_SEHAT_INVALID_ICD10` | 422 | Kode ICD-10 tidak valid |
| `SATU_SEHAT_INVALID_LOINC` | 422 | Kode LOINC tidak valid |
| `SATU_SEHAT_MISSING_IHS` | 422 | Nomor IHS diperlukan tetapi tidak disediakan |
