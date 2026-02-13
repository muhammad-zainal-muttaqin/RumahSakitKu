# API Billing (Penagihan)

Dokumen ini menjelaskan semua endpoint untuk billing, faktur, dan manajemen pembayaran dalam sistem SIMRS.

## Daftar Isi

- [Endpoint Faktur](#endpoint-faktur)
- [Endpoint Pembayaran](#endpoint-pembayaran)
- [Endpoint Laporan](#endpoint-laporan)

---

## Endpoint Faktur

### Daftar Faktur

Mengambil daftar faktur dengan paginasi.

```http
GET /api/billing/invoices
```

### Header

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Nomor halaman (default: 1) |
| per_page | integer | No | Jumlah item per halaman (default: 20) |
| patient_id | integer | No | Filter berdasarkan ID pasien |
| visit_id | integer | No | Filter berdasarkan ID kunjungan |
| status | string | No | Filter berdasarkan status (pending, paid, cancelled, refunded) |
| payment_status | string | No | Filter berdasarkan status pembayaran (unpaid, partial, paid) |
| from_date | date | No | Filter dari tanggal (YYYY-MM-DD) |
| to_date | date | No | Filter sampai tanggal (YYYY-MM-DD) |
| overdue | boolean | No | Tampilkan hanya faktur yang lewat jatuh tempo |
| with_balance | boolean | No | Tampilkan hanya faktur dengan sisa saldo |
| invoice_number | string | No | Cari berdasarkan nomor faktur |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "invoice_number": "INV-20240115-0045",
      "visit": {
        "id": 45,
        "visit_number": "RJ-20240115-0045",
        "visit_date": "2024-01-15",
        "polyclinic": {
          "id": 1,
          "name": "Poli Umum"
        }
      },
      "patient": {
        "id": 1,
        "medical_record_number": "20240101-0001",
        "name": "John Doe",
        "nik": "1234567890123456"
      },
      "invoice_date": "2024-01-15",
      "due_date": "2024-01-15",
      "subtotal": 125000.00,
      "discount_amount": 0.00,
      "tax_amount": 12500.00,
      "total_amount": 137500.00,
      "paid_amount": 137500.00,
      "balance_due": 0.00,
      "status": "paid",
      "payment_status": "paid",
      "insurance_claim_status": null,
      "is_paid": true,
      "is_overdue": false,
      "payment_progress": 100.00,
      "formatted_total": "Rp 137.500",
      "created_at": "2024-01-15T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 185,
    "summary": {
      "total_invoices": 185,
      "total_amount": 27500000.00,
      "total_paid": 25000000.00,
      "total_outstanding": 2500000.00,
      "overdue_count": 12
    }
  }
}
```

---

### Buat Faktur

Membuat faktur baru untuk kunjungan.

```http
POST /api/billing/invoices
```

### Header

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| visit_id | integer | Yes | ID Kunjungan |
| patient_id | integer | Yes | ID Pasien |
| invoice_date | date | Yes | Tanggal faktur (YYYY-MM-DD) |
| due_date | date | Yes | Tanggal jatuh tempo (YYYY-MM-DD) |
| items | array | Yes | Item baris faktur |
| discount_amount | decimal | No | Jumlah diskon (default: 0) |
| tax_amount | decimal | No | Jumlah pajak (default: 0) |
| notes | string | No | Catatan faktur |

### Contoh Request

```json
{
  "visit_id": 45,
  "patient_id": 1,
  "invoice_date": "2024-01-15",
  "due_date": "2024-01-15",
  "items": [
    {
      "description": "Jasa Dokter",
      "quantity": 1,
      "unit_price": 50000.00,
      "discount": 0
    },
    {
      "description": "Tindakan Medis",
      "quantity": 1,
      "unit_price": 25000.00,
      "discount": 0
    },
    {
      "description": "Obat",
      "quantity": 1,
      "unit_price": 50000.00,
      "discount": 0
    }
  ],
  "discount_amount": 0,
  "tax_amount": 12500,
  "notes": "Invoice rawat jalan"
}
```

### Respons Sukses (201)

```json
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "id": 45,
    "invoice_number": "INV-20240115-0045",
    "visit_id": 45,
    "patient_id": 1,
    "invoice_date": "2024-01-15",
    "due_date": "2024-01-15",
    "subtotal": 125000.00,
    "discount_amount": 0.00,
    "tax_amount": 12500.00,
    "total_amount": 137500.00,
    "paid_amount": 0.00,
    "balance_due": 137500.00,
    "status": "pending",
    "payment_status": "unpaid",
    "items": [
      {
        "id": 1,
        "description": "Jasa Dokter",
        "quantity": 1,
        "unit_price": 50000.00,
        "discount": 0,
        "total": 50000.00
      },
      {
        "id": 2,
        "description": "Tindakan Medis",
        "quantity": 1,
        "unit_price": 25000.00,
        "discount": 0,
        "total": 25000.00
      },
      {
        "id": 3,
        "description": "Obat",
        "quantity": 1,
        "unit_price": 50000.00,
        "discount": 0,
        "total": 50000.00
      }
    ],
    "notes": "Invoice rawat jalan",
    "is_paid": false,
    "is_overdue": false,
    "payment_progress": 0.00,
    "created_at": "2024-01-15T10:15:00Z"
  }
}
```

### Respons Error (409) - Faktur Sudah Ada

```json
{
  "success": false,
  "message": "Invoice already exists for this visit",
  "error": {
    "code": "INVOICE_EXISTS",
    "details": {
      "existing_invoice_id": 44,
      "invoice_number": "INV-20240115-0044"
    }
  }
}
```

---

### Ambil Faktur

Mengambil informasi detail tentang faktur tertentu.

```http
GET /api/billing/invoices/{id}
```

### Parameter URL

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID Faktur |

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| include | string | No | Data terkait yang akan disertakan (payments, visit, patient) |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 45,
    "invoice_number": "INV-20240115-0045",
    "visit": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "visit_date": "2024-01-15",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      }
    },
    "patient": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "address": "Jl. Merdeka No. 123",
      "phone": "08123456789",
      "insurance_type": "bpjs"
    },
    "invoice_date": "2024-01-15",
    "due_date": "2024-01-15",
    "subtotal": 125000.00,
    "discount_amount": 0.00,
    "tax_amount": 12500.00,
    "total_amount": 137500.00,
    "paid_amount": 137500.00,
    "balance_due": 0.00,
    "status": "paid",
    "payment_status": "paid",
    "insurance_claim_status": null,
    "insurance_claim_amount": 0.00,
    "insurance_paid_amount": 0.00,
    "items": [
      {
        "id": 1,
        "description": "Jasa Dokter",
        "quantity": 1,
        "unit_price": 50000.00,
        "discount": 0,
        "total": 50000.00
      },
      {
        "id": 2,
        "description": "Tindakan Medis",
        "quantity": 1,
        "unit_price": 25000.00,
        "discount": 0,
        "total": 25000.00
      },
      {
        "id": 3,
        "description": "Obat",
        "quantity": 1,
        "unit_price": 50000.00,
        "discount": 0,
        "total": 50000.00
      }
    ],
    "payments": [
      {
        "id": 52,
        "payment_number": "PAY-20240115-0052",
        "amount": 137500.00,
        "payment_method": "cash",
        "payment_date": "2024-01-15",
        "received_by": {
          "id": 15,
          "name": "Siti Rahayu"
        }
      }
    ],
    "notes": "Invoice rawat jalan",
    "is_paid": true,
    "is_overdue": false,
    "payment_progress": 100.00,
    "formatted_total": "Rp 137.500",
    "created_at": "2024-01-15T10:15:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

---

### Perbarui Faktur

Memperbarui informasi faktur (hanya jika belum dibayar).

```http
PUT /api/billing/invoices/{id}
```

### Body Request

| Parameter | Type | Description |
|-----------|------|-------------|
| items | array | Item baris faktur (mengganti yang ada) |
| discount_amount | decimal | Jumlah diskon |
| tax_amount | decimal | Jumlah pajak |
| notes | string | Catatan faktur |

### Respons Error (422) - Faktur Sudah Dibayar

```json
{
  "success": false,
  "message": "Cannot modify paid invoice",
  "error": {
    "code": "INVOICE_PAID",
    "details": {}
  }
}
```

---

### Batalkan Faktur

Membatalkan faktur.

```http
POST /api/billing/invoices/{id}/cancel
```

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| reason | string | Yes | Alasan pembatalan |

### Respons Sukses (200)

```json
{
  "success": true,
  "message": "Invoice cancelled successfully",
  "data": {
    "id": 45,
    "status": "cancelled",
    "cancelled_at": "2024-01-15T11:00:00Z",
    "cancellation_reason": "Double billing"
  }
}
```

---

## Endpoint Pembayaran

### Buat Pembayaran

Memproses pembayaran untuk faktur.

```http
POST /api/billing/payments
```

### Header

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoice_id | integer | Yes | ID Faktur |
| amount | decimal | Yes | Jumlah pembayaran |
| payment_date | date | Yes | Tanggal pembayaran (YYYY-MM-DD) |
| payment_method | string | Yes | cash, credit_card, debit_card, bank_transfer, mobile_payment, bpjs, insurance |
| payment_type | string | No | full, partial, deposit |
| reference_number | string | No | Nomor referensi (untuk non-tunai) |
| bank_name | string | No | Nama bank (untuk transfer/kartu) |
| account_number | string | No | Nomor rekening (untuk transfer) |
| card_number | string | No | 4 digit terakhir (untuk pembayaran kartu) |
| approval_code | string | No | Kode persetujuan (untuk pembayaran kartu) |
| notes | string | No | Catatan pembayaran |

### Contoh Request - Pembayaran Tunai

```json
{
  "invoice_id": 45,
  "amount": 137500.00,
  "payment_date": "2024-01-15",
  "payment_method": "cash",
  "payment_type": "full",
  "notes": "Pembayaran tunai"
}
```

### Contoh Request - Pembayaran Kartu

```json
{
  "invoice_id": 45,
  "amount": 137500.00,
  "payment_date": "2024-01-15",
  "payment_method": "credit_card",
  "payment_type": "full",
  "reference_number": "REF123456",
  "card_number": "1234",
  "approval_code": "APPROVE123",
  "notes": "Pembayaran kartu kredit"
}
```

### Contoh Request - Pembayaran BPJS

```json
{
  "invoice_id": 45,
  "amount": 137500.00,
  "payment_date": "2024-01-15",
  "payment_method": "bpjs",
  "payment_type": "full",
  "reference_number": "0123B0010124A000001",
  "notes": "Klaim BPJS"
}
```

### Respons Sukses (201)

```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "id": 52,
    "payment_number": "PAY-20240115-0052",
    "invoice_id": 45,
    "invoice_number": "INV-20240115-0045",
    "payment_date": "2024-01-15",
    "payment_time": "2024-01-15T10:30:00Z",
    "amount": 137500.00,
    "payment_method": "cash",
    "payment_type": "full",
    "reference_number": null,
    "received_by": {
      "id": 15,
      "name": "Siti Rahayu"
    },
    "is_refunded": false,
    "refunded_amount": 0.00,
    "formatted_amount": "Rp 137.500",
    "payment_method_label": "Cash",
    "can_be_refunded": true,
    "refundable_amount": 137500.00,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Respons Error (422) - Kelebihan Pembayaran

