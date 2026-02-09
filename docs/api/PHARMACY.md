# Pharmacy (Farmasi) API

This document describes all endpoints for pharmacy management including prescriptions, medicine stock, and dispensing in the SIMRS system.

## Table of Contents

- [Prescription Endpoints](#prescription-endpoints)
- [Prescription Item Endpoints](#prescription-item-endpoints)
- [Medicine Stock Endpoints](#medicine-stock-endpoints)
- [Dispensing Endpoints](#dispensing-endpoints)
- [Pharmacy Reports](#pharmacy-reports)

---

## Prescription Endpoints

### List Prescriptions

Retrieve a paginated list of prescriptions.

```http
GET /api/pharmacy/prescriptions
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
| doctor_id | integer | No | Filter by prescribing doctor |
| status | string | No | Filter by status (pending, verified, dispensed, completed, cancelled) |
| prescription_type | string | No | Filter by type (umum, bpjs, narkotika, psikotropika) |
| priority | string | No | Filter by priority (normal, urgent, emergency) |
| from_date | date | No | Filter from date |
| to_date | date | No | Filter to date |
| is_verified | boolean | No | Filter by verification status |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 32,
      "prescription_number": "RX-20240115-0032",
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
      "medical_record": {
        "id": 78,
        "record_number": "RM-20240115-0078"
      },
      "prescription_date": "2024-01-15",
      "prescription_type": "umum",
      "priority": "normal",
      "status": "completed",
      "clinical_indication": "Tension-type headache with fever",
      "allergies": "None known",
      "prescribed_by": {
        "id": 5,
        "name": "Dr. Sarah Johnson",
        "specialization": "Dokter Umum"
      },
      "verified_by_pharmacist": true,
      "verified_at": "2024-01-15T09:30:00Z",
      "dispensed_at": "2024-01-15T10:00:00Z",
      "total_items": 3,
      "total_estimated_cost": 75000.00,
      "created_at": "2024-01-15T09:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 85,
    "summary": {
      "pending": 12,
      "verified": 8,
      "dispensed": 5,
      "completed": 55,
      "cancelled": 5
    }
  }
}
```

---

### Create Prescription

Create a new prescription.

```http
POST /api/pharmacy/prescriptions
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
| visit_id | integer | Yes | Visit ID |
| medical_record_id | integer | Yes | Medical record ID |
| prescription_date | date | Yes | Prescription date (YYYY-MM-DD) |
| prescription_type | string | Yes | Type (umum, bpjs, narkotika, psikotropika) |
| priority | string | No | Priority (normal, urgent, emergency) - default: normal |
| clinical_indication | string | No | Clinical indication/diagnosis |
| allergies | string | No | Known allergies |
| notes | string | No | Additional notes |
| items | array | Yes | Prescription items |

### Request Example

```json
{
  "patient_id": 1,
  "visit_id": 45,
  "medical_record_id": 78,
  "prescription_date": "2024-01-15",
  "prescription_type": "umum",
  "priority": "normal",
  "clinical_indication": "Tension-type headache with fever",
  "allergies": "Tidak ada alergi obat yang diketahui",
  "notes": "Minum obat setelah makan",
  "items": [
    {
      "medicine_id": 1,
      "quantity": 15,
      "dosage": "3x1 tablet",
      "frequency": "3 kali sehari",
      "duration": "5 hari",
      "instruction": "Sesudah makan",
      "notes": "Jika demam > 38.5°C"
    },
    {
      "medicine_id": 2,
      "quantity": 10,
      "dosage": "2x1 tablet",
      "frequency": "2 kali sehari",
      "duration": "5 hari",
      "instruction": "Sesudah makan"
    }
  ]
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Prescription created successfully",
  "data": {
    "id": 32,
    "prescription_number": "RX-20240115-0032",
    "patient_id": 1,
    "visit_id": 45,
    "medical_record_id": 78,
    "prescription_date": "2024-01-15",
    "prescription_type": "umum",
    "priority": "normal",
    "status": "pending",
    "clinical_indication": "Tension-type headache with fever",
    "allergies": "Tidak ada alergi obat yang diketahui",
    "prescribed_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "verified_by_pharmacist": false,
    "items": [
      {
        "id": 1,
        "medicine": {
          "id": 1,
          "code": "PAR500",
          "name": "Paracetamol 500mg",
          "dosage_form": "tablet"
        },
        "quantity": 15,
        "dosage": "3x1 tablet",
        "frequency": "3 kali sehari",
        "duration": "5 hari",
        "instruction": "Sesudah makan",
        "notes": "Jika demam > 38.5°C",
        "unit_price": 1500.00,
        "total_price": 22500.00
      },
      {
        "id": 2,
        "medicine": {
          "id": 2,
          "code": "IBU400",
          "name": "Ibuprofen 400mg",
          "dosage_form": "tablet"
        },
        "quantity": 10,
        "dosage": "2x1 tablet",
        "frequency": "2 kali sehari",
        "duration": "5 hari",
        "instruction": "Sesudah makan",
        "unit_price": 2500.00,
        "total_price": 25000.00
      }
    ],
    "total_items": 2,
    "total_estimated_cost": 47500.00,
    "created_at": "2024-01-15T09:00:00Z"
  }
}
```

---

### Get Prescription

Retrieve detailed information about a specific prescription.

```http
GET /api/pharmacy/prescriptions/{id}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Prescription ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 32,
    "prescription_number": "RX-20240115-0032",
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "gender": "L",
      "age": 34,
      "address": "Jl. Merdeka No. 123",
      "phone": "08123456789"
    },
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      }
    },
    "medical_record": {
      "id": 78,
      "record_number": "RM-20240115-0078",
      "diagnosis_primary": "Tension Type Headache"
    },
    "prescription_date": "2024-01-15",
    "prescription_type": "umum",
    "priority": "normal",
    "status": "completed",
    "clinical_indication": "Tension-type headache with fever",
    "allergies": "Tidak ada alergi obat yang diketahui",
    "notes": "Minum obat setelah makan",
    "prescribed_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson",
      "specialization": "Dokter Umum",
      "phone": "08123456780"
    },
    "verified_by_pharmacist": true,
    "verified_at": "2024-01-15T09:30:00Z",
    "dispensed_at": "2024-01-15T10:00:00Z",
    "dispensed_by": {
      "id": 10,
      "name": "Apt. Linda Wijaya"
    },
    "items": [
      {
        "id": 1,
        "medicine": {
          "id": 1,
          "code": "PAR500",
          "name": "Paracetamol 500mg",
          "classification": "obat_bebas",
          "dosage_form": "tablet",
          "unit": "tablet"
        },
        "quantity": 15,
        "quantity_dispensed": 15,
        "dosage": "3x1 tablet",
        "frequency": "3 kali sehari",
        "duration": "5 hari",
        "instruction": "Sesudah makan",
        "notes": "Jika demam > 38.5°C",
        "unit_price": 1500.00,
        "total_price": 22500.00,
        "is_dispensed": true
      }
    ],
    "total_items": 2,
    "total_estimated_cost": 47500.00,
    "is_ready_for_dispensing": false,
    "created_at": "2024-01-15T09:00:00Z"
  }
}
```

---

### Update Prescription

Update prescription information (only if not dispensed).

```http
PUT /api/pharmacy/prescriptions/{id}
```

### Request Body

Same as Create Prescription (all fields optional).

### Response Error (422) - Already Dispensed

```json
{
  "success": false,
  "message": "Cannot modify prescription that has been dispensed",
  "error": {
    "code": "PRESCRIPTION_ALREADY_DISPENSED",
    "details": {
      "dispensed_at": "2024-01-15T10:00:00Z"
    }
  }
}
```

---

### Verify Prescription

Verify prescription by pharmacist.

```http
POST /api/pharmacy/prescriptions/{id}/verify
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| notes | string | No | Verification notes |

