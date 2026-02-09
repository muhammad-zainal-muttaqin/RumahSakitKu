# Authentication API

This document describes all authentication-related endpoints for the SIMRS API.

## Overview

The SIMRS API uses **Laravel Sanctum** for token-based authentication. After successful authentication, you receive an access token that must be included in all subsequent requests.

## Login

Authenticate a user and obtain an access token.

```http
POST /api/auth/login
```

### Headers

| Name | Value |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| email | string | Yes | User email address |
| password | string | Yes | User password |
| device_name | string | No | Device identifier for token tracking |

### Request Example

```json
{
  "email": "doctor@rumahsakitku.com",
  "password": "yourpassword",
  "device_name": "Mobile App - iPhone 15"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Dr. John Doe",
      "email": "doctor@rumahsakitku.com",
      "roles": ["doctor", "admin"],
      "permissions": ["patients.view", "patients.create", "medical_records.manage"],
      "employee_id": 5,
      "is_active": true,
      "last_login_at": "2024-01-01T12:00:00Z"
    },
    "token": {
      "access_token": "1|laravel_sanctum_token_string_here",
      "token_type": "Bearer",
      "expires_at": "2024-01-02T12:00:00Z"
    }
  }
}
```

### Response Error (401)

```json
{
  "success": false,
  "message": "Invalid credentials",
  "error": {
    "code": "INVALID_CREDENTIALS",
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
      "email": ["The email field is required"],
      "password": ["The password field is required"]
    }
  }
}
```

### Response Error (429) - Rate Limited

```json
{
  "success": false,
  "message": "Too many login attempts. Please try again in 60 seconds.",
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "details": {
      "retry_after": 60
    }
  }
}
```

---

## Logout

Revoke the current access token.

