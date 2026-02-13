# Pharmacy (Farmasi) API

Dokumen ini menjelaskan semua endpoint untuk manajemen farmasi termasuk resep, stok obat, dan penyediaan obat dalam sistem SIMRS.

## Daftar Isi

- [Endpoint Resep](#endpoint-resep)
- [Endpoint Item Resep](#endpoint-item-resep)
- [Endpoint Stok Obat](#endpoint-stok-obat)
- [Endpoint Penyediaan Obat](#endpoint-penyediaan-obat)
- [Laporan Farmasi](#laporan-farmasi)

---

## Endpoint Resep

### Daftar Resep

Mengambil daftar resep dengan paginasi.

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
| page | integer | No | Nomor halaman (default: 1) |
| per_page | integer | No | Item per halaman (default: 20) |
| patient_id | integer | No | Filter berdasarkan ID pasien |
| doctor_id | integer | No | Filter berdasarkan dokter yang meresepkan |
| status | string | No | Filter berdasarkan status (pending, verified, dispensed, completed, cancelled) |
| prescription_type | string | No | Filter berdasarkan tipe (umum, bpjs, narkotika, psikotropika) |
| priority | string | No | Filter berdasarkan prioritas (normal, urgent, emergency) |
| from_date | date | No | Filter dari tanggal |
| to_date | date | No | Filter sampai tanggal |
| is_verified | boolean | No | Filter berdasarkan status verifikasi |

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

### Buat Resep

Membuat resep baru.

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
| patient_id | integer | Yes | ID Pasien |
| visit_id | integer | Yes | ID Kunjungan |
| medical_record_id | integer | Yes | ID Rekam Medis |
| prescription_date | date | Yes | Tanggal resep (YYYY-MM-DD) |
| prescription_type | string | Yes | Tipe (umum, bpjs, narkotika, psikotropika) |
| priority | string | No | Prioritas (normal, urgent, emergency) - default: normal |
| clinical_indication | string | No | Indikasi klinis/diagnosis |
| allergies | string | No | Alergi yang diketahui |
| notes | string | No | Catatan tambahan |
| items | array | Yes | Item resep |

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

### Ambil Resep

Mengambil informasi detail tentang resep tertentu.

```http
GET /api/pharmacy/prescriptions/{id}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID Resep |

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

### Update Resep

Memperbarui informasi resep (hanya jika belum diberikan).

```http
PUT /api/pharmacy/prescriptions/{id}
```

### Request Body

Sama dengan Buat Resep (semua field opsional).

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

### Verifikasi Resep

Verifikasi resep oleh apoteker.

```http
POST /api/pharmacy/prescriptions/{id}/verify
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| notes | string | No | Catatan verifikasi |

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

## Endpoint Stok Obat

### Daftar Obat

```http
GET /api/pharmacy/medicines
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Nomor halaman |
| per_page | integer | No | Item per halaman |
| search | string | No | Cari berdasarkan nama atau kode |
| classification | string | No | Filter berdasarkan klasifikasi |
| dosage_form | string | No | Filter berdasarkan bentuk sediaan |
| is_active | boolean | No | Filter berdasarkan status aktif |
| low_stock | boolean | No | Tampilkan hanya stok rendah |
| expired | boolean | No | Tampilkan hanya yang kadaluarsa |

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

### Detail Obat

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

### Update Stok Obat

```http
POST /api/pharmacy/medicines/{id}/stock
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| quantity | number | Yes | Jumlah untuk menambah/mengurangi |
| type | string | Yes | in (stok masuk), out (stok keluar) |
| reference | string | No | Nomor referensi/dokumen |
| notes | string | No | Catatan pergerakan stok |
| expired_date | date | No | Tanggal kadaluarsa (untuk stok masuk) |

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

## Endpoint Penyediaan Obat

### Berikan Obat Resep

Memberikan item resep kepada pasien.

```http
POST /api/pharmacy/prescriptions/{id}/dispense
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| items | array | Yes | Item yang akan diberikan |
| patient_confirmation | boolean | Yes | Konfirmasi pasien diterima |
| counseling_provided | boolean | Yes | Konseling diberikan kepada pasien |

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

### Berikan Obat Parsial

Memberikan hanya item yang tersedia.

```http
POST /api/pharmacy/prescriptions/{id}/partial-dispense
```

---

## Laporan Farmasi

### Laporan Stok

```http
GET /api/pharmacy/reports/stock
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from_date | date | No | Dari tanggal |
| to_date | date | No | Sampai tanggal |
| classification | string | No | Filter berdasarkan klasifikasi |

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

### Laporan Penyediaan Obat

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

## Tipe Resep

| Type | Description | Persyaratan Khusus |
|------|-------------|---------------------|
| `umum` | Resep umum | Tidak ada |
| `bpjs` | Resep BPJS | Memerlukan kelayakan BPJS |
| `narkotika` | Obat narkotika | Dokumentasi khusus diperlukan |
| `psikotropika` | Obat psikotropika | Dokumentasi khusus diperlukan |

## Status Resep

| Status | Description |
|--------|-------------|
| `pending` | Resep baru, menunggu verifikasi |
| `verified` | Terverifikasi oleh apoteker |
| `dispensed` | Diberikan sebagian |
| `completed` | Diberikan sepenuhnya |
| `cancelled` | Dibatalkan |

## Klasifikasi Obat

| Classification | Description |
|---------------|-------------|
| `obat_bebas` | Bebas dijual bebas (Over-the-counter) |
| `obat_bebas_terbatas` | Bebas terbatas (Limited OTC) |
| `obat_keras` | Perlu resep dokter (Prescription required) |
| `narkotika` | Narkotika |
| `psikotropik` | Psikotropika |

## Referensi Kode Error

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `PRESCRIPTION_NOT_FOUND` | 404 | Resep tidak ditemukan |
| `PRESCRIPTION_ALREADY_DISPENSED` | 422 | Sudah diberikan sepenuhnya |
| `MEDICINE_NOT_FOUND` | 404 | Obat tidak ditemukan |
| `INSUFFICIENT_STOCK` | 422 | Stok tidak cukup |
| `MEDICINE_EXPIRED` | 422 | Obat sudah kadaluarsa |
| `NOT_VERIFIED` | 422 | Resep belum diverifikasi |
| `INVALID_DOSAGE` | 422 | Instruksi dosis tidak valid |
| `NARCOTIC_RESTRICTION` | 403 | Pelanggaran pembatasan narkotika |