```json
{
  "success": false,
  "message": "Payment amount exceeds balance due",
  "error": {
    "code": "OVERPAYMENT",
    "details": {
      "balance_due": 50000.00,
      "payment_amount": 75000.00,
      "excess": 25000.00
    }
  }
}
```

---

### Ambil Pembayaran

```http
GET /api/billing/payments/{id}
```

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 52,
    "payment_number": "PAY-20240115-0052",
    "invoice": {
      "id": 45,
      "invoice_number": "INV-20240115-0045",
      "patient": {
        "id": 1,
        "name": "John Doe"
      }
    },
    "payment_date": "2024-01-15",
    "payment_time": "2024-01-15T10:30:00Z",
    "amount": 137500.00,
    "payment_method": "cash",
    "payment_type": "full",
    "reference_number": null,
    "bank_name": null,
    "card_number": null,
    "approval_code": null,
    "received_by": {
      "id": 15,
      "name": "Siti Rahayu"
    },
    "notes": "Pembayaran tunai",
    "is_refunded": false,
    "refunded_amount": 0.00,
    "refunded_at": null,
    "refund_reason": null,
    "formatted_amount": "Rp 137.500",
    "payment_method_label": "Cash",
    "can_be_refunded": true,
    "refundable_amount": 137500.00,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

