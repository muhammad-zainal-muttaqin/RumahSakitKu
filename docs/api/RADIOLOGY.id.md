# API Radiologi

Dokumen ini menjelaskan semua endpoint untuk manajemen pemeriksaan radiologi dalam sistem SIMRS.

## Daftar Isi

- [Daftar Order Radiologi](#daftar-order-radiologi)
- [Buat Order Radiologi](#buat-order-radiologi)
- [Dapatkan Detail Order](#dapatkan-detail-order)
- [Kirim Hasil Pemeriksaan](#kirim-hasil-pemeriksaan)
- [Unggah Gambar](#unggah-gambar)

---

## Daftar Order Radiologi

Mengambil daftar order radiologi dengan filter opsional.

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
| status | string | No | Filter berdasarkan status (menunggu, proses, selesai, validasi) |
| patient_id | integer | No | Filter berdasarkan ID pasien |
| modality | string | No | Filter berdasarkan modalitas (xray, ct, mri, usg, mammografi, fluoroscopy) |
| doctor_id | integer | No | Filter berdasarkan ID dokter pengirim |
| date_from | date | No | Filter dari tanggal (YYYY-MM-DD) |
| date_to | date | No | Filter sampai tanggal (YYYY-MM-DD) |
| priority | string | No | Filter berdasarkan prioritas (normal, urgent, stat) |
| page | integer | No | Nomor halaman (default: 1) |
| per_page | integer | No | Item per halaman (default: 20, max: 100) |

### Response Sukses (200)

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

## Buat Order Radiologi

Membuat order pemeriksaan radiologi baru.

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
| patient_id | integer | Yes | ID pasien |
| doctor_id | integer | Yes | ID dokter pengirim |
| visit_id | integer | No | ID kunjungan (untuk rawat jalan) |
| inpatient_id | integer | No | ID pasien rawat inap (untuk rawat inap) |
| examination_code | string | Yes | Kode jenis pemeriksaan |
| body_part | string | Yes | Bagian tubuh yang diperiksa |
| priority | string | No | Prioritas (normal, urgent, stat) - default: normal |
| contrast_used | boolean | No | Apakah akan menggunakan kontras - default: false |
| contrast_type | string | No | Jenis kontras (jika contrast_used bernilai true) |
| allergy_history | string | No | Riwayat alergi yang diketahui |
| clinical_diagnosis | string | No | Diagnosis klinis |
| indication | string | Yes | Indikasi pemeriksaan |
| notes | string | No | Catatan tambahan |
| previous_exam_id | integer | No | Referensi ke pemeriksaan terkait sebelumnya |

### Contoh Request

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

### Response Sukses (201)

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

### Response Error (422) - Kode Pemeriksaan Tidak Valid

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

## Dapatkan Detail Order

Mengambil informasi detail tentang order radiologi tertentu.

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
| id | integer | ID order radiologi |

### Response Sukses (200)

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

## Kirim Hasil Pemeriksaan

Mengirimkan laporan dan temuan ahli radiologi.

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
| id | integer | ID order radiologi |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| findings | string | Yes | Deskripsi temuan detail |
| report | string | Yes | Laporan lengkap ahli radiologi |
| conclusion | string | Yes | Kesimpulan/diagnosis akhir |
| icd10_code | string | No | Kode ICD-10 jika berlaku |
| recommended_follow_up | string | No | Tindak lanjut yang direkomendasikan |
| critical_findings | boolean | No | Apakah temuan bersifat kritis - default: false |
| reported_by | integer | Yes | ID user ahli radiologi |

### Contoh Request

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

### Response Sukses (200)

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

### Response Error (403) - Tidak Sah

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

### Response Error (422) - Error Validasi

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

## Unggah Gambar

Mengunggah gambar radiologi untuk sebuah order.

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
| id | integer | ID order radiologi |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| images[] | file | Yes | File gambar (jpg, png, dicom) |
| descriptions[] | string | No | Deskripsi untuk setiap gambar |
| uploaded_by | integer | Yes | ID user yang mengunggah gambar |

### Contoh Request (Form Multipart)

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

### Response Sukses (200)

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

### Response Error (422) - File Tidak Valid

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

### Response Error (422) - File Terlalu Besar

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

## Referensi Tipe Data

### Modalitas Pemeriksaan

| Modality | Description |
|----------|-------------|
| xray | Radiografi Sinar-X |
| ct | Computed Tomography |
| mri | Magnetic Resonance Imaging |
| usg | Ultrasonografi |
| mammografi | Mamografi |
| fluoroscopy | Fluoroskopi |
| petct | PET-CT |

### Prioritas

| Priority | Description |
|----------|-------------|
| normal | Prioritas normal |
| urgent | Urgent - dalam 2 jam |
| stat | STAT - segera |

### Status Order

| Status | Description |
|--------|-------------|
| menunggu | Menunggu pemeriksaan |
| proses | Pemeriksaan sedang berlangsung |
| selesai | Pemeriksaan selesai |
| validasi | Laporan telah divalidasi |

### Jenis Kontras

| Type | Description |
|------|-------------|
| iodine | Kontras berbasis Iodin |
| gadolinium | Kontras berbasis Gadolinium |
| barium | Barium sulfat |

## Referensi Kode Error

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `RADIOLOGY_ORDER_NOT_FOUND` | 404 | Order radiologi dengan ID yang dimaksud tidak ditemukan |
| `INVALID_EXAMINATION_CODE` | 422 | Kode jenis pemeriksaan tidak valid |
| `INVALID_FILE` | 422 | Tipe file tidak valid untuk diunggah |
| `FILE_TOO_LARGE` | 422 | Ukuran file melebihi batas maksimum |
| `REPORTING_UNAUTHORIZED` | 403 | User tidak berwenang mengirimkan laporan |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki izin |
| `VALIDATION_ERROR` | 422 | Validasi request gagal |
