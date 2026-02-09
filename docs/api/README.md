# SIMRS API Documentation

## Introduction

Welcome to the **SIMRS (Sistem Informasi Manajemen Rumah Sakit)** API documentation. This comprehensive API provides programmatic access to all hospital management functions including patient management, medical records, pharmacy, billing, and integrations with national health systems like BPJS and Satu Sehat.

## Base URL

```
Production:  https://api.rumahsakitku.com/api
Staging:     https://staging-api.rumahsakitku.com/api
Local:       http://localhost:8000/api
```

## API Version

Current API Version: `v1`

All endpoints are prefixed with `/api/v1/`. For backward compatibility, `/api/` without version defaults to the latest version.

## Authentication

The SIMRS API uses **Laravel Sanctum** token-based authentication. All API requests must include an authentication token in the header.

### Authentication Header

```http
Authorization: Bearer {your_access_token}
```

### Obtaining Tokens

Tokens are obtained through the login endpoint:

```http
POST /api/auth/login
```

See [AUTHENTICATION.md](./AUTHENTICATION.md) for detailed authentication flows.

## Response Format

All API responses follow a standardized JSON format:

### Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "request_id": "req_123456789"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "error": {
    "code": "ERROR_CODE",
    "details": { ... }
  },
  "meta": {
    "timestamp": "2024-01-01T12:00:00Z",
    "request_id": "req_123456789"
  }
}
```

### Pagination Response

List endpoints return paginated results:

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/patients?page=1",
    "last": "/api/patients?page=10",
    "prev": null,
    "next": "/api/patients?page=2"
  }
}
```

## HTTP Status Codes

| Code | Description | Usage |
|------|-------------|-------|
| 200 | OK | Successful GET, PUT, PATCH requests |
| 201 | Created | Successful POST requests |
| 204 | No Content | Successful DELETE requests |
| 400 | Bad Request | Invalid request format or parameters |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource does not exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |
| 503 | Service Unavailable | External service unavailable |

## Error Handling

### Common Error Codes

| Code | Description | Resolution |
|------|-------------|------------|
| `INVALID_CREDENTIALS` | Username or password incorrect | Check credentials and retry |
| `TOKEN_EXPIRED` | Authentication token has expired | Refresh or obtain new token |
| `INSUFFICIENT_PERMISSIONS` | User lacks required role | Contact administrator for access |
| `RESOURCE_NOT_FOUND` | Requested resource not found | Verify resource ID |
| `VALIDATION_ERROR` | Input validation failed | Check request parameters |
| `RATE_LIMIT_EXCEEDED` | Too many requests | Wait and retry |
| `BPJS_SERVICE_ERROR` | BPJS service unavailable | Retry or contact support |
| `SATU_SEHAT_ERROR` | Satu Sehat service unavailable | Retry or contact support |

### Validation Errors

When validation fails (422), the response includes detailed error messages:

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "nik": ["The NIK field is required", "The NIK must be 16 digits"],
      "name": ["The name field is required"]
    }
  }
}
```

## Rate Limiting

API requests are rate-limited to ensure fair usage:

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| Authentication | 10 requests | 1 minute |
| Standard API | 100 requests | 1 minute |
| Batch Operations | 20 requests | 1 minute |
| BPJS Integration | 60 requests | 1 minute |
| Satu Sehat Integration | 120 requests | 1 minute |

Rate limit headers are included in all responses:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Request/Response Headers

### Required Headers

| Header | Value | Description |
|--------|-------|-------------|
| `Authorization` | `Bearer {token}` | Authentication token |
| `Accept` | `application/json` | Expected response format |
| `Content-Type` | `application/json` | Request body format (for POST/PUT) |

### Optional Headers

| Header | Value | Description |
|--------|-------|-------------|
| `X-Request-ID` | UUID | Unique identifier for request tracing |
| `X-Client-Version` | Semver | Client application version |
| `Accept-Language` | `id`, `en` | Preferred language for messages |

## API Modules

| Module | Description | Documentation |
|--------|-------------|---------------|
| Authentication | User login, logout, token management | [AUTHENTICATION.md](./AUTHENTICATION.md) |
| Patients | Patient registration and management | [PATIENTS.md](./PATIENTS.md) |
| Visits | Visit/Kunjungan management | [VISITS.md](./VISITS.md) |
| Medical Records | EMR, SOAP notes, CPPT | [MEDICAL_RECORDS.md](./MEDICAL_RECORDS.md) |
| BPJS | BPJS integration APIs | [BPJS.md](./BPJS.md) |
| Satu Sehat | Satu Sehat FHIR integration | [SATU_SEHAT.md](./SATU_SEHAT.md) |
| Pharmacy | Prescription and medicine management | [PHARMACY.md](./PHARMACY.md) |
| Billing | Invoices and payments | [BILLING.md](./BILLING.md) |
| Webhooks | Event notifications | [WEBHOOKS.md](./WEBHOOKS.md) |

## Date/Time Format

All dates and times are in **ISO 8601** format and **UTC** timezone unless specified otherwise:

```
YYYY-MM-DDTHH:mm:ssZ
2024-01-01T12:00:00Z
```

For date-only fields:

```
YYYY-MM-DD
2024-01-01
```

## Common Data Types

| Type | Format | Example |
|------|--------|---------|
| `date` | ISO 8601 Date | `2024-01-01` |
| `datetime` | ISO 8601 DateTime | `2024-01-01T12:00:00Z` |
| `decimal` | Numeric with 2 decimals | `150000.00` |
| `nik` | 16 digits | `1234567890123456` |
| `bpjs_card` | 13 digits | `0001234567890` |
| `phone` | E.164 format | `+628123456789` |
| `uuid` | UUID v4 | `550e8400-e29b-41d4-a716-446655440000` |

## SDK and Tools

### Official SDKs

- PHP: `composer require rumahsakitku/simrs-sdk`
- JavaScript/Node.js: `npm install @rumahsakitku/simrs-sdk`
- Python: `pip install rumahsakitku-simrs`

### Postman Collection

Download our Postman collection: [SIMRS API Postman Collection](https://api.rumahsakitku.com/docs/postman)

## Support

For technical support and questions:

- **Documentation**: https://docs.rumahsakitku.com
- **Support Email**: api-support@rumahsakitku.com
- **Issue Tracker**: https://github.com/rumahsakitku/simrs-api/issues
- **Status Page**: https://status.rumahsakitku.com

## Changelog

### v1.0.0 (2024-01-01)

- Initial API release
- Patient management endpoints
- Visit management endpoints
- Medical records (EMR) endpoints
- BPJS integration
- Satu Sehat integration
- Pharmacy management
- Billing and payment endpoints
