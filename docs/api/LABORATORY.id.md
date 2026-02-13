# API Laboratorium

Dokumen ini menjelaskan semua endpoint untuk manajemen pemeriksaan laboratorium dalam sistem SIMRS.

## Daftar Isi

- [Daftar Order Lab](#daftar-order-lab)
- [Buat Order Lab](#buat-order-lab)
- [Dapatkan Detail Order](#dapatkan-detail-order)
- [Masukkan Hasil](#masukkan-hasil)
- [Validasi Hasil](#validasi-hasil)

---

## Daftar Order Lab

Mengambil daftar order laboratorium dengan penyaringan opsional.

```http
GET /api/lab/orders
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| status | string | Tidak | Saring berdasarkan status (menunggu, proses, selesai, validasi) |
| patient_id | integer | Tidak | Saring berdasarkan ID pasien |
| doctor_id | integer | Tidak | Saring berdasarkan ID dokter pengirim |
| date_from | date | Tidak | Saring dari tanggal (YYYY-MM-DD) |
| date_to | date | Tidak | Saring sampai tanggal (YYYY-MM-DD) |
| priority | string | Tidak | Saring berdasarkan prioritas (normal, urgent, stat) |
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Jumlah item per halaman (default: 20, maks: 100) |

### Respons Sukses (200)

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

### Respons Error (401)

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

### Respons Error (403)

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

## Buat Order Lab

Membuat order pemeriksaan laboratorium baru.

```http
POST /api/lab/orders
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| patient_id | integer | Ya | ID pasien |
| doctor_id | integer | Ya | ID dokter pengirim |
| visit_id | integer | Tidak | ID kunjungan (untuk rawat jalan) |
| inpatient_id | integer | Tidak | ID rawat inap (untpasien rawat inap) |
| priority | string | Tidak | Prioritas (normal, urgent, stat) - default: normal |
| clinical_diagnosis | string | Tidak | Catatan diagnosis klinis |
| notes | string | Tidak | Catatan tambahan |
| tests | array | Ya | Daftar item pemeriksaan |
| tests[].test_id | integer | Ya | ID pemeriksaan |
| tests[].notes | string | Tidak | Catatan khusus untuk pemeriksaan ini |

### Contoh Permintaan

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

### Respons Sukses (201)

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

### Respons Error (401)

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

### Respons Error (422) - ID Pemeriksaan Tidak Valid

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

## Dapatkan Detail Order

Mengambil informasi detail tentang order lab tertentu.

```http
GET /api/lab/orders/{id}
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| id | integer | ID order lab |

### Respons Sukses (200)

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

### Respons Error (401)

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

### Respons Error (404)

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

## Masukkan Hasil

Memasukkan hasil pemeriksaan untuk order lab.

```http
PUT /api/lab/orders/{id}/results
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| id | integer | ID order lab |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| results | array | Ya | Daftar hasil pemeriksaan |
| results[].test_item_id | integer | Ya | ID item order lab |
| results[].value | string | Ya | Nilai hasil pemeriksaan |
| results[].notes | string | Tidak | Catatan untuk hasil tertentu |
| entered_by | integer | Ya | ID user yang memasukkan hasil |

### Contoh Permintaan

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

### Respons Sukses (200)

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

### Respons Error (401)

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

### Respons Error (404)

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

### Respons Error (422) - Item Pemeriksaan Tidak Valid

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

## Validasi Hasil

Memvalidasi hasil pemeriksaan yang telah dimasukkan.

```http
PUT /api/lab/orders/{id}/validate
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| id | integer | ID order lab |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| validated_by | integer | Ya | ID user yang memvalidasi hasil |
| notes | string | Tidak | Catatan validasi |

### Contoh Permintaan

```json
{
  "validated_by": 10,
  "notes": "Hasil normal, hanya HGB sedikit rendah"
}
```

### Respons Sukses (200)

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

### Respons Error (401)

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

### Respons Error (404)

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

### Respons Error (422) - Tidak Ada Hasil untuk Divalidasi

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

### Respons Error (403) - Hak Akses Tidak Memadai

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

## Referensi Tipe Data

### Prioritas Pemeriksaan

| Prioritas | Deskripsi |
|-----------|-------------|
| normal | Prioritas normal |
| urgent | Urgent - proses dalam 2 jam |
| stat | STAT - proses segera |

### Status Order

| Status | Deskripsi |
|--------|-------------|
| menunggu | Menunggu sampel |
| proses | Pengolahan sampel |
| selesai | Hasil dimasukkan |
| validasi | Hasil tervalidasi |

### Flag Hasil

| Flag | Deskripsi |
|------|-------------|
| normal | Dalam rentang referensi |
| low | Di bawah rentang referensi |
| high | Di atas rentang referensi |
| critical | Nilai kritis - memerlukan perhatian segera |

### Kategori Pemeriksaan

| Kategori | Deskripsi |
|----------|-------------|
| Hematologi | Hematology |
| Kimia Klinik | Clinical Chemistry |
| Urinalisa | Urinalysis |
| Mikrobiologi | Microbiology |
| Imunologi | Immunology |
| Serologi | Serology |
| Parasitologi | Parasitology |

## Referensi Kode Error

| Kode | Status HTTP | Deskripsi |
|------|-------------|-------------|
| `LAB_ORDER_NOT_FOUND` | 404 | Order lab dengan ID yang ditentukan tidak ditemukan |
| `INVALID_TEST_ITEM` | 422 | ID item pemeriksaan tidak ditemukan dalam order |
| `NO_RESULTS` | 422 | Tidak ada hasil yang dimasukkan untuk divalidasi |
| `VALIDATION_UNAUTHORIZED` | 403 | User tidak dapat memvalidasi hasil |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki izin |
| `VALIDATION_ERROR` | 422 | Validasi permintaan gagal |
