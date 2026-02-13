# API Manajemen Kamar dan Tempat Tidur

Dokumen ini menjelaskan semua endpoint untuk manajemen kamar dan tempat tidur dalam sistem SIMRS.

## Daftar Isi

- [Daftar Semua Kamar](#daftar-semua-kamar)
- [Dapatkan Tempat Tidur dalam Kamar](#dapatkan-tempat-tidur-dalam-kamar)
- [Dapatkan Statistik Tingkat Hunian Kamar](#dapatkan-statistik-tingkat-hunian-kamar)
- [Daftar Tempat Tidur Tersedia](#daftar-tempat-tidur-tersedia)

---

## Daftar Semua Kamar

Mengambil daftar semua kamar di rumah sakit.

```http
GET /api/rooms
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| room_class | string | Tidak | Filter berdasarkan kelas kamar (vvip, vip, i, ii, iii) |
| room_type | string | Tidak | Filter berdasarkan tipe kamar (reguler, isolasi, icu, nicu, picu) |
| floor | integer | Tidak | Filter berdasarkan nomor lantai |
| building | string | Tidak | Filter berdasarkan nama gedung |
| status | string | Tidak | Filter berdasarkan status (aktif, nonaktif, perbaikan) |
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Item per halaman (default: 20, maks: 100) |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "room_number": "301",
      "room_name": "Melati VIP",
      "room_class": "vip",
      "room_class_display": "VIP",
      "room_type": "reguler",
      "floor": 3,
      "building": "Gedung A",
      "capacity": 2,
      "base_price_per_day": 750000,
      "facilities": [
        "AC",
        "TV",
        "Kamar Mandi Dalam",
        "Lemari Pakaian",
        "Sofa"
      ],
      "description": "Kamar VIP dengan fasilitas lengkap",
      "status": "aktif",
      "bed_count": 2,
      "available_beds": 1,
      "occupied_beds": 1,
      "created_at": "2024-01-01T08:00:00Z",
      "updated_at": "2024-01-10T14:00:00Z"
    },
    {
      "id": 2,
      "room_number": "201",
      "room_name": "Mawar Kelas I",
      "room_class": "i",
      "room_class_display": "Kelas I",
      "room_type": "reguler",
      "floor": 2,
      "building": "Gedung A",
      "capacity": 3,
      "base_price_per_day": 500000,
      "facilities": [
        "AC",
        "TV",
        "Kamar Mandi Dalam"
      ],
      "description": "Kamar Kelas I dengan 3 tempat tidur",
      "status": "aktif",
      "bed_count": 3,
      "available_beds": 2,
      "occupied_beds": 1,
      "created_at": "2024-01-01T08:00:00Z",
      "updated_at": "2024-01-10T14:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/rooms?page=1",
    "last": "/api/rooms?page=10",
    "prev": null,
    "next": "/api/rooms?page=2"
  }
}
```

### Respons Gagal (401)

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

### Respons Gagal (403)

```json
{
  "success": false,
  "message": "You do not have permission to view rooms",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Dapatkan Tempat Tidur dalam Kamar

Mengambil semua tempat tidur dalam kamar tertentu.

```http
GET /api/rooms/{id}/beds
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter URL

| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| id | integer | ID Kamar |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| status | string | Tidak | Filter berdasarkan status tempat tidur (kosong, terisi, perbaikan, dibooking) |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "room": {
      "id": 1,
      "room_number": "301",
      "room_name": "Melati VIP",
      "room_class": "vip",
      "room_class_display": "VIP",
      "floor": 3,
      "building": "Gedung A",
      "base_price_per_day": 750000
    },
    "beds": [
      {
        "id": 1,
        "bed_number": "301-A",
        "bed_code": "A",
        "status": "terisi",
        "current_patient": {
          "id": 45,
          "name": "John Doe",
          "medical_record_number": "20240101-0001",
          "admission_date": "2024-01-10",
          "diagnosis": "Demam Berdarah"
        },
        "features": [
          "Elektrik",
          "Side Rails",
          "IV Pole"
        ],
        "created_at": "2024-01-01T08:00:00Z",
        "updated_at": "2024-01-10T09:00:00Z"
      },
      {
        "id": 2,
        "bed_number": "301-B",
        "bed_code": "B",
        "status": "kosong",
        "current_patient": null,
        "features": [
          "Elektrik",
          "Side Rails",
          "IV Pole"
        ],
        "created_at": "2024-01-01T08:00:00Z",
        "updated_at": "2024-01-01T08:00:00Z"
      }
    ],
    "summary": {
      "total_beds": 2,
      "available_beds": 1,
      "occupied_beds": 1,
      "maintenance_beds": 0,
      "booked_beds": 0
    }
  }
}
```

### Respons Gagal (401)

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

### Respons Gagal (404)

```json
{
  "success": false,
  "message": "Room not found",
  "error": {
    "code": "ROOM_NOT_FOUND",
    "details": {}
  }
}
```

---

## Dapatkan Statistik Tingkat Hunian Kamar

Mengambil statistik tingkat hunian kamar.

```http
GET /api/rooms/occupancy
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| floor | integer | Tidak | Filter berdasarkan nomor lantai |
| building | string | Tidak | Filter berdasarkan nama gedung |
| date | date | Tidak | Tanggal statistik (YYYY-MM-DD, default: hari ini) |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": {
    "date": "2024-01-15",
    "overall": {
      "total_rooms": 50,
      "active_rooms": 48,
      "total_beds": 150,
      "available_beds": 45,
      "occupied_beds": 102,
      "maintenance_beds": 3,
      "booked_beds": 0,
      "occupancy_rate": 68.0
    },
    "by_class": [
      {
        "class": "vvip",
        "class_display": "VVIP",
        "total_rooms": 3,
        "total_beds": 3,
        "available_beds": 1,
        "occupied_beds": 2,
        "occupancy_rate": 66.7,
        "base_price_per_day": 1500000
      },
      {
        "class": "vip",
        "class_display": "VIP",
        "total_rooms": 10,
        "total_beds": 20,
        "available_beds": 8,
        "occupied_beds": 12,
        "occupancy_rate": 60.0,
        "base_price_per_day": 750000
      },
      {
        "class": "i",
        "class_display": "Kelas I",
        "total_rooms": 12,
        "total_beds": 36,
        "available_beds": 10,
        "occupied_beds": 26,
        "occupancy_rate": 72.2,
        "base_price_per_day": 500000
      },
      {
        "class": "ii",
        "class_display": "Kelas II",
        "total_rooms": 15,
        "total_beds": 45,
        "available_beds": 15,
        "occupied_beds": 30,
        "occupancy_rate": 66.7,
        "base_price_per_day": 300000
      },
      {
        "class": "iii",
        "class_display": "Kelas III",
        "total_rooms": 10,
        "total_beds": 46,
        "available_beds": 11,
        "occupied_beds": 32,
        "occupancy_rate": 69.6,
        "base_price_per_day": 150000
      }
    ],
    "by_type": [
      {
        "type": "reguler",
        "type_display": "Reguler",
        "total_rooms": 40,
        "total_beds": 120,
        "available_beds": 35,
        "occupied_beds": 82
      },
      {
        "type": "isolasi",
        "type_display": "Isolasi",
        "total_rooms": 5,
        "total_beds": 15,
        "available_beds": 5,
        "occupied_beds": 10
      },
      {
        "type": "icu",
        "type_display": "ICU",
        "total_rooms": 3,
        "total_beds": 15,
        "available_beds": 5,
        "occupied_beds": 10
      }
    ],
    "by_floor": [
      {
        "floor": 1,
        "total_rooms": 10,
        "total_beds": 30,
        "available_beds": 8,
        "occupied_beds": 22
      },
      {
        "floor": 2,
        "total_rooms": 15,
        "total_beds": 45,
        "available_beds": 15,
        "occupied_beds": 30
      },
      {
        "floor": 3,
        "total_rooms": 15,
        "total_beds": 45,
        "available_beds": 12,
        "occupied_beds": 33
      },
      {
        "floor": 4,
        "total_rooms": 10,
        "total_beds": 30,
        "available_beds": 10,
        "occupied_beds": 17
      }
    ]
  }
}
```

### Respons Gagal (401)

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

### Respons Gagal (403)

```json
{
  "success": false,
  "message": "You do not have permission to view occupancy statistics",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Daftar Tempat Tidur Tersedia

Mengambil daftar semua tempat tidur yang tersedia untuk rawat inap.

```http
GET /api/beds/available
```

### Header

| Nama | Nilai |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|----------|-------------|
| room_class | string | Tidak | Filter berdasarkan kelas kamar (vvip, vip, i, ii, iii) |
| room_type | string | Tidak | Filter berdasarkan tipe kamar (reguler, isolasi, icu, nicu, picu) |
| floor | integer | Tidak | Filter berdasarkan nomor lantai |
| gender | string | Tidak | Filter berdasarkan preferensi gender kamar (laki-laki, perempuan, campur) |
| page | integer | Tidak | Nomor halaman (default: 1) |
| per_page | integer | Tidak | Item per halaman (default: 20, maks: 100) |

### Respons Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "bed_number": "301-B",
      "bed_code": "B",
      "room": {
        "id": 1,
        "room_number": "301",
        "room_name": "Melati VIP",
        "room_class": "vip",
        "room_class_display": "VIP",
        "room_type": "reguler",
        "floor": 3,
        "building": "Gedung A",
        "base_price_per_day": 750000,
        "facilities": [
          "AC",
          "TV",
          "Kamar Mandi Dalam",
          "Lemari Pakaian",
          "Sofa"
        ]
      },
      "features": [
        "Elektrik",
        "Side Rails",
        "IV Pole"
      ],
      "gender_preference": "campur",
      "estimated_available_until": null,
      "created_at": "2024-01-01T08:00:00Z"
    },
    {
      "id": 15,
      "bed_number": "201-B",
      "bed_code": "B",
      "room": {
        "id": 2,
        "room_number": "201",
        "room_name": "Mawar Kelas I",
        "room_class": "i",
        "room_class_display": "Kelas I",
        "room_type": "reguler",
        "floor": 2,
        "building": "Gedung A",
        "base_price_per_day": 500000,
        "facilities": [
          "AC",
          "TV",
          "Kamar Mandi Dalam"
        ]
      },
      "features": [
        "Manual",
        "Side Rails",
        "IV Pole"
      ],
      "gender_preference": "campur",
      "estimated_available_until": null,
      "created_at": "2024-01-01T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/beds/available?page=1",
    "last": "/api/beds/available?page=3",
    "prev": null,
    "next": "/api/beds/available?page=2"
  }
}
```

### Respons Gagal (401)

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

### Respons Gagal (403)

```json
{
  "success": false,
  "message": "You do not have permission to view available beds",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Referensi Tipe Data

### Kelas Kamar

| Kelas | Deskripsi | Rentang Harga Dasar |
|-------|-------------|------------------|
| vvip | VVIP | Rp 1.500.000+ |
| vip | VIP | Rp 750.000 - Rp 1.500.000 |
| i | Kelas I | Rp 500.000 - Rp 750.000 |
| ii | Kelas II | Rp 300.000 - Rp 500.000 |
| iii | Kelas III | Rp 150.000 - Rp 300.000 |

### Tipe Kamar

| Tipe | Deskripsi |
|------|-------------|
| reguler | Kamar Reguler |
| isolasi | Kamar Isolasi |
| icu | Intensive Care Unit |
| nicu | Neonatal ICU |
| picu | Pediatric ICU |

### Status Tempat Tidur

| Status | Deskripsi |
|--------|-------------|
| kosong | Kosong/Tersedia |
| terisi | Terisi |
| perbaikan | Dalam Perbaikan |
| dibooking | Dipesan/Direservasi |

### Status Kamar

| Status | Deskripsi |
|--------|-------------|
| aktif | Aktif |
| nonaktif | Nonaktif |
| perbaikan | Dalam Perbaikan |

## Referensi Kode Error

| Kode | Status HTTP | Deskripsi |
|------|-------------|-------------|
| `ROOM_NOT_FOUND` | 404 | Kamar dengan ID yang ditentukan tidak ditemukan |
| `BED_NOT_FOUND` | 404 | Tempat tidur dengan ID yang ditentukan tidak ditemukan |
| `INSUFFICIENT_PERMISSIONS` | 403 | Pengguna tidak memiliki izin untuk melihat data kamar |
| `VALIDATION_ERROR` | 422 | Validasi permintaan gagal |