---

### Daftar Pembayaran

```http
GET /api/billing/payments
```

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Nomor halaman |
| per_page | integer | No | Item per halaman |
| invoice_id | integer | No | Filter berdasarkan faktur |
| payment_method | string | No | Filter berdasarkan metode |
| from_date | date | No | Dari tanggal |
| to_date | date | No | Sampai tanggal |
| is_refunded | boolean | No | Filter berdasarkan status pengembalian |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 52,
      "payment_number": "PAY-20240115-0052",
      "invoice_number": "INV-20240115-0045",
      "patient_name": "John Doe",
      "payment_date": "2024-01-15",
      "amount": 137500.00,
      "payment_method": "cash",
      "is_refunded": false,
      "received_by": "Siti Rahayu"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150,
    "summary": {
      "total_payments": 150,
      "total_amount": 35000000.00,
      "by_method": {
        "cash": 20000000.00,
        "bpjs": 10000000.00,
        "credit_card": 5000000.00
      }
    }
  }
}
```

---

### Proses Pengembalian Dana

Mengembalikan dana pembayaran.

```http
POST /api/billing/payments/{id}/refund
```

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| amount | decimal | Yes | Jumlah pengembalian |
| reason | string | Yes | Alasan pengembalian |
| password | string | Yes | Kata sandi pengguna untuk verifikasi |

### Contoh Request

```json
{
  "amount": 50000.00,
  "reason": "Kelebihan pembayaran",
  "password": "yourpassword"
}
```

### Respons Sukses (200)

```json
{
  "success": true,
  "message": "Refund processed successfully",
  "data": {
    "id": 52,
    "is_refunded": false,
    "refunded_amount": 50000.00,
    "refunded_at": "2024-01-15T11:00:00Z",
    "refund_reason": "Kelebihan pembayaran",
    "refundable_amount": 87500.00,
    "can_be_refunded": true
  }
}
```

---

## Endpoint Laporan

### Laporan Pendapatan

```http
GET /api/billing/reports/revenue
```

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from_date | date | Yes | Dari tanggal |
| to_date | date | Yes | Sampai tanggal |
| group_by | string | No | Kelompokkan berdasarkan (day, week, month) |
| polyclinic_id | integer | No | Filter berdasarkan poliklinik |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_revenue": 35000000.00,
      "total_invoices": 150,
      "total_payments": 148,
      "average_invoice": 233333.33,
      "outstanding": 500000.00
    },
    "by_date": [
      {
        "date": "2024-01-15",
        "revenue": 2750000.00,
        "invoices": 12,
        "payments": 12
      }
    ],
    "by_payment_method": [
      {
        "method": "cash",
        "amount": 20000000.00,
        "percentage": 57.14
      },
      {
        "method": "bpjs",
        "amount": 10000000.00,
        "percentage": 28.57
      },
      {
        "method": "credit_card",
        "amount": 5000000.00,
        "percentage": 14.29
      }
    ],
    "by_polyclinic": [
      {
        "polyclinic": "Poli Umum",
        "revenue": 15000000.00,
        "invoices": 85
      },
      {
        "polyclinic": "Poli Gigi",
        "revenue": 8000000.00,
        "invoices": 35
      }
    ]
  }
}
```

