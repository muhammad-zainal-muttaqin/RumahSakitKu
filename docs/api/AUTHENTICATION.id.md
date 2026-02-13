# API Autentikasi

Dokumen ini menjelaskan semua endpoint terkait autentikasi untuk API SIMRS.

## Ringkasan

API SIMRS menggunakan **Laravel Sanctum** untuk autentikasi berbasis token. Setelah autentikasi berhasil, Anda akan menerima access token yang harus disertakan dalam semua request berikutnya.

## Login

Autentikasi user dan mendapatkan access token.

```http
POST /api/auth/login
```

### Headers

| Nama | Nilai |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| email | string | Ya | Alamat email user |
| password | string | Ya | Password user |
| device_name | string | Tidak | Identifier perangkat untuk tracking token |

### Contoh Request

```json
{
  "email": "doctor@rumahsakitku.com",
  "password": "password",
  "device_name": "Mobile App - iPhone 15"
}
```

### Response Sukses (200)

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

### Response Error (422) - Validasi Error

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

Revoke access token saat ini.

```http
POST /api/auth/logout
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

## Logout Semua Perangkat

Revoke semua token untuk user saat ini di semua perangkat.

```http
POST /api/auth/logout-all
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

Mengambil informasi tentang user yang sedang autentikasi.

```http
GET /api/auth/user
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

Refresh access token yang akan expired.

```http
POST /api/auth/refresh
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

Meminta link/token untuk reset password.

```http
POST /api/auth/password/forgot
```

### Headers

| Nama | Nilai |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| email | string | Ya | Alamat email user |

### Contoh Request

```json
{
  "email": "doctor@rumahsakitku.com"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

> **Catatan:** Untuk alasan keamanan, endpoint ini mengembalikan sukses meskipun email tidak ada di sistem.

---

## Password Reset - Confirm

Reset password menggunakan token yang diterima via email.

```http
POST /api/auth/password/reset
```

### Headers

| Nama | Nilai |
|------|-------|
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| token | string | Ya | Reset token dari email |
| email | string | Ya | Alamat email user |
| password | string | Ya | Password baru (min 8 karakter) |
| password_confirmation | string | Ya | Konfirmasi password baru |

### Contoh Request

```json
{
  "token": "reset_token_from_email",
  "email": "doctor@rumahsakitku.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### Response Sukses (200)

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

Ganti password untuk user yang terautentikasi.

```http
POST /api/auth/password/change
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Request Body

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|--------|-----------|
| current_password | string | Ya | Password saat ini |
| new_password | string | Ya | Password baru (min 8 karakter) |
| new_password_confirmation | string | Ya | Konfirmasi password baru |

### Contoh Request

```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

### Response Error (403) - Password Lama Salah

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

Mencantumkan semua token aktif untuk user saat ini.

```http
GET /api/auth/tokens
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

Mencabut token tertentu berdasarkan ID.

```http
DELETE /api/auth/tokens/{token_id}
```

### Headers

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Response Sukses (200)

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

## Role-Based Access Control (RBAC)

API SIMRS menggunakan role-based access control (RBAC) dengan roles berikut:

### Available Roles

| Role | Deskripsi | Permission Default |
|------|-----------|-------------------|
| `super_admin` | Full system access | Semua permission |
| `admin` | Administrative access | users.manage, roles.manage, reports.view |
| `doctor` | Medical doctor | patients.view, medical_records.manage, prescriptions.create |
| `nurse` | Nursing staff | patients.view, assessments.manage, visits.manage |
| `pharmacist` | Pharmacy staff | prescriptions.view, medicines.manage, dispensing.manage |
| `cashier` | Billing staff | invoices.manage, payments.process |
| `registration` | Front desk | patients.create, visits.manage, queues.manage |
| `laboratory` | Lab staff | lab_orders.manage, lab_results.manage |
| `radiology` | Radiology staff | radiology_orders.manage, radiology_results.manage |
| `bpjs_officer` | BPJS staff | bpjs.manage, sep.create, claims.manage |

### Cek Permission

Cek apakah user saat ini memiliki permission tertentu:

```http
GET /api/auth/permissions/{permission}
```

Contoh: `GET /api/auth/permissions/patients.create`

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "has_permission": true
  }
}
```

---

## Best Practices Keamanan

1. **Token Storage**: Simpan token dengan aman (e.g., secure storage untuk mobile apps, httpOnly cookies untuk web)
2. **Token Expiration**: Token expire setelah 24 jam by default. Implement token refresh logic
3. **HTTPS Only**: Selalu gunakan HTTPS untuk API requests
4. **Token Revocation**: Revoke tokens saat user logout
5. **Password Policy**: Enforce strong password requirements (min 8 chars, kombinasi huruf besar/kecil, angka, simbol)

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

## Kode Error Reference

| Kode | HTTP Status | Deskripsi |
|------|-------------|-----------|
| `INVALID_CREDENTIALS` | 401 | Username/email atau password salah |
| `UNAUTHENTICATED` | 401 | Tidak ada token autentikasi yang valid |
| `TOKEN_EXPIRED` | 401 | Token autentikasi sudah expired |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki permission yang dibutuhkan |
| `INVALID_RESET_TOKEN` | 400 | Token reset password tidak valid atau expired |
| `INVALID_CURRENT_PASSWORD` | 403 | Password saat ini yang dimasukkan salah |
| `RATE_LIMIT_EXCEEDED` | 429 | Terlalu banyak request, rate limit tercapai |
| `VALIDATION_ERROR` | 422 | Request validasi gagal |
