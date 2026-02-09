# Billing API

This document describes all endpoints for billing, invoicing, and payment management in the SIMRS system.

## Table of Contents

- [Invoice Endpoints](#invoice-endpoints)
- [Payment Endpoints](#payment-endpoints)
- [Reports Endpoints](#reports-endpoints)

---

## Invoice Endpoints

### List Invoices

Retrieve a paginated list of all invoices.

```http
GET /api/billing/invoices
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
| visit_id | integer | No | Filter by visit ID |
| status | string | No | Filter by status (pending, paid, cancelled, refunded) |
| payment_status | string | No | Filter by payment status (unpaid, partial, paid) |
| from_date | date | No | Filter from date (YYYY-MM-DD) |
| to_date | date | No | Filter to date (YYYY-MM-DD) |
| overdue | boolean | No | Show only overdue invoices |
| with_balance | boolean | No | Show only invoices with remaining balance |
| invoice_number | string | No | Search by invoice number |

### Response Success (200)

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

### Create Invoice

Create a new invoice for a visit.

```http
POST /api/billing/invoices
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
| visit_id | integer | Yes | Visit ID |
| patient_id | integer | Yes | Patient ID |
| invoice_date | date | Yes | Invoice date (YYYY-MM-DD) |
| due_date | date | Yes | Due date (YYYY-MM-DD) |
| items | array | Yes | Invoice line items |
| discount_amount | decimal | No | Discount amount (default: 0) |
| tax_amount | decimal | No | Tax amount (default: 0) |
| notes | string | No | Invoice notes |

### Request Example

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

### Response Success (201)

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

### Response Error (409) - Invoice Already Exists

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

### Get Invoice

Retrieve detailed information about a specific invoice.

```http
GET /api/billing/invoices/{id}
```

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Invoice ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| include | string | No | Related data to include (payments, visit, patient) |

### Response Success (200)

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

### Update Invoice

Update invoice information (only if not paid).

```http
PUT /api/billing/invoices/{id}
```

### Request Body

| Parameter | Type | Description |
|-----------|------|-------------|
| items | array | Invoice line items (replaces existing) |
| discount_amount | decimal | Discount amount |
| tax_amount | decimal | Tax amount |
| notes | string | Invoice notes |

### Response Error (422) - Invoice Already Paid

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

### Cancel Invoice

Cancel an invoice.

```http
POST /api/billing/invoices/{id}/cancel
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| reason | string | Yes | Cancellation reason |

### Response Success (200)

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

## Payment Endpoints

### Create Payment

Process a payment for an invoice.

```http
POST /api/billing/payments
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
| invoice_id | integer | Yes | Invoice ID |
| amount | decimal | Yes | Payment amount |
| payment_date | date | Yes | Payment date (YYYY-MM-DD) |
| payment_method | string | Yes | cash, credit_card, debit_card, bank_transfer, mobile_payment, bpjs, insurance |
| payment_type | string | No | full, partial, deposit |
| reference_number | string | No | Reference number (for non-cash) |
| bank_name | string | No | Bank name (for transfer/card) |
| account_number | string | No | Account number (for transfer) |
| card_number | string | No | Last 4 digits (for card payments) |
| approval_code | string | No | Approval code (for card payments) |
| notes | string | No | Payment notes |

### Request Example - Cash Payment

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

### Request Example - Card Payment

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

### Request Example - BPJS Payment

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

### Response Success (201)

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

### Response Error (422) - Overpayment

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

### Get Payment

```http
GET /api/billing/payments/{id}
```

### Response Success (200)

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

### List Payments

```http
GET /api/billing/payments
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number |
| per_page | integer | No | Items per page |
| invoice_id | integer | No | Filter by invoice |
| payment_method | string | No | Filter by method |
| from_date | date | No | From date |
| to_date | date | No | To date |
| is_refunded | boolean | No | Filter by refund status |

### Response Success (200)

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

### Process Refund

Refund a payment.

```http
POST /api/billing/payments/{id}/refund
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| amount | decimal | Yes | Refund amount |
| reason | string | Yes | Refund reason |
| password | string | Yes | User password for verification |

### Request Example

```json
{
  "amount": 50000.00,
  "reason": "Kelebihan pembayaran",
  "password": "yourpassword"
}
```

### Response Success (200)

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

## Reports Endpoints

### Revenue Report

```http
GET /api/billing/reports/revenue
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| from_date | date | Yes | From date |
| to_date | date | Yes | To date |
| group_by | string | No | Group by (day, week, month) |
| polyclinic_id | integer | No | Filter by polyclinic |

### Response Success (200)

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

### Outstanding Invoices Report

```http
GET /api/billing/reports/outstanding
```

### Response Success (200)

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

### Daily Cash Report

```http
GET /api/billing/reports/daily-cash
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | date | Yes | Report date |

### Response Success (200)

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

## Invoice Statuses

| Status | Description |
|--------|-------------|
| `pending` | Invoice created, awaiting payment |
| `paid` | Fully paid |
| `cancelled` | Cancelled |
| `refunded` | Refunded |

## Payment Statuses

| Status | Description |
|--------|-------------|
| `unpaid` | No payment received |
| `partial` | Partial payment received |
| `paid` | Fully paid |

## Payment Methods

| Method | Description |
|--------|-------------|
| `cash` | Cash payment |
| `credit_card` | Credit card |
| `debit_card` | Debit card |
| `bank_transfer` | Bank transfer |
| `mobile_payment` | Mobile/e-wallet payment |
| `bpjs` | BPJS claim |
| `insurance` | Private insurance |

## Insurance Claim Statuses

| Status | Description |
|--------|-------------|
| `pending` | Claim submitted, awaiting approval |
| `approved` | Claim approved |
| `rejected` | Claim rejected |
| `paid` | Claim paid |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `INVOICE_NOT_FOUND` | 404 | Invoice not found |
| `INVOICE_EXISTS` | 409 | Invoice already exists for visit |
| `INVOICE_PAID` | 422 | Cannot modify paid invoice |
| `PAYMENT_NOT_FOUND` | 404 | Payment not found |
| `OVERPAYMENT` | 422 | Payment exceeds balance |
| `INVALID_PAYMENT_METHOD` | 422 | Invalid payment method |
| `REFUND_EXCEEDS_PAYMENT` | 422 | Refund amount exceeds payment |
| `PAYMENT_ALREADY_REFUNDED` | 422 | Payment already fully refunded |
| `INVALID_DATE_RANGE` | 422 | Invalid date range |