---

### Laporan Faktur Belum Dibayar

```http
GET /api/billing/reports/outstanding
```

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "total_outstanding": 2500000.00,
    "total_invoices": 25,
    "overdue_invoices": 12,
    "overdue_amount": 1200000.00,
    "by_age": {
      "0-30_days": {
        "count": 10,
        "amount": 1000000.00
      },
      "31-60_days": {
        "count": 8,
        "amount": 800000.00
      },
      "61-90_days": {
        "count": 4,
        "amount": 400000.00
      },
      "over_90_days": {
        "count": 3,
        "amount": 300000.00
      }
    }
  }
}
```

---

### Laporan Kas Harian

```http
GET /api/billing/reports/daily-cash
```

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | date | Yes | Tanggal laporan |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "date": "2024-01-15",
    "opening_balance": 500000.00,
    "cash_receipts": {
      "total": 1500000.00,
      "by_category": {
        "registration": 250000.00,
        "consultation": 500000.00,
        "pharmacy": 500000.00,
        "laboratory": 250000.00
      }
    },
    "cash_disbursements": {
      "total": 200000.00,
      "refunds": 150000.00,
      "expenses": 50000.00
    },
    "closing_balance": 1800000.00,
    "transactions": [
      {
        "id": 52,
        "time": "10:30",
        "description": "John Doe - INV-20240115-0045",
        "type": "in",
        "amount": 137500.00
      }
    ]
  }
}
```