### Response Success (200)

```json
{
  "success": true,
  "message": "Prescription verified successfully",
  "data": {
    "id": 32,
    "verified_by_pharmacist": true,
    "verified_at": "2024-01-15T09:30:00Z",
    "verified_by": {
      "id": 10,
      "name": "Apt. Linda Wijaya"
    },
    "status": "verified"
  }
}
```

---

## Medicine Stock Endpoints

### List Medicines

```http
GET /api/pharmacy/medicines
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number |
| per_page | integer | No | Items per page |
| search | string | No | Search by name or code |
| classification | string | No | Filter by classification |
| dosage_form | string | No | Filter by dosage form |
| is_active | boolean | No | Filter by active status |
| low_stock | boolean | No | Show only low stock items |
| expired | boolean | No | Show only expired items |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "PAR500",
      "name": "Paracetamol 500mg",
      "classification": "obat_bebas",
      "dosage_form": "tablet",
      "unit": "tablet",
      "manufacturer": "Generic",
      "registration_number": "BPOM-12345",
      "is_generic": true,
      "stock": 1500,
      "min_stock": 100,
      "selling_price": 1500.00,
      "purchase_price": 800.00,
      "expired_date": "2026-12-31",
      "is_active": true,
      "is_low_stock": false,
      "is_out_of_stock": false,
      "is_expired": false,
      "is_expiring_soon": false,
      "stock_status": "in_stock",
      "expiration_status": "valid"
    }
  ]
}
```

---

### Get Medicine Detail

```http
GET /api/pharmacy/medicines/{id}
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "PAR500",
    "name": "Paracetamol 500mg",
    "classification": "obat_bebas",
    "classification_label": "Obat Bebas",
    "dosage_form": "tablet",
    "dosage_form_label": "Tablet",
    "unit": "tablet",
    "manufacturer": "Generic",
    "registration_number": "BPOM-12345",
    "is_generic": true,
    "stock": 1500,
    "min_stock": 100,
    "selling_price": 1500.00,
    "purchase_price": 800.00,
    "expired_date": "2026-12-31",
    "is_active": true,
    "stock_history": [
      {
        "date": "2024-01-15",
        "type": "out",
        "quantity": 15,
        "reference": "RX-20240115-0032",
        "balance": 1500
      }
    ],
    "created_at": "2023-01-01T00:00:00Z"
  }
}
```

