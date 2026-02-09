# Queue Management API

This document describes all endpoints for queue management in the SIMRS system.

## Table of Contents

- [List Current Queues](#list-current-queues)
- [Get Display Screen Data](#get-display-screen-data)
- [Call Queue Number](#call-queue-number)
- [Skip Queue Number](#skip-queue-number)
- [Complete Queue](#complete-queue)
- [Get Queue Statistics](#get-queue-statistics)

---

## List Current Queues

Retrieve a list of current queues with optional filtering.

```http
GET /api/queues
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| polyclinic_id | integer | No | Filter by polyclinic ID |
| date | date | No | Filter by date (YYYY-MM-DD, default: today) |
| status | string | No | Filter by status (menunggu, dipanggil, selesai, dilewati) |
| queue_type | string | No | Filter by type (umum, bpjs, prioritas) |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 152,
      "queue_number": "A-045",
      "display_number": "045",
      "prefix": "A",
      "queue_type": "bpjs",
      "polyclinic": {
        "id": 1,
        "name": "Poli Umum",
        "code": "UM"
      },
      "doctor": {
        "id": 5,
        "name": "Dr. Sarah Johnson",
        "specialization": "Dokter Umum"
      },
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001",
        "insurance_type": "bpjs"
      },
      "visit": {
        "id": 45,
        "visit_number": "RJ-20240115-0045",
        "complaint": "Sakit kepala dan demam"
      },
      "status": "menunggu",
      "counter": null,
      "waiting_time_minutes": 15,
      "called_at": null,
      "completed_at": null,
      "created_at": "2024-01-15T08:30:00Z",
      "updated_at": "2024-01-15T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 85,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/queues?page=1",
    "last": "/api/queues?page=5",
    "prev": null,
    "next": "/api/queues?page=2"
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
  "message": "You do not have permission to view queues",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Get Display Screen Data

Retrieve data formatted for display screen (TV display in waiting area).

```http
GET /api/queues/display
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| polyclinic_id | integer | No | Filter by polyclinic ID |
| limit | integer | No | Number of recent queues to show (default: 10) |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "current_queue": {
      "queue_number": "A-042",
      "display_number": "042",
      "counter": "1",
      "polyclinic": "Poli Umum",
      "status": "dipanggil",
      "called_at": "2024-01-15T09:15:00Z"
    },
    "previous_queues": [
      {
        "queue_number": "A-041",
        "counter": "2",
        "polyclinic": "Poli Umum",
        "status": "selesai",
        "completed_at": "2024-01-15T09:10:00Z"
      },
      {
        "queue_number": "A-040",
        "counter": "1",
        "polyclinic": "Poli Umum",
        "status": "selesai",
        "completed_at": "2024-01-15T09:05:00Z"
      }
    ],
    "upcoming_queues": [
      {
        "queue_number": "A-043",
        "polyclinic": "Poli Umum",
        "estimated_wait_minutes": 10
      },
      {
        "queue_number": "A-044",
        "polyclinic": "Poli Umum",
        "estimated_wait_minutes": 20
      }
    ],
    "polyclinic_stats": [
      {
        "polyclinic_id": 1,
        "polyclinic_name": "Poli Umum",
        "waiting_count": 12,
        "called_count": 3,
        "completed_count": 42
      }
    ],
    "last_updated": "2024-01-15T09:15:30Z"
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

---

## Call Queue Number

Call a queue number to a specific counter.

```http
POST /api/queues/{id}/call
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
| id | integer | Queue ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| counter | string | Yes | Counter number where patient should come |
| called_by | integer | Yes | User ID of the staff calling the queue |

### Request Example

```json
{
  "counter": "1",
  "called_by": 5
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Queue A-045 called to counter 1",
  "data": {
    "id": 152,
    "queue_number": "A-045",
    "status": "dipanggil",
    "counter": "1",
    "called_at": "2024-01-15T09:20:00Z",
    "called_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "polyclinic": {
      "id": 1,
      "name": "Poli Umum"
    },
    "patient": {
      "id": 23,
      "name": "John Doe"
    }
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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot call queue with status: selesai",
  "error": {
    "code": "INVALID_QUEUE_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["menunggu", "dilewati"]
    }
  }
}
```

---

## Skip Queue Number

Skip a queue number and move to the next one.

```http
POST /api/queues/{id}/skip
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
| id | integer | Queue ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| reason | string | No | Reason for skipping the queue |
| skipped_by | integer | Yes | User ID of the staff skipping the queue |

### Request Example

```json
{
  "reason": "Pasien tidak hadir",
  "skipped_by": 5
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Queue A-045 skipped",
  "data": {
    "id": 152,
    "queue_number": "A-045",
    "status": "dilewati",
    "skip_reason": "Pasien tidak hadir",
    "skipped_at": "2024-01-15T09:25:00Z",
    "skipped_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "polyclinic": {
      "id": 1,
      "name": "Poli Umum"
    },
    "patient": {
      "id": 23,
      "name": "John Doe"
    }
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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot skip queue with status: selesai",
  "error": {
    "code": "INVALID_QUEUE_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["menunggu", "dipanggil"]
    }
  }
}
```

---

## Complete Queue

Mark a queue as completed after patient is served.

```http
POST /api/queues/{id}/complete
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
| id | integer | Queue ID |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| completed_by | integer | Yes | User ID of the staff completing the queue |
| notes | string | No | Additional notes |

### Request Example

```json
{
  "completed_by": 5,
  "notes": "Pemeriksaan selesai normal"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Queue A-045 completed",
  "data": {
    "id": 152,
    "queue_number": "A-045",
    "status": "selesai",
    "completed_at": "2024-01-15T09:45:00Z",
    "completed_by": {
      "id": 5,
      "name": "Dr. Sarah Johnson"
    },
    "service_duration_minutes": 25,
    "polyclinic": {
      "id": 1,
      "name": "Poli Umum"
    },
    "patient": {
      "id": 23,
      "name": "John Doe"
    }
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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Invalid Status

```json
{
  "success": false,
  "message": "Cannot complete queue with status: menunggu",
  "error": {
    "code": "INVALID_QUEUE_STATUS",
    "details": {
      "current_status": "menunggu",
      "allowed_statuses": ["dipanggil"]
    }
  }
}
```

---

## Get Queue Statistics

Retrieve queue statistics for dashboard and reporting.

```http
GET /api/queues/stats
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | date | No | Statistics date (YYYY-MM-DD, default: today) |
| polyclinic_id | integer | No | Filter by polyclinic ID |
| from_date | date | No | Start date for range (YYYY-MM-DD) |
| to_date | date | No | End date for range (YYYY-MM-DD) |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "date": "2024-01-15",
    "summary": {
      "total_queues": 85,
      "waiting": 12,
      "called": 3,
      "completed": 68,
      "skipped": 2
    },
    "average_wait_time_minutes": 18,
    "average_service_time_minutes": 22,
    "by_polyclinic": [
      {
        "polyclinic_id": 1,
        "polyclinic_name": "Poli Umum",
        "polyclinic_code": "UM",
        "total": 45,
        "waiting": 8,
        "called": 2,
        "completed": 34,
        "skipped": 1,
        "average_wait_time": 15
      },
      {
        "polyclinic_id": 2,
        "polyclinic_name": "Poli Gigi",
        "polyclinic_code": "GD",
        "total": 25,
        "waiting": 3,
        "called": 1,
        "completed": 21,
        "skipped": 0,
        "average_wait_time": 12
      },
      {
        "polyclinic_id": 3,
        "polyclinic_name": "Poli Anak",
        "polyclinic_code": "AN",
        "total": 15,
        "waiting": 1,
        "called": 0,
        "completed": 13,
        "skipped": 1,
        "average_wait_time": 25
      }
    ],
    "by_type": [
      {
        "type": "bpjs",
        "count": 65
      },
      {
        "type": "umum",
        "count": 18
      },
      {
        "type": "prioritas",
        "count": 2
      }
    ],
    "peak_hours": [
      {
        "hour": "08:00-09:00",
        "count": 25
      },
      {
        "hour": "09:00-10:00",
        "count": 35
      },
      {
        "hour": "10:00-11:00",
        "count": 20
      }
    ]
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
  "message": "You do not have permission to view queue statistics",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Data Types Reference

### Queue Status

| Status | Description |
|--------|-------------|
| menunggu | Waiting in queue |
| dipanggil | Called to counter |
| selesai | Service completed |
| dilewati | Skipped |

### Queue Type

| Type | Description |
|------|-------------|
| umum | General/Regular |
| bpjs | BPJS Insurance |
| prioritas | Priority (elderly, emergency, etc.) |

### Queue Prefix

| Prefix | Description |
|--------|-------------|
| A | BPJS Queue |
| B | General Queue |
| P | Priority Queue |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `QUEUE_NOT_FOUND` | 404 | Queue with specified ID not found |
| `INVALID_QUEUE_STATUS` | 422 | Cannot perform action on queue with current status |
| `COUNTER_REQUIRED` | 422 | Counter parameter is required for call action |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission to manage queues |
| `VALIDATION_ERROR` | 422 | Request validation failed |
