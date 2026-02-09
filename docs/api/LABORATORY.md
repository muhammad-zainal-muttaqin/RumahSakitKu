# Laboratory API

This document describes all endpoints for laboratory test management in the SIMRS system.

## Table of Contents

- [List Lab Orders](#list-lab-orders)
- [Create Lab Order](#create-lab-order)
- [Get Order Details](#get-order-details)
- [Enter Results](#enter-results)
- [Validate Results](#validate-results)

---

## List Lab Orders

Retrieve a list of laboratory orders with optional filtering.

```http
GET /api/lab/orders
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status (menunggu, proses, selesai, validasi) |
| patient_id | integer | No | Filter by patient ID |
| doctor_id | integer | No | Filter by requesting doctor ID |
| date_from | date | No | Filter from date (YYYY-MM-DD) |
| date_to | date | No | Filter to date (YYYY-MM-DD) |
| priority | string | No | Filter by priority (normal, urgent, stat) |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 452,
      "order_number": "LAB-20240115-0452",
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001",
        "gender": "L",
        "age": 34,
        "date_of_birth": "1990-01-01"
      },
      "requesting_doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson",
        "specialization": "Dokter Umum"
      },
      "visit": {
        "id": 45,
        "visit_number": "RJ-20240115-0045",
        "visit_type": "rawat_jalan"
      },
      "inpatient": null,
      "order_date": "2024-01-15",
      "order_time": "09:30:00",
      "priority": "normal",
      "status": "menunggu",
      "tests": [
        {
          "id": 1,
          "code": "HGB",
          "name": "Hemoglobin",
          "category": "Hematologi"
        },
        {
          "id": 2,
          "code": "LEU",
          "name": "Leukosit",
          "category": "Hematologi"
        }
      ],
      "total_tests": 2,
      "clinical_diagnosis": "Suspected anemia",
      "sample_info": {
        "sample_type": "Darah",
        "sample_number": null,
        "collected_at": null,
        "collected_by": null
      },
      "created_at": "2024-01-15T09:30:00Z",
      "updated_at": "2024-01-15T09:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 15,
    "per_page": 20,
    "total": 285,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/lab/orders?page=1",
    "last": "/api/lab/orders?page=15",
    "prev": null,
    "next": "/api/lab/orders?page=2"
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
  "message": "You do not have permission to view lab orders",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Create Lab Order

Create a new laboratory test order.

```http
POST /api/lab/orders
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
| doctor_id | integer | Yes | Requesting doctor ID |
| visit_id | integer | No | Visit ID (for outpatient) |
| inpatient_id | integer | No | Inpatient ID (for inpatient) |
| priority | string | No | Priority (normal, urgent, stat) - default: normal |
| clinical_diagnosis | string | No | Clinical diagnosis notes |
| notes | string | No | Additional notes |
| tests | array | Yes | List of test items |
| tests[].test_id | integer | Yes | Test ID |
| tests[].notes | string | No | Specific notes for this test |

### Request Example

```json
{
  "patient_id": 23,
  "doctor_id": 5,
  "visit_id": 45,
  "priority": "normal",
  "clinical_diagnosis": "Suspected anemia",
  "notes": "Pasien mengeluh pusing dan lemas",
  "tests": [
    {
      "test_id": 1,
      "notes": "Puasa 8 jam"
    },
    {
      "test_id": 2
    },
    {
      "test_id": 5,
      "notes": "Puasa 8 jam"
    },
    {
      "test_id": 12
    }
  ]
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Lab order created successfully",
  "data": {
    "id": 453,
    "order_number": "LAB-20240115-0453",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001",
      "gender": "L",
      "age": 34
    },
    "requesting_doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045"
    },
    "order_date": "2024-01-15",
    "order_time": "10:15:00",
    "priority": "normal",
    "priority_display": "Normal",
    "status": "menunggu",
    "tests": [
      {
        "id": 1,
        "lab_order_id": 453,
        "test": {
          "id": 1,
          "code": "HGB",
          "name": "Hemoglobin",
          "category": "Hematologi",
          "unit": "g/dL",
          "reference_range": "Male: 13.5-17.5, Female: 12.0-16.0"
        },
        "status": "menunggu",
        "notes": "Puasa 8 jam"
      },
      {
        "id": 2,
        "lab_order_id": 453,
        "test": {
          "id": 2,
          "code": "LEU",
          "name": "Leukosit",
          "category": "Hematologi",
          "unit": "ribu/µL",
          "reference_range": "4.5-11.0"
        },
        "status": "menunggu",
        "notes": null
      },
      {
        "id": 3,
        "lab_order_id": 453,
        "test": {
          "id": 5,
          "code": "GLU",
          "name": "Glukosa Puasa",
          "category": "Kimia Klinik",
          "unit": "mg/dL",
          "reference_range": "70-100"
        },
        "status": "menunggu",
        "notes": "Puasa 8 jam"
      },
      {
        "id": 4,
        "lab_order_id": 453,
        "test": {
          "id": 12,
          "code": "HCT",
          "name": "Hematokrit",
          "category": "Hematologi",
          "unit": "%",
          "reference_range": "Male: 38.8-50.0, Female: 34.9-44.5"
        },
        "status": "menunggu",
        "notes": null
      }
    ],
    "total_tests": 4,
    "clinical_diagnosis": "Suspected anemia",
    "notes": "Pasien mengeluh pusing dan lemas",
    "created_at": "2024-01-15T10:15:00Z"
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

### Response Error (422) - Invalid Test ID

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "tests": ["Invalid test ID: 999"]
    }
  }
}
```

---

## Get Order Details

Retrieve detailed information about a specific lab order.

```http
GET /api/lab/orders/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Lab order ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 453,
    "order_number": "LAB-20240115-0453",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001",
      "gender": "L",
      "age": 34,
      "date_of_birth": "1990-01-01",
      "phone": "08123456789"
    },
    "requesting_doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson",
      "specialization": "Dokter Umum"
    },
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "visit_type": "rawat_jalan"
    },
    "order_date": "2024-01-15",
    "order_time": "10:15:00",
    "priority": "normal",
    "priority_display": "Normal",
    "status": "selesai",
    "status_display": "Selesai",
    "clinical_diagnosis": "Suspected anemia",
    "notes": "Pasien mengeluh pusing dan lemas",
    "tests": [
      {
        "id": 1,
        "test": {
          "id": 1,
          "code": "HGB",
          "name": "Hemoglobin",
          "category": "Hematologi",
          "unit": "g/dL",
          "reference_range": "Male: 13.5-17.5, Female: 12.0-16.0",
          "method": "Cyanmethemoglobin"
        },
        "result": {
          "value": "11.2",
          "numeric_value": 11.2,
          "flag": "low",
          "flag_display": "Rendah",
          "status": "valid",
          "entered_at": "2024-01-15T11:30:00Z",
          "entered_by": {
            "id": 15,
            "name": "Lab Technician A"
          },
          "validated_at": "2024-01-15T12:00:00Z",
          "validated_by": {
            "id": 10,
            "name": "Dr. Lab Specialist"
          }
        },
        "notes": "Puasa 8 jam",
        "status": "selesai"
      },
      {
        "id": 2,
        "test": {
          "id": 2,
          "code": "LEU",
          "name": "Leukosit",
          "category": "Hematologi",
          "unit": "ribu/µL",
          "reference_range": "4.5-11.0",
          "method": "Impedance"
        },
        "result": {
          "value": "7.8",
          "numeric_value": 7.8,
          "flag": "normal",
          "flag_display": "Normal",
          "status": "valid",
          "entered_at": "2024-01-15T11:30:00Z",
          "entered_by": {
            "id": 15,
            "name": "Lab Technician A"
          },
          "validated_at": "2024-01-15T12:00:00Z",
          "validated_by": {
            "id": 10,
            "name": "Dr. Lab Specialist"
          }
        },
        "notes": null,
        "status": "selesai"
      }
    ],
    "sample_info": {
      "sample_type": "Darah",
      "sample_number": "S-20240115-0892",
      "collected_at": "2024-01-15T10:20:00Z",
      "collected_by": {
        "id": 12,
        "name": "Nurse Staff"
      },
      "received_at": "2024-01-15T10:45:00Z",
      "received_by": {
        "id": 15,
        "name": "Lab Technician A"
      }
    },
    "timeline": [
      {
        "timestamp": "2024-01-15T10:15:00Z",
        "event": "Order created",
        "user": "Dr. Sarah Johnson"
      },
      {
        "timestamp": "2024-01-15T10:20:00Z",
        "event": "Sample collected",
        "user": "Nurse Staff"
      },
      {
        "timestamp": "2024-01-15T10:45:00Z",
        "event": "Sample received",
        "user": "Lab Technician A"
      },
      {
        "timestamp": "2024-01-15T11:30:00Z",
        "event": "Results entered",
        "user": "Lab Technician A"
      },
      {
        "timestamp": "2024-01-15T12:00:00Z",
        "event": "Results validated",
        "user": "Dr. Lab Specialist"
      }
    ],
    "created_at": "2024-01-15T10:15:00Z",
    "updated_at": "2024-01-15T12:00:00Z"
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
  "message": "Lab order not found",
  "error": {
    "code": "LAB_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

---

## Enter Results

Enter test results for a lab order.

```http
PUT /api/lab/orders/{id}/results
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
| id | integer | Lab order ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| results | array | Yes | List of test results |
| results[].test_item_id | integer | Yes | Lab order test item ID |
| results[].value | string | Yes | Test result value |
| results[].notes | string | No | Notes for specific result |
| entered_by | integer | Yes | User ID entering results |

### Request Example

```json
{
  "entered_by": 15,
  "results": [
    {
      "test_item_id": 1,
      "value": "11.2",
      "notes": "Sedikit rendah dari normal"
    },
    {
      "test_item_id": 2,
      "value": "7.8"
    },
    {
      "test_item_id": 3,
      "value": "95",
      "notes": "Puasa 10 jam"
    },
    {
      "test_item_id": 4,
      "value": "36.5"
    }
  ]
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Results entered successfully",
  "data": {
    "id": 453,
    "order_number": "LAB-20240115-0453",
    "status": "selesai",
    "results": [
      {
        "test_item_id": 1,
        "test_name": "Hemoglobin",
        "value": "11.2",
        "flag": "low",
        "flag_display": "Rendah",
        "reference_range": "Male: 13.5-17.5, Female: 12.0-16.0",
        "status": "pending_validation"
      },
      {
        "test_item_id": 2,
        "test_name": "Leukosit",
        "value": "7.8",
        "flag": "normal",
        "flag_display": "Normal",
        "reference_range": "4.5-11.0",
        "status": "pending_validation"
      },
      {
        "test_item_id": 3,
        "test_name": "Glukosa Puasa",
        "value": "95",
        "flag": "normal",
        "flag_display": "Normal",
        "reference_range": "70-100",
        "status": "pending_validation"
      },
      {
        "test_item_id": 4,
        "test_name": "Hematokrit",
        "value": "36.5",
        "flag": "normal",
        "flag_display": "Normal",
        "reference_range": "Male: 38.8-50.0, Female: 34.9-44.5",
        "status": "pending_validation"
      }
    ],
    "entered_by": {
      "id": 15,
      "name": "Lab Technician A"
    },
    "entered_at": "2024-01-15T11:30:00Z",
    "validation_required": true
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
  "message": "Lab order not found",
  "error": {
    "code": "LAB_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Test Item

```json
{
  "success": false,
  "message": "Invalid test item ID",
  "error": {
    "code": "INVALID_TEST_ITEM",
    "details": {
      "test_item_id": 999,
      "message": "Test item not found in this order"
    }
  }
}
```

---

## Validate Results

Validate entered test results.

```http
PUT /api/lab/orders/{id}/validate
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
| id | integer | Lab order ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| validated_by | integer | Yes | User ID validating results |
| notes | string | No | Validation notes |

### Request Example

```json
{
  "validated_by": 10,
  "notes": "Hasil normal, hanya HGB sedikit rendah"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Results validated successfully",
  "data": {
    "id": 453,
    "order_number": "LAB-20240115-0453",
    "status": "validasi",
    "status_display": "Tervalidasi",
    "validation": {
      "validated_at": "2024-01-15T12:00:00Z",
      "validated_by": {
        "id": 10,
        "name": "Dr. Lab Specialist",
        "role": "Dokter Lab"
      },
      "notes": "Hasil normal, hanya HGB sedikit rendah"
    },
    "results": [
      {
        "test_item_id": 1,
        "test_name": "Hemoglobin",
        "value": "11.2",
        "flag": "low",
        "flag_display": "Rendah",
        "status": "valid"
      },
      {
        "test_item_id": 2,
        "test_name": "Leukosit",
        "value": "7.8",
        "flag": "normal",
        "flag_display": "Normal",
        "status": "valid"
      }
    ],
    "report_generated": true,
    "report_url": "/api/lab/orders/453/report"
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
  "message": "Lab order not found",
  "error": {
    "code": "LAB_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - No Results to Validate

```json
{
  "success": false,
  "message": "No results to validate",
  "error": {
    "code": "NO_RESULTS",
    "details": {
      "message": "Please enter results before validation"
    }
  }
}
```

### Response Error (403) - Insufficient Privileges

```json
{
  "success": false,
  "message": "Only authorized doctors can validate lab results",
  "error": {
    "code": "VALIDATION_UNAUTHORIZED",
    "details": {}
  }
}
```

## Data Types Reference

### Test Priority

| Priority | Description |
|----------|-------------|
| normal | Normal priority |
| urgent | Urgent - process within 2 hours |
| stat | STAT - process immediately |

### Order Status

| Status | Description |
|--------|-------------|
| menunggu | Waiting for sample |
| proses | Sample processing |
| selesai | Results entered |
| validasi | Results validated |

### Result Flag

| Flag | Description |
|------|-------------|
| normal | Within reference range |
| low | Below reference range |
| high | Above reference range |
| critical | Critical value - requires immediate attention |

### Test Categories

| Category | Description |
|----------|-------------|
| Hematologi | Hematology |
| Kimia Klinik | Clinical Chemistry |
| Urinalisa | Urinalysis |
| Mikrobiologi | Microbiology |
| Imunologi | Immunology |
| Serologi | Serology |
| Parasitologi | Parasitology |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `LAB_ORDER_NOT_FOUND` | 404 | Lab order with specified ID not found |
| `INVALID_TEST_ITEM` | 422 | Test item ID not found in order |
| `NO_RESULTS` | 422 | No results entered for validation |
| `VALIDATION_UNAUTHORIZED` | 403 | User cannot validate results |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission |
| `VALIDATION_ERROR` | 422 | Request validation failed |
