# API Webhook

Dokumen ini menjelaskan sistem webhook untuk menerima notifikasi real-time dari sistem SIMRS.

## Daftar Isi

- [Ikhtisar](#ikhtisar)
- [Webhook yang Tersedia](#webhook-yang-tersedia)
- [Format Payload Webhook](#format-payload-webhook)
- [Verifikasi Tanda Tangan](#verifikasi-tanda-tangan)
- [Mekanisme Pengulangan](#mekanisme-pengulangan)
- [Konfigurasi Webhook](#konfigurasi-webhook)

---

## Ikhtisar

Webhook memungkinkan aplikasi Anda menerima notifikasi real-time ketika peristiwa tertentu terjadi di sistem SIMRS. Alih-alih melakukan polling untuk perubahan, Anda dapat mendaftarkan endpoint webhook yang akan menerima permintaan HTTP POST ketika peristiwa terjadi.

### Alur Webhook

1. Peristiwa terjadi di SIMRS (misalnya, pasien terdaftar, kunjungan selesai)
2. SIMRS membangun payload webhook
3. SIMRS menandatangani payload dengan kunci rahasia
4. SIMRS mengirim permintaan POST ke endpoint yang terdaftar
5. Endpoint Anda memverifikasi tanda tangan dan memproses payload
6. Endpoint Anda mengembalikan kode status 2xx

---

## Webhook yang Tersedia

### Peristiwa Pasien

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `patient.created` | Pasien baru terdaftar | Patient |
| `patient.updated` | Informasi pasien diperbarui | Patient |
| `patient.deleted` | Rekam pasien dihapus | Patient |

### Peristiwa Kunjungan

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `visit.created` | Kunjungan baru terdaftar | Visit |
| `visit.updated` | Informasi kunjungan diperbarui | Visit |
| `visit.status_changed` | Status kunjungan berubah | Visit dengan detail perubahan status |
| `visit.completed` | Kunjungan selesai | Visit |
| `visit.cancelled` | Kunjungan dibatalkan | Visit |

### Peristiwa Rekam Medis

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `medical_record.created` | Rekam medis dibuat | Medical Record |
| `medical_record.updated` | Rekam medis diperbarui | Medical Record |
| `medical_record.finalized` | Rekam medis diselesaikan | Medical Record |

### Peristiwa Resep

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `prescription.created` | Resep baru dibuat | Prescription |
| `prescription.verified` | Resep diverifikasi oleh apoteker | Prescription |
| `prescription.dispensed` | Resep ditebus | Prescription |
| `prescription.completed` | Resep selesai | Prescription |

### Peristiwa Penagihan

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `invoice.created` | Tagihan baru dibuat | Invoice |
| `invoice.paid` | Tagihan lunas | Invoice dengan detail pembayaran |
| `invoice.cancelled` | Tagihan dibatalkan | Invoice |
| `payment.received` | Pembayaran diterima | Payment |

### Peristiwa Antrian

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `queue.called` | Pasien dipanggil ke loket | Queue |
| `queue.completed` | Antrian selesai | Queue |
| `queue.skipped` | Antrian dilewati | Queue |

### Peristiwa Integrasi

| Nama Peristiwa | Deskripsi | Tipe Payload |
|----------------|-----------|--------------|
| `bpjs.sep.created` | SEP BPJS dibuat | SEP |
| `bpjs.sep.deleted` | SEP BPJS dihapus | SEP |
| `satusehat.patient.synced` | Pasien disinkronkan ke Satu Sehat | Patient |
| `satusehat.encounter.synced` | Encounter disinkronkan ke Satu Sehat | Encounter |

---

## Format Payload Webhook

Semua payload webhook mengikuti format standar:

### Struktur Payload Umum

```json
{
  "id": "evt_1234567890abcdef",
  "object": "event",
  "api_version": "v1",
  "created_at": "2024-01-15T10:30:00Z",
  "type": "patient.created",
  "data": {
    "object": {
      // Data spesifik peristiwa
    }
  }
}
```

### Peristiwa Pasien Dibuat

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

### Peristiwa Status Kunjungan Berubah

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

### Peristiwa Tagihan Lunas

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

### Peristiwa Antrian Dipanggil

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

## Verifikasi Tanda Tangan

SIMRS menandatangani semua payload webhook untuk memastikan keaslian. Anda harus memverifikasi tanda tangan sebelum memproses webhook.

### Header Tanda Tangan

```http
X-SIMRS-Signature: t=1705312200,v1=sha256=abc123def456...
```

### Format Tanda Tangan

- `t`: Timestamp Unix ketika tanda tangan dibuat
- `v1`: Tanda tangan HMAC-SHA256 dari timestamp dan payload

### Langkah-langkah Verifikasi

1. Ekstrak timestamp (`t`) dan tanda tangan (`v1`) dari header
2. Siapkan payload yang ditandatangani: `{timestamp}.{json_payload}`
3. Hasilkan HMAC-SHA256 menggunakan rahasia webhook Anda
4. Bandingkan tanda tangan yang dihasilkan dengan tanda tangan yang diterima
5. Verifikasi timestamp berada dalam jendela yang dapat diterima (misalnya, 5 menit)

### Contoh Verifikasi (PHP)

```php
function verifyWebhookSignature($payload, $header, $secret) {
    // Parse header tanda tangan
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
    
    // Periksa timestamp (tolak jika lebih dari 5 menit)
    $now = time();
    if (abs($now - $timestamp) > 300) {
        return false;
    }
    
    // Hasilkan tanda tangan yang diharapkan
    $signedPayload = $timestamp . '.' . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
    
    // Bandingkan tanda tangan (timing-safe)
    return hash_equals($signature, $expectedSignature);
}

// Penggunaan
$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_SIMRS_SIGNATURE'];
$webhookSecret = 'your_webhook_secret';

if (!verifyWebhookSignature($payload, $signatureHeader, $webhookSecret)) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

// Proses webhook
$data = json_decode($payload, true);
```

### Contoh Verifikasi (JavaScript/Node.js)

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
    
    // Periksa timestamp
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        return false;
    }
    
    // Hasilkan tanda tangan yang diharapkan
    const signedPayload = `${timestamp}.${payload}`;
    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(signedPayload)
        .digest('hex');
    
    // Bandingkan tanda tangan
    return crypto.timingSafeEqual(
        Buffer.from(signature, 'hex'),
        Buffer.from(expectedSignature, 'hex')
    );
}

// Penggunaan (Express)
app.post('/webhook', express.raw({ type: 'application/json' }), (req, res) => {
    const signature = req.headers['x-simrs-signature'];
    const secret = process.env.WEBHOOK_SECRET;
    
    if (!verifyWebhookSignature(req.body, signature, secret)) {
        return res.status(400).send('Invalid signature');
    }
    
    const event = JSON.parse(req.body);
    // Proses peristiwa
    
    res.status(200).send('OK');
});
```

---

## Mekanisme Pengulangan

SIMRS mengimplementasikan mekanisme pengulangan otomatis untuk pengiriman webhook yang gagal.

### Jadwal Pengulangan

| Percobaan | Jeda Setelah Sebelumnya |
|-----------|------------------------|
| 1 | Langsung |
| 2 | 5 detik |
| 3 | 30 detik |
| 4 | 2 menit |
| 5 | 10 menit |
| 6+ | 1 jam (maks 24 jam) |

### Kondisi Pengulangan

SIMRS akan mengulang webhook jika endpoint Anda mengembalikan:
- Kode status 4xx apa pun (kecuali 410 Gone)
- Kode status 5xx apa pun
- Timeout (tidak ada respons dalam 30 detik)
- Kesalahan jaringan

### Endpoint yang Dinonaktifkan

Jika endpoint Anda secara konsisten gagal (semua percobaan habis) selama 7 hari berturut-turut:
- Webhook akan dinonaktifkan secara otomatis
- Email notifikasi dikirim ke administrator
- Pengaktifan kembali manual diperlukan melalui dashboard

### Idempotensi

Semua peristiwa webhook menyertakan kolom `id` yang unik. Simpan ID peristiwa yang telah diproses untuk mencegah pemrosesan ganda:

```php
// Contoh: Periksa apakah peristiwa sudah diproses
$eventId = $data['id'];
if (EventLog::where('event_id', $eventId)->exists()) {
    http_response_code(200);
    exit; // Sudah diproses
}

// Proses peristiwa...

// Catat sebagai diproses
EventLog::create(['event_id' => $eventId]);
```

---

## Konfigurasi Webhook

### Daftarkan Endpoint Webhook

```http
POST /api/webhooks
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| url | string | Ya | URL endpoint (harus menggunakan HTTPS) |
| events | array | Ya | Array tipe peristiwa untuk berlangganan |
| description | string | Tidak | Deskripsi untuk webhook ini |
| is_active | boolean | Tidak | Apakah webhook aktif (default: true) |
| secret | string | Tidak | Rahasia kustom (dihasilkan otomatis jika tidak diberikan) |

### Contoh Permintaan

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
  "description": "Integrasi dengan Hospital Management System",
  "is_active": true
}
```

### Respons Sukses (201)

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

### Daftar Webhook

```http
GET /api/webhooks
```

### Respons Sukses (200)

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

### Perbarui Webhook

```http
PUT /api/webhooks/{id}
```

### Body Permintaan

Sama seperti Buat Webhook (semua kolom opsional).

---

### Hapus Webhook

```http
DELETE /api/webhooks/{id}
```

### Respons Sukses (200)

```json
{
  "success": true,
  "message": "Webhook deleted successfully"
}
```

---

### Uji Webhook

Kirim peristiwa uji untuk memverifikasi endpoint Anda.

```http
POST /api/webhooks/{id}/test
```

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| event_type | string | Ya | Tipe peristiwa untuk diuji |

### Contoh Permintaan

```json
{
  "event_type": "patient.created"
}
```

### Respons Sukses (200)

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

### Dapatkan Log Pengiriman Webhook

```http
GET /api/webhooks/{id}/deliveries
```

### Parameter Kueri

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| page | integer | Tidak | Nomor halaman |
| per_page | integer | Tidak | Item per halaman |
| status | string | Tidak | Filter berdasarkan status (success, failed) |

### Respons Sukses (200)

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

## Praktik Terbaik

### 1. Kembalikan 2xx dengan Cepat

Proses webhook secara asinkron. Kembalikan 200 OK segera dan proses peristiwa di latar belakang:

```php
// Baik: Akui segera
http_response_code(200);
fastcgi_finish_request(); // Tutup koneksi

// Proses di latar belakang
processWebhookAsync($data);
```

### 2. Tangani Duplikat

Gunakan kunci idempotensi untuk mencegah pemrosesan ganda:

```php
$eventId = $data['id'];
$lockKey = "webhook:{$eventId}";

if (!Cache::add($lockKey, true, 3600)) {
    return; // Sudah diproses atau sedang diproses
}
```

### 3. Verifikasi Tanda Tangan

Selalu verifikasi tanda tangan webhook untuk memastikan keaslian.

### 4. Gunakan HTTPS

URL webhook harus menggunakan HTTPS di production.

### 5. Catat Peristiwa

Catat semua peristiwa webhook untuk keperluan debugging dan audit.

### 6. Tangani Timeout

Atur timeout yang sesuai dan tangani kegagalan jaringan dengan baik.

## Referensi Kode Error

| Kode | Status HTTP | Deskripsi |
|------|-------------|-----------|
| `WEBHOOK_NOT_FOUND` | 404 | Konfigurasi webhook tidak ditemukan |
| `INVALID_WEBHOOK_URL` | 422 | URL harus menggunakan HTTPS |
| `INVALID_EVENT_TYPE` | 422 | Tipe peristiwa tidak valid ditentukan |
| `WEBHOOK_DISABLED` | 403 | Webhook dinonaktifkan |
| `DELIVERY_FAILED` | 422 | Pengiriman uji gagal |
| `RATE_LIMIT_EXCEEDED` | 429 | Terlalu banyak pendaftaran webhook |
