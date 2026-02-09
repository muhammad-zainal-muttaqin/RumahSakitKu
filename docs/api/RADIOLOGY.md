# Radiology API

This document describes all endpoints for radiology examination management in the SIMRS system.

## Table of Contents

- [List Radiology Orders](#list-radiology-orders)
- [Create Radiology Order](#create-radiology-order)
- [Get Order Details](#get-order-details)
- [Submit Results](#submit-results)
- [Upload Images](#upload-images)

---

## List Radiology Orders

Retrieve a list of radiology orders with optional filtering.

```http
GET /api/radiology/orders
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
| modality | string | No | Filter by modality (xray, ct, mri, usg, mammografi, fluoroscopy) |
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
      "id": 234,
      "order_number": "RAD-20240115-0234",
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
      "examination": {
        "modality": "xray",
        "modality_display": "X-Ray",
        "body_part": "Thorax",
        "examination_type": "Chest PA",
        "examination_code": "XR-CHEST-PA"
      },
      "contrast": {
        "used": false,
        "type": null,
        "allergy_history": null
      },
      "priority": "normal",
      "status": "menunggu",
      "clinical_diagnosis": "Suspected pneumonia",
      "indication": "Batuk dan sesak napas 3 hari",
      "notes": null,
      "created_at": "2024-01-15T09:30:00Z",
      "updated_at": "2024-01-15T09:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 12,
    "per_page": 20,
    "total": 230,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/radiology/orders?page=1",
    "last": "/api/radiology/orders?page=12",
    "prev": null,
    "next": "/api/radiology/orders?page=2"
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
  "message": "You do not have permission to view radiology orders",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Create Radiology Order

Create a new radiology examination order.

```http
POST /api/radiology/orders
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
| examination_code | string | Yes | Examination type code |
| body_part | string | Yes | Body part to examine |
| priority | string | No | Priority (normal, urgent, stat) - default: normal |
| contrast_used | boolean | No | Whether contrast will be used - default: false |
| contrast_type | string | No | Contrast type (if contrast_used is true) |
| allergy_history | string | No | Known allergy history |
| clinical_diagnosis | string | No | Clinical diagnosis |
| indication | string | Yes | Examination indication |
| notes | string | No | Additional notes |
| previous_exam_id | integer | No | Reference to previous related examination |

### Request Example

```json
{
  "patient_id": 23,
  "doctor_id": 5,
  "visit_id": 45,
  "examination_code": "XR-CHEST-PA",
  "body_part": "Thorax",
  "priority": "normal",
  "contrast_used": false,
  "clinical_diagnosis": "Suspected pneumonia",
  "indication": "Batuk dan sesak napas 3 hari",
  "notes": "Pasien mengeluh nyeri dada saat batuk"
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Radiology order created successfully",
  "data": {
    "id": 235,
    "order_number": "RAD-20240115-0235",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001",
      "gender": "L",
      "age": 34
    },
    "requesting_doctor": {
      "id": 5,
      "name": "Dr. Sarah Johnson",
      "specialization": "Dokter Umum"
    },
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045"
    },
    "examination": {
      "modality": "xray",
      "modality_display": "X-Ray",
      "body_part": "Thorax",
      "examination_type": "Chest PA",
      "examination_code": "XR-CHEST-PA",
      "description": "Chest X-Ray Posteroanterior"
    },
    "contrast": {
      "used": false,
      "type": null,
      "allergy_history": null
    },
    "priority": "normal",
    "priority_display": "Normal",
    "status": "menunggu",
    "clinical_diagnosis": "Suspected pneumonia",
    "indication": "Batuk dan sesak napas 3 hari",
    "notes": "Pasien mengeluh nyeri dada saat batuk",
    "images": [],
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

### Response Error (422) - Invalid Examination Code

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "examination_code": ["Invalid examination code"]
    }
  }
}
```

---

## Get Order Details

Retrieve detailed information about a specific radiology order.

```http
GET /api/radiology/orders/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Radiology order ID |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 235,
    "order_number": "RAD-20240115-0235",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001",
      "gender": "L",
      "age": 34,
      "date_of_birth": "1990-01-01",
      "phone": "08123456789",
      "weight_kg": 70,
      "height_cm": 175
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
    "examination": {
      "modality": "xray",
      "modality_display": "X-Ray",
      "body_part": "Thorax",
      "examination_type": "Chest PA",
      "examination_code": "XR-CHEST-PA",
      "description": "Chest X-Ray Posteroanterior",
      "preparation_instructions": "Lepaskan perhiasan dan pakaian logam di area dada"
    },
    "contrast": {
      "used": false,
      "type": null,
      "allergy_history": null
    },
    "priority": "normal",
    "priority_display": "Normal",
    "status": "selesai",
    "status_display": "Selesai",
    "clinical_diagnosis": "Suspected pneumonia",
    "indication": "Batuk dan sesak napas 3 hari",
    "notes": "Pasien mengeluh nyeri dada saat batuk",
    "schedule": {
      "scheduled_at": "2024-01-15T11:00:00Z",
      "room": "X-Ray Room 1",
      "technician": {
        "id": 20,
        "name": "Rad Technician B"
      }
    },
    "result": {
      "report": "Cor dan mediastinum dalam batas normal. Tidak terlihat infiltrat atau konsolidasi. Sulci costophrenicus tajam. Os costae intact. Kesimpulan: Tidak ditemukan kelainan signifikan pada foto thoraks.",
      "findings": "Normal heart size and mediastinum. Clear lung fields. No infiltrates or consolidation seen. Sharp costophrenic angles. Intact ribs.",
      "conclusion": "Normal chest X-ray findings",
      "icd10_code": null,
      "radiologist": {
        "id": 25,
        "name": "Dr. Radiologist Specialist",
        "specialization": "Radiologi"
      },
      "reported_at": "2024-01-15T14:30:00Z"
    },
    "images": [
      {
        "id": 1,
        "file_name": "XR-20240115-0235-001.jpg",
        "original_url": "/storage/radiology/XR-20240115-0235-001.jpg",
        "thumbnail_url": "/storage/radiology/thumbs/XR-20240115-0235-001.jpg",
        "file_size": 2048576,
        "uploaded_at": "2024-01-15T11:15:00Z"
      }
    ],
    "timeline": [
      {
        "timestamp": "2024-01-15T10:30:00Z",
        "event": "Order created",
        "user": "Dr. Sarah Johnson"
      },
      {
        "timestamp": "2024-01-15T11:00:00Z",
        "event": "Examination scheduled",
        "user": "System"
      },
      {
        "timestamp": "2024-01-15T11:15:00Z",
        "event": "Image acquired",
        "user": "Rad Technician B"
      },
      {
        "timestamp": "2024-01-15T14:30:00Z",
        "event": "Report finalized",
        "user": "Dr. Radiologist Specialist"
      }
    ],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T14:30:00Z"
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
  "message": "Radiology order not found",
  "error": {
    "code": "RADIOLOGY_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

---

## Submit Results

Submit radiologist's report and findings.

```http
POST /api/radiology/orders/{id}/results
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
| id | integer | Radiology order ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| findings | string | Yes | Detailed findings description |
| report | string | Yes | Full radiologist report |
| conclusion | string | Yes | Final conclusion/diagnosis |
| icd10_code | string | No | ICD-10 code if applicable |
| recommended_follow_up | string | No | Recommended follow-up |
| critical_findings | boolean | No | Whether findings are critical - default: false |
| reported_by | integer | Yes | Radiologist user ID |

### Request Example

```json
{
  "findings": "Cor dan mediastinum dalam batas normal. Terlihat infiltrat patchy di lobus inferior paru kanan. Sulci costophrenicus kanan tumpul. Os costae intact.",
  "report": "Cor dan mediastinum dalam batas normal. Terlihat infiltrat patchy di lobus inferior paru kanan yang mengindikasikan pneumonia. Sulci costophrenicus kanan tumpul menunjukkan efusi pleura minimal. Os costae intact.",
  "conclusion": "Pneumonia lobus inferior paru kanan dengan efusi pleura minimal",
  "icd10_code": "J18.1",
  "recommended_follow_up": "Kontrol 1 minggu, pertimbangkan CT jika tidak membaik",
  "critical_findings": false,
  "reported_by": 25
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Results submitted successfully",
  "data": {
    "id": 235,
    "order_number": "RAD-20240115-0235",
    "status": "selesai",
    "result": {
      "findings": "Cor dan mediastinum dalam batas normal. Terlihat infiltrat patchy di lobus inferior paru kanan. Sulci costophrenicus kanan tumpul. Os costae intact.",
      "report": "Cor dan mediastinum dalam batas normal. Terlihat infiltrat patchy di lobus inferior paru kanan yang mengindikasikan pneumonia. Sulci costophrenicus kanan tumpul menunjukkan efusi pleura minimal. Os costae intact.",
      "conclusion": "Pneumonia lobus inferior paru kanan dengan efusi pleura minimal",
      "icd10_code": "J18.1",
      "icd10_description": "Lobar pneumonia, unspecified",
      "recommended_follow_up": "Kontrol 1 minggu, pertimbangkan CT jika tidak membaik",
      "critical_findings": false,
      "radiologist": {
        "id": 25,
        "name": "Dr. Radiologist Specialist",
        "specialization": "Radiologi"
      },
      "reported_at": "2024-01-15T14:30:00Z"
    },
    "report_url": "/api/radiology/orders/235/report"
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
  "message": "Radiology order not found",
  "error": {
    "code": "RADIOLOGY_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (403) - Unauthorized

```json
{
  "success": false,
  "message": "Only authorized radiologists can submit reports",
  "error": {
    "code": "REPORTING_UNAUTHORIZED",
    "details": {}
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
      "findings": ["The findings field is required"],
      "conclusion": ["The conclusion field is required"]
    }
  }
}
```

---

## Upload Images

Upload radiology images for an order.

```http
POST /api/radiology/orders/{id}/upload
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | multipart/form-data |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Radiology order ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| images[] | file | Yes | Image files (jpg, png, dicom) |
| descriptions[] | string | No | Descriptions for each image |
| uploaded_by | integer | Yes | User ID uploading images |

### Request Example (Multipart Form)

```
Content-Type: multipart/form-data; boundary=----FormBoundary7MA4YWxkTrZu0gW

------FormBoundary7MA4YWxkTrZu0gW
Content-Disposition: form-data; name="images[]"; filename="chest-xray-001.jpg"
Content-Type: image/jpeg

[Binary image data]
------FormBoundary7MA4YWxkTrZu0gW
Content-Disposition: form-data; name="descriptions[]"

Chest PA view
------FormBoundary7MA4YWxkTrZu0gW
Content-Disposition: form-data; name="uploaded_by"

20
------FormBoundary7MA4YWxkTrZu0gW--
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": {
    "id": 235,
    "order_number": "RAD-20240115-0235",
    "uploaded_images": [
      {
        "id": 1,
        "file_name": "XR-20240115-0235-001.jpg",
        "description": "Chest PA view",
        "original_url": "/storage/radiology/XR-20240115-0235-001.jpg",
        "thumbnail_url": "/storage/radiology/thumbs/XR-20240115-0235-001.jpg",
        "file_size": 2048576,
        "mime_type": "image/jpeg",
        "dimensions": {
          "width": 2048,
          "height": 2048
        },
        "uploaded_at": "2024-01-15T11:15:00Z",
        "uploaded_by": {
          "id": 20,
          "name": "Rad Technician B"
        }
      }
    ],
    "total_images": 1
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
  "message": "Radiology order not found",
  "error": {
    "code": "RADIOLOGY_ORDER_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid File

```json
{
  "success": false,
  "message": "Invalid file upload",
  "error": {
    "code": "INVALID_FILE",
    "details": {
      "file_name": "document.pdf",
      "message": "Only JPG, PNG, and DICOM files are allowed"
    }
  }
}
```

### Response Error (422) - File Too Large

```json
{
  "success": false,
  "message": "File size exceeds limit",
  "error": {
    "code": "FILE_TOO_LARGE",
    "details": {
      "file_name": "scan.dcm",
      "max_size": "50MB",
      "actual_size": "65MB"
    }
  }
}
```

## Data Types Reference

### Examination Modality

| Modality | Description |
|----------|-------------|
| xray | X-Ray Radiography |
| ct | Computed Tomography |
| mri | Magnetic Resonance Imaging |
| usg | Ultrasound |
| mammografi | Mammography |
| fluoroscopy | Fluoroscopy |
| petct | PET-CT |

### Priority

| Priority | Description |
|----------|-------------|
| normal | Normal priority |
| urgent | Urgent - within 2 hours |
| stat | STAT - immediate |

### Order Status

| Status | Description |
|--------|-------------|
| menunggu | Waiting for examination |
| proses | Examination in progress |
| selesai | Examination completed |
| validasi | Report validated |

### Contrast Types

| Type | Description |
|------|-------------|
| iodine | Iodine-based contrast |
| gadolinium | Gadolinium-based contrast |
| barium | Barium sulfate |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `RADIOLOGY_ORDER_NOT_FOUND` | 404 | Radiology order with specified ID not found |
| `INVALID_EXAMINATION_CODE` | 422 | Invalid examination type code |
| `INVALID_FILE` | 422 | Invalid file type for upload |
| `FILE_TOO_LARGE` | 422 | File size exceeds maximum limit |
| `REPORTING_UNAUTHORIZED` | 403 | User not authorized to submit reports |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission |
| `VALIDATION_ERROR` | 422 | Request validation failed |
