# Webhooks API

This document describes the webhook system for receiving real-time notifications from the SIMRS system.

## Table of Contents

- [Overview](#overview)
- [Available Webhooks](#available-webhooks)
- [Webhook Payload Format](#webhook-payload-format)
- [Signature Verification](#signature-verification)
- [Retry Mechanism](#retry-mechanism)
- [Webhook Configuration](#webhook-configuration)

---

## Overview

Webhooks allow your application to receive real-time notifications when specific events occur in the SIMRS system. Instead of polling for changes, you can register webhook endpoints that will receive HTTP POST requests when events happen.

### Webhook Flow

1. Event occurs in SIMRS (e.g., patient registered, visit completed)
2. SIMRS constructs webhook payload
3. SIMRS signs payload with secret key
4. SIMRS sends POST request to your registered endpoint
5. Your endpoint verifies signature and processes payload
6. Your endpoint returns 2xx status code

---

## Available Webhooks

### Patient Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `patient.created` | New patient registered | Patient |
| `patient.updated` | Patient information updated | Patient |
| `patient.deleted` | Patient record deleted | Patient |

### Visit Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `visit.created` | New visit registered | Visit |
| `visit.updated` | Visit information updated | Visit |
| `visit.status_changed` | Visit status changed | Visit with status change details |
| `visit.completed` | Visit completed | Visit |
| `visit.cancelled` | Visit cancelled | Visit |

### Medical Record Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `medical_record.created` | Medical record created | Medical Record |
| `medical_record.updated` | Medical record updated | Medical Record |
| `medical_record.finalized` | Medical record finalized | Medical Record |

### Prescription Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `prescription.created` | New prescription created | Prescription |
| `prescription.verified` | Prescription verified by pharmacist | Prescription |
| `prescription.dispensed` | Prescription dispensed | Prescription |
| `prescription.completed` | Prescription completed | Prescription |

### Billing Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `invoice.created` | New invoice created | Invoice |
| `invoice.paid` | Invoice fully paid | Invoice with payment details |
| `invoice.cancelled` | Invoice cancelled | Invoice |
| `payment.received` | Payment received | Payment |

### Queue Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `queue.called` | Patient called to counter | Queue |
| `queue.completed` | Queue completed | Queue |
| `queue.skipped` | Queue skipped | Queue |

### Integration Events

| Event Name | Description | Payload Type |
|------------|-------------|--------------|
| `bpjs.sep.created` | BPJS SEP created | SEP |
| `bpjs.sep.deleted` | BPJS SEP deleted | SEP |
| `satusehat.patient.synced` | Patient synced to Satu Sehat | Patient |
| `satusehat.encounter.synced` | Encounter synced to Satu Sehat | Encounter |

---

## Webhook Payload Format

All webhook payloads follow a standardized format:

### Common Payload Structure

```json
{
  "id": "evt_1234567890abcdef",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T10:30:00Z",
  "type": "patient.created",
  "data": {
    "object": {
      // Event-specific data
    }
  }
}
```

### Patient Created Event

```json
{
  "id": "evt_1234567890abcdef",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T10:30:00Z",
  "type": "patient.created",
  "data": {
    "object": {
      "id": 1,
      "medical_record_number": "20240101-0001",
      "name": "John Doe",
      "nik": "1234567890123456",
      "birth_date": "1990-01-01",
      "gender": "L",
      "address": "Jl. Merdeka No. 123",
      "phone": "08123456789",
      "insurance_type": "bpjs",
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

### Visit Status Changed Event

```json
{
  "id": "evt_abcdef1234567890",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T10:45:00Z",
  "type": "visit.status_changed",
  "data": {
    "object": {
      "id": 45,
      "visit_number": "RJ-20240115-0045",
      "patient": {
        "id": 1,
        "name": "John Doe",
        "medical_record_number": "20240101-0001"
      },
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      },
      "previous_status": "menunggu",
      "current_status": "proses",
      "changed_at": "2024-01-15T10:45:00Z",
      "changed_by": {
        "id": 5,
        "name": "Dr. Sarah Johnson"
      }
    }
  }
}
```

### Invoice Paid Event

```json
{
  "id": "evt_7890abcdef123456",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T11:00:00Z",
  "type": "invoice.paid",
  "data": {
    "object": {
      "id": 45,
      "invoice_number": "INV-20240115-0045",
      "patient": {
        "id": 1,
        "name": "John Doe"
      },
      "visit": {
        "id": 45,
        "visit_number": "RJ-20240115-0045"
      },
      "total_amount": 137500.00,
      "paid_amount": 137500.00,
      "payment_status": "paid",
      "payments": [
        {
          "id": 52,
          "payment_number": "PAY-20240115-0052",
          "amount": 137500.00,
          "payment_method": "cash",
          "received_by": "Siti Rahayu"
        }
      ],
      "paid_at": "2024-01-15T11:00:00Z"
    }
  }
}
```

### Queue Called Event

```json
{
  "id": "evt_queue123456789",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T10:30:00Z",
  "type": "queue.called",
  "data": {
    "object": {
      "id": 45,
      "queue_number": 15,
      "display_number": "A-015",
      "patient": {
        "id": 1,
        "name": "John Doe"
      },
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum"
      },
      "counter_number": "1",
      "called_at": "2024-01-15T10:30:00Z",
      "waiting_time_minutes": 30
    }
  }
}
```

---

## Signature Verification

SIMRS signs all webhook payloads to ensure authenticity. You should verify the signature before processing the webhook.

### Signature Header

```http
X-SIMRS-Signature: t=1705312200,v1=sha256=abc123def456...
```

### Signature Format

- `t`: Unix timestamp when the signature was generated
- `v1`: HMAC-SHA256 signature of the timestamp and payload

### Verification Steps

1. Extract timestamp (`t`) and signature (`v1`) from header
2. Prepare the signed payload: `{timestamp}.{json_payload}`
3. Generate HMAC-SHA256 using your webhook secret
4. Compare generated signature with received signature
5. Verify timestamp is within acceptable window (e.g., 5 minutes)

### Verification Example (PHP)

```php
function verifyWebhookSignature($payload, $header, $secret) {
    // Parse signature header
    $parts = explode(',', $header);
    $timestamp = null;
    $signature = null;
    
    foreach ($parts as $part) {
        $kv = explode('=', $part, 2);
        if ($kv[0] === 't') {
            $timestamp = $kv[1];
        } elseif ($kv[0] === 'v1') {
            $signature = $kv[1];
        }
    }
    
    if (!$timestamp || !$signature) {
        return false;
    }
    
    // Check timestamp (reject if older than 5 minutes)
    $now = time();
    if (abs($now - $timestamp) > 300) {
        return false;
    }
    
    // Generate expected signature
    $signedPayload = $timestamp . '.' . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
    
    // Compare signatures (timing-safe)
    return hash_equals($signature, $expectedSignature);
}

// Usage
$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_SIMRS_SIGNATURE'];
$webhookSecret = 'your_webhook_secret';

if (!verifyWebhookSignature($payload, $signatureHeader, $webhookSecret)) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

// Process webhook
$data = json_decode($payload, true);
```

### Verification Example (JavaScript/Node.js)

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, header, secret) {
    const parts = header.split(',');
    let timestamp, signature;
    
    for (const part of parts) {
        const [key, value] = part.split('=');
        if (key === 't') timestamp = value;
        if (key === 'v1') signature = value;
    }
    
    if (!timestamp || !signature) {
        return false;
    }
    
    // Check timestamp
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        return false;
    }
    
    // Generate expected signature
    const signedPayload = `${timestamp}.${payload}`;
    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(signedPayload)
        .digest('hex');
    
    // Compare signatures
    return crypto.timingSafeEqual(
        Buffer.from(signature, 'hex'),
        Buffer.from(expectedSignature, 'hex')
    );
}

// Usage (Express)
app.post('/webhook', express.raw({ type: 'application/json' }), (req, res) => {
    const signature = req.headers['x-simrs-signature'];
    const secret = process.env.WEBHOOK_SECRET;
    
    if (!verifyWebhookSignature(req.body, signature, secret)) {
        return res.status(400).send('Invalid signature');
    }
    
    const event = JSON.parse(req.body);
    // Process event
    
    res.status(200).send('OK');
});
```

---

## Retry Mechanism

SIMRS implements an automatic retry mechanism for failed webhook deliveries.

### Retry Schedule

| Attempt | Delay After Previous |
|---------|---------------------|
| 1 | Immediate |
| 2 | 5 seconds |
| 3 | 30 seconds |
| 4 | 2 minutes |
| 5 | 10 minutes |
| 6+ | 1 hour (max 24 hours) |

### Retry Conditions

SIMRS will retry a webhook if your endpoint returns:
- Any 4xx status code (except 410 Gone)
- Any 5xx status code
- Timeout (no response within 30 seconds)
- Network error

### Disabled Endpoints

If your endpoint consistently fails (all retries exhausted) for 7 consecutive days:
- Webhook will be automatically disabled
- Notification email sent to administrators
- Manual re-enable required through dashboard

### Idempotency

All webhook events include a unique `id` field. Store processed event IDs to prevent duplicate processing:

```php
// Example: Check if event already processed
$eventId = $data['id'];
if (EventLog::where('event_id', $eventId)->exists()) {
    http_response_code(200);
    exit; // Already processed
}

// Process event...

// Log as processed
EventLog::create(['event_id' => $eventId]);
```

---

## Webhook Configuration

### Register Webhook Endpoint

```http
POST /api/webhooks
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| url | string | Yes | Endpoint URL (must use HTTPS) |
| events | array | Yes | Array of event types to subscribe |
| description | string | No | Description for this webhook |
| is_active | boolean | No | Whether webhook is active (default: true) |
| secret | string | No | Custom secret (auto-generated if not provided) |

### Request Example

```json
{
  "url": "https://your-app.com/webhooks/simrs",
  "events": [
    "patient.created",
    "patient.updated",
    "visit.created",
    "visit.completed",
    "invoice.paid"
  ],
  "description": "Integration with Hospital Management System",
  "is_active": true
}
```

### Response Success (201)

```json
{
  "success": true,
  "message": "Webhook registered successfully",
  "data": {
    "id": 1,
    "url": "https://your-app.com/webhooks/simrs",
    "events": [
      "patient.created",
      "patient.updated",
      "visit.created",
      "visit.completed",
      "invoice.paid"
    ],
    "description": "Integration with Hospital Management System",
    "secret": "whsec_xxxxxxxxxxxxxxxx",
    "is_active": true,
    "created_at": "2024-01-15T00:00:00Z"
  }
}
```

---

### List Webhooks

```http
GET /api/webhooks
```

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://your-app.com/webhooks/simrs",
      "events": ["patient.created", "visit.completed"],
      "description": "Integration with HMS",
      "is_active": true,
      "last_delivery": {
        "status": "success",
        "delivered_at": "2024-01-15T10:30:00Z",
        "http_status": 200
      },
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

---

### Update Webhook

```http
PUT /api/webhooks/{id}
```

### Request Body

Same as Create Webhook (all fields optional).

---

### Delete Webhook

```http
DELETE /api/webhooks/{id}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Webhook deleted successfully"
}
```

---

### Test Webhook

Send a test event to verify your endpoint.

```http
POST /api/webhooks/{id}/test
```

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| event_type | string | Yes | Event type to test |

### Request Example

```json
{
  "event_type": "patient.created"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Test webhook sent",
  "data": {
    "delivery_id": "test_123456789",
    "status": "success",
    "http_status": 200,
    "response_body": "OK",
    "delivered_at": "2024-01-15T12:00:00Z"
  }
}
```

---

### Get Webhook Delivery Logs

```http
GET /api/webhooks/{id}/deliveries
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number |
| per_page | integer | No | Items per page |
| status | string | No | Filter by status (success, failed) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": "del_1234567890",
      "event_id": "evt_1234567890abcdef",
      "event_type": "patient.created",
      "status": "success",
      "http_status": 200,
      "response_body": "OK",
      "attempts": 1,
      "delivered_at": "2024-01-15T10:30:00Z",
      "duration_ms": 150
    },
    {
      "id": "del_0987654321",
      "event_id": "evt_abcdef1234567890",
      "event_type": "invoice.paid",
      "status": "failed",
      "http_status": 500,
      "response_body": "Internal Server Error",
      "attempts": 3,
      "error": "Endpoint returned 500",
      "next_retry_at": "2024-01-15T10:35:00Z"
    }
  ]
}
```

## Best Practices

### 1. Return 2xx Quickly

Process webhooks asynchronously. Return 200 OK immediately and process the event in background:

```php
// Good: Acknowledge immediately
http_response_code(200);
fastcgi_finish_request(); // Close connection

// Process in background
processWebhookAsync($data);
```

### 2. Handle Duplicates

Use idempotency keys to prevent duplicate processing:

```php
$eventId = $data['id'];
$lockKey = "webhook:{$eventId}";

if (!Cache::add($lockKey, true, 3600)) {
    return; // Already processing or processed
}
```

### 3. Verify Signatures

Always verify webhook signatures to ensure authenticity.

### 4. Use HTTPS

Webhook URLs must use HTTPS in production.

### 5. Log Events

Log all webhook events for debugging and audit purposes.

### 6. Handle Timeouts

Set appropriate timeouts and handle network failures gracefully.

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `WEBHOOK_NOT_FOUND` | 404 | Webhook configuration not found |
| `INVALID_WEBHOOK_URL` | 422 | URL must use HTTPS |
| `INVALID_EVENT_TYPE` | 422 | Invalid event type specified |
| `WEBHOOK_DISABLED` | 403 | Webhook is disabled |
| `DELIVERY_FAILED` | 422 | Test delivery failed |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many webhook registrations |