---

### Update Medicine Stock

```http
POST /api/pharmacy/medicines/{id}/stock
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| quantity | number | Yes | Quantity to add/remove |
| type | string | Yes | in (stock in), out (stock out) |
| reference | string | No | Reference number/document |
| notes | string | No | Stock movement notes |
| expired_date | date | No | Expiration date (for stock in) |

### Request Example

```json
{
  "quantity": 500,
  "type": "in",
  "reference": "PO-20240115-001",
  "notes": "Penerimaan dari distributor",
  "expired_date": "2026-12-31"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Stock updated successfully",
  "data": {
    "id": 1,
    "previous_stock": 1000,
    "current_stock": 1500,
    "movement": {
      "type": "in",
      "quantity": 500
    }
  }
}
```

---

## Dispensing Endpoints

### Dispense Prescription

Dispense prescription items to patient.

```http
POST /api/pharmacy/prescriptions/{id}/dispense
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| items | array | Yes | Items to dispense |
| patient_confirmation | boolean | Yes | Patient confirmation received |
| counseling_provided | boolean | Yes | Counseling provided to patient |

### Request Example

```json
{
  "items": [
    {
      "item_id": 1,
      "quantity_dispensed": 15,
      "batch_number": "BATCH001",
      "expired_date": "2026-12-31"
    },
    {
      "item_id": 2,
      "quantity_dispensed": 10,
      "batch_number": "BATCH002",
      "expired_date": "2026-06-30"
    }
  ],
  "patient_confirmation": true,
  "counseling_provided": true,
  "notes": "Pasien sudah diberikan informasi cara minum obat"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Prescription dispensed successfully",
  "data": {
    "id": 32,
    "status": "completed",
    "dispensed_at": "2024-01-15T10:00:00Z",
    "dispensed_by": {
      "id": 10,
      "name": "Apt. Linda Wijaya"
    },
    "items": [
      {
        "id": 1,
        "quantity_dispensed": 15,
        "is_dispensed": true
      }
    ],
    "patient_confirmation": true,
    "counseling_provided": true
  }
}
```

### Response Error (422) - Insufficient Stock

```json
{
  "success": false,
  "message": "Insufficient stock for some items",
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "details": {
      "items": [
        {
          "medicine_id": 1,
          "medicine_name": "Paracetamol 500mg",
          "requested": 15,
          "available": 10
        }
      ]
    }
  }
}
```

---

### Partial Dispense

Dispense available items only.

```http
POST /api/pharmacy/prescriptions/{id}/partial-dispense
```

---

## Pharmacy Reports

### Stock Report

```http
GET /api/pharmacy/reports/stock
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from_date | date | No | From date |
| to_date | date | No | To date |
| classification | string | No | Filter by classification |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_medicines": 250,
      "total_value": 150000000,
      "low_stock_count": 15,
      "expired_count": 5,
      "expiring_soon_count": 20
    },
    "classifications": [
      {
        "classification": "obat_bebas",
        "count": 100,
        "total_value": 50000000
      }
    ]
  }
}
```

---

### Dispensing Report

```http
GET /api/pharmacy/reports/dispensing
```

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_prescriptions": 156,
      "total_items_dispensed": 420,
      "total_value": 8500000
    },
    "by_date": [
      {
        "date": "2024-01-15",
        "prescriptions": 45,
        "items": 120,
        "value": 2500000
      }
    ]
  }
}
```

## Prescription Types

| Type | Description | Special Requirements |
|------|-------------|---------------------|
| `umum` | General prescription | None |
| `bpjs` | BPJS prescription | Requires BPJS eligibility |
| `narkotika` | Narcotic drugs | Special documentation required |
| `psikotropika` | Psychotropic drugs | Special documentation required |

## Prescription Statuses

| Status | Description |
|--------|-------------|
| `pending` | New prescription, awaiting verification |
| `verified` | Verified by pharmacist |
| `dispensed` | Partially dispensed |
| `completed` | Fully dispensed |
| `cancelled` | Cancelled |

## Medicine Classifications

| Classification | Description |
|---------------|-------------|
| `obat_bebas` | Over-the-counter |
| `obat_bebas_terbatas` | Limited OTC |
| `obat_keras` | Prescription required |
| `narkotika` | Narcotic |
| `psikotropik` | Psychotropic |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `PRESCRIPTION_NOT_FOUND` | 404 | Prescription not found |
| `PRESCRIPTION_ALREADY_DISPENSED` | 422 | Already fully dispensed |
| `MEDICINE_NOT_FOUND` | 404 | Medicine not found |
| `INSUFFICIENT_STOCK` | 422 | Not enough stock |
| `MEDICINE_EXPIRED` | 422 | Medicine has expired |
| `NOT_VERIFIED` | 422 | Prescription not verified |
| `INVALID_DOSAGE` | 422 | Invalid dosage instructions |
| `NARCOTIC_RESTRICTION` | 403 | Narcotic restriction violated |