## Status Faktur

| Status | Deskripsi |
|--------|-------------|
| `pending` | Faktur dibuat, menunggu pembayaran |
| `paid` | Sudah dibayar penuh |
| `cancelled` | Dibatalkan |
| `refunded` | Dikembalikan |

## Status Pembayaran

| Status | Deskripsi |
|--------|-------------|
| `unpaid` | Belum ada pembayaran diterima |
| `partial` | Pembayaran sebagian diterima |
| `paid` | Sudah dibayar penuh |

## Metode Pembayaran

| Method | Deskripsi |
|--------|-------------|
| `cash` | Pembayaran tunai |
| `credit_card` | Kartu kredit |
| `debit_card` | Kartu debit |
| `bank_transfer` | Transfer bank |
| `mobile_payment` | Pembayaran mobile/e-wallet |
| `bpjs` | Klaim BPJS |
| `insurance` | Asuransi swasta |

## Status Klaim Asuransi

| Status | Deskripsi |
|--------|-------------|
| `pending` | Klaim diajukan, menunggu persetujuan |
| `approved` | Klaim disetujui |
| `rejected` | Klaim ditolak |
| `paid` | Klaim dibayar |

## Referensi Kode Error

| Code | HTTP Status | Deskripsi |
|------|-------------|-------------|
| `INVOICE_NOT_FOUND` | 404 | Faktur tidak ditemukan |
| `INVOICE_EXISTS` | 409 | Faktur sudah ada untuk kunjungan |
| `INVOICE_PAID` | 422 | Tidak dapat mengubah faktur yang sudah dibayar |
| `PAYMENT_NOT_FOUND` | 404 | Pembayaran tidak ditemukan |
| `OVERPAYMENT` | 422 | Pembayaran melebihi saldo |
| `INVALID_PAYMENT_METHOD` | 422 | Metode pembayaran tidak valid |
| `REFUND_EXCEEDS_PAYMENT` | 422 | Jumlah pengembalian melebihi pembayaran |
| `PAYMENT_ALREADY_REFUNDED` | 422 | Pembayaran sudah dikembalikan penuh |
| `INVALID_DATE_RANGE` | 422 | Rentang tanggal tidak valid |