```http
POST /api/auth/logout
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "message": "Successfully logged out"
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

## Logout All Devices

Revoke all tokens for the current user across all devices.

```http
POST /api/auth/logout-all
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "message": "Successfully logged out from all devices",
  "data": {
    "revoked_tokens_count": 5
  }
}
```

---

## Get Current User

Retrieve information about the currently authenticated user.

```http
GET /api/auth/user
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Dr. John Doe",
    "email": "doctor@rumahsakitku.com",
    "roles": [
      {
        "id": 1,
        "name": "doctor",
        "permissions": ["patients.view", "patients.create", "medical_records.manage"]
      }
    ],
    "employee": {
      "id": 5,
      "employee_number": "EMP001",
      "name": "Dr. John Doe",
      "position": "Dokter Spesialis",
      "department": "Poli Umum",
      "polyclinic_id": 1
    },
    "is_active": true,
    "last_login_at": "2024-01-01T12:00:00Z",
    "created_at": "2023-01-01T00:00:00Z"
  }
}
```

---

## Refresh Token

Refresh an expiring access token.

```http
POST /api/auth/refresh
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": {
      "access_token": "2|new_laravel_sanctum_token_string",
      "token_type": "Bearer",
      "expires_at": "2024-01-03T12:00:00Z"
    }
  }
}
```

### Response Error (401) - Token Expired

```json
{
  "success": false,
  "message": "Token has expired",
  "error": {
    "code": "TOKEN_EXPIRED",
    "details": {}
  }
}
```

---

## Password Reset - Request

Request a password reset link/token.

```http
POST /api/auth/password/forgot
```

### Headers

| Name | Value |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| email | string | Yes | User email address |

### Request Example

```json
{
  "email": "doctor@rumahsakitku.com"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

> **Note:** For security reasons, this endpoint returns success even if the email doesn't exist in the system.

---

## Password Reset - Confirm

Reset password using the token received via email.

```http
POST /api/auth/password/reset
```

### Headers

| Name | Value |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| token | string | Yes | Reset token from email |
| email | string | Yes | User email address |
| password | string | Yes | New password (min 8 chars) |
| password_confirmation | string | Yes | Confirm new password |

### Request Example

```json
{
  "token": "reset_token_from_email",
  "email": "doctor@rumahsakitku.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Password has been reset successfully"
}
```

### Response Error (400) - Invalid Token

```json
{
  "success": false,
  "message": "Invalid or expired reset token",
  "error": {
    "code": "INVALID_RESET_TOKEN",
    "details": {}
  }
}
```

---

## Change Password

Change password for authenticated user.

```http
POST /api/auth/password/change
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
| current_password | string | Yes | Current password |
| new_password | string | Yes | New password (min 8 chars) |
| new_password_confirmation | string | Yes | Confirm new password |

### Request Example

```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

### Response Success (200)

```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

### Response Error (403) - Wrong Current Password

```json
{
  "success": false,
  "message": "Current password is incorrect",
  "error": {
    "code": "INVALID_CURRENT_PASSWORD",
    "details": {}
  }
}
```

---

## List Active Tokens

List all active tokens for the current user.

```http
GET /api/auth/tokens
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Mobile App - iPhone 15",
      "last_used_at": "2024-01-01T12:00:00Z",
      "created_at": "2023-12-01T00:00:00Z",
      "is_current": true
    },
    {
      "id": 2,
      "name": "Web Dashboard - Chrome",
      "last_used_at": "2024-01-01T10:00:00Z",
      "created_at": "2023-12-15T00:00:00Z",
      "is_current": false
    }
  ]
}
```

---

## Revoke Specific Token

Revoke a specific token by ID.

```http
DELETE /api/auth/tokens/{token_id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Success (200)

```json
{
  "success": true,
  "message": "Token revoked successfully"
}
```

### Response Error (404)

```json
{
  "success": false,
  "message": "Token not found",
  "error": {
    "code": "TOKEN_NOT_FOUND",
    "details": {}
  }
}
```

---

## Role-Based Access Control

The SIMRS API uses role-based access control (RBAC) with the following predefined roles:

### Available Roles

| Role | Description | Default Permissions |
|------|-------------|---------------------|
| `super_admin` | Full system access | All permissions |
| `admin` | Administrative access | users.manage, roles.manage, reports.view |
| `doctor` | Medical doctor | patients.view, medical_records.manage, prescriptions.create |
| `nurse` | Nursing staff | patients.view, assessments.manage, visits.manage |
| `pharmacist` | Pharmacy staff | prescriptions.view, medicines.manage, dispensing.manage |
| `cashier` | Billing staff | invoices.manage, payments.process |
| `registration` | Front desk | patients.create, visits.manage, queues.manage |
| `laboratory` | Lab staff | lab_orders.manage, lab_results.manage |
| `radiology` | Radiology staff | radiology_orders.manage, radiology_results.manage |
| `bpjs_officer` | BPJS staff | bpjs.manage, sep.create, claims.manage |

### Checking Permissions

Check if current user has a specific permission:

```http
GET /api/auth/permissions/{permission}
```

Example: `GET /api/auth/permissions/patients.create`

### Response Success (200)

```json
{
  "success": true,
  "data": {
    "has_permission": true
  }
}
```

## Security Best Practices

1. **Token Storage**: Store tokens securely (e.g., in secure storage for mobile apps, httpOnly cookies for web)
2. **Token Expiration**: Tokens expire after 24 hours by default. Implement token refresh logic
3. **HTTPS Only**: Always use HTTPS for API requests
4. **Token Revocation**: Revoke tokens when user logs out
5. **Password Policy**: Enforce strong password requirements (min 8 chars, mixed case, numbers, symbols)

## Two-Factor Authentication (2FA)

### Enable 2FA

```http
POST /api/auth/2fa/enable
```

### Verify 2FA Setup

```http
POST /api/auth/2fa/verify
```

### Disable 2FA

```http
POST /api/auth/2fa/disable
```

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `INVALID_CREDENTIALS` | 401 | Username/email or password is incorrect |
| `UNAUTHENTICATED` | 401 | No valid authentication token provided |
| `TOKEN_EXPIRED` | 401 | Authentication token has expired |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks required permission |
| `INVALID_RESET_TOKEN` | 400 | Password reset token is invalid or expired |
| `INVALID_CURRENT_PASSWORD` | 403 | Current password provided is incorrect |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests, rate limit exceeded |
| `VALIDATION_ERROR` | 422 | Request validation failed |
