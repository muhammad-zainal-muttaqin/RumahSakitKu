# API Manajemen Antrian

Dokumen ini menjelaskan semua endpoint untuk manajemen antrian dalam sistem SIMRS.

## Daftar Isi

- [Daftar Antrian Saat Ini](#daftar-antrian-saat-ini)
- [Ambil Data Layar Tampilan](#ambil-data-layar-tampilan)
- [Panggil Nomor Antrian](#panggil-nomor-antrian)
- [Lewati Nomor Antrian](#lewati-nomor-antrian)
- [Selesaikan Antrian](#selesaikan-antrian)
- [Ambil Statistik Antrian](#ambil-statistik-antrian)

---

## Daftar Antrian Saat Ini

Ambil daftar antrian saat ini dengan filter opsional.

```http
GET /api/queues
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| polyclinic_id | integer | Tidak | Filter berdasarkan ID poliklinik |
| date | date | Tidak | Filter berdasarkan tanggal (YYYY-MM-DD, default: hari ini) |
| status | string | Tidak | Filter berdasarkan status (menunggu, dipanggil, selesai, dilewati) |
| queue_type | string | Tidak | Filter berdasarkan tipe (umum, bpjs, prioritas) |
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Item per halaman (default: 20, maks: 100) |

### Respons Sukses (200)

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
  "message": "You do not have permission to view queues",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Ambil Data Layar Tampilan

Ambil data yang diformat untuk layar tampilan (tampilan TV di area tunggu).

```http
GET /api/queues/display
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| polyclinic_id | integer | Tidak | Filter berdasarkan ID poliklinik |
| limit | integer | Tidak | Jumlah antrian terbaru yang ditampilkan (default: 10) |

### Respons Sukses (200)

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

---

## Panggil Nomor Antrian

Panggil nomor antrian ke loket tertentu.

```http
POST /api/queues/{id}/call
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
| id | integer | ID Antrian |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| counter | string | Ya | Nomor loket tempat pasien harus datang |
| called_by | integer | Ya | ID User staf yang memanggil antrian |

### Contoh Permintaan

```json
{
  "counter": "1",
  "called_by": 5
}
```

### Respons Sukses (200)

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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Respons Error (422) - Status Tidak Valid

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

## Lewati Nomor Antrian

Lewati nomor antrian dan lanjut ke nomor berikutnya.

```http
POST /api/queues/{id}/skip
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
| id | integer | ID Antrian |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| reason | string | Tidak | Alasan melewati antrian |
| skipped_by | integer | Ya | ID User staf yang melewati antrian |

### Contoh Permintaan

```json
{
  "reason": "Pasien tidak hadir",
  "skipped_by": 5
}
```

### Respons Sukses (200)

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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Respons Error (422) - Status Tidak Valid

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

## Selesaikan Antrian

Tandai antrian sebagai selesai setelah pasien dilayani.

```http
POST /api/queues/{id}/complete
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
| id | integer | ID Antrian |

### Body Permintaan

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| completed_by | integer | Ya | ID User staf yang menyelesaikan antrian |
| notes | string | Tidak | Catatan tambahan |

### Contoh Permintaan

```json
{
  "completed_by": 5,
  "notes": "Pemeriksaan selesai normal"
}
```

### Respons Sukses (200)

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
  "message": "Queue not found",
  "error": {
    "code": "QUEUE_NOT_FOUND",
    "details": {}
  }
}
```

### Respons Error (422) - Status Tidak Valid

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

## Ambil Statistik Antrian

Ambil statistik antrian untuk dashboard dan pelaporan.

```http
GET /api/queues/stats
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| date | date | Tidak | Tanggal statistik (YYYY-MM-DD, default: hari ini) |
| polyclinic_id | integer | Tidak | Filter berdasarkan ID poliklinik |
| from_date | date | Tidak | Tanggal awal untuk rentang (YYYY-MM-DD) |
| to_date | date | Tidak | Tanggal akhir untuk rentang (YYYY-MM-DD) |

### Respons Sukses (200)

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
  "message": "You do not have permission to view queue statistics",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Referensi Tipe Data

### Status Antrian

| Status | Deskripsi |
|--------|-------------|
| menunggu | Menunggu dalam antrian |
| dipanggil | Dipanggil ke loket |
| selesai | Layanan selesai |
| dilewati | Dilewati |

### Tipe Antrian

| Tipe | Deskripsi |
|------|-------------|
| umum | Umum/Reguler |
| bpjs | Asuransi BPJS |
| prioritas | Prioritas (lansia, darurat, dll.) |

### Prefix Antrian

| Prefix | Deskripsi |
|--------|-------------|
| A | Antrian BPJS |
| B | Antrian Umum |
| P | Antrian Prioritas |

## Referensi Kode Error

| Kode | Status HTTP | Deskripsi |
|------|-------------|-------------|
| `QUEUE_NOT_FOUND` | 404 | Antrian dengan ID yang ditentukan tidak ditemukan |
| `INVALID_QUEUE_STATUS` | 422 | Tidak dapat melakukan aksi pada antrian dengan status saat ini |
| `COUNTER_REQUIRED` | 422 | Parameter counter wajib diisi untuk aksi panggil |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki izin untuk mengelola antrian |
| `VALIDATION_ERROR` | 422 | Validasi permintaan gagal |
