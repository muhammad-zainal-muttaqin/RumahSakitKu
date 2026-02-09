# Room and Bed Management API

This document describes all endpoints for room and bed management in the SIMRS system.

## Table of Contents

- [List All Rooms](#list-all-rooms)
- [Get Beds in Room](#get-beds-in-room)
- [Get Room Occupancy Stats](#get-room-occupancy-stats)
- [List Available Beds](#list-available-beds)

---

## List All Rooms

Retrieve a list of all rooms in the hospital.

```http
GET /api/rooms
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| room_class | string | No | Filter by room class (vvip, vip, i, ii, iii) |
| room_type | string | No | Filter by room type (reguler, isolasi, icu, nicu, picu) |
| floor | integer | No | Filter by floor number |
| building | string | No | Filter by building name |
| status | string | No | Filter by status (aktif, nonaktif, perbaikan) |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

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
  "message": "You do not have permission to view rooms",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Get Beds in Room

Retrieve all beds in a specific room.

```http
GET /api/rooms/{id}/beds
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Room ID |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by bed status (kosong, terisi, perbaikan, dibooking) |

### Response Success (200)

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
  "message": "Room not found",
  "error": {
    "code": "ROOM_NOT_FOUND",
    "details": {}
  }
}
```

---

## Get Room Occupancy Stats

Retrieve room occupancy statistics.

```http
GET /api/rooms/occupancy
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| floor | integer | No | Filter by floor number |
| building | string | No | Filter by building name |
| date | date | No | Statistics date (YYYY-MM-DD, default: today) |

### Response Success (200)

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
  "message": "You do not have permission to view occupancy statistics",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## List Available Beds

Retrieve a list of all available beds for admission.

```http
GET /api/beds/available
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| room_class | string | No | Filter by room class (vvip, vip, i, ii, iii) |
| room_type | string | No | Filter by room type (reguler, isolasi, icu, nicu, picu) |
| floor | integer | No | Filter by floor number |
| gender | string | No | Filter by room gender preference (laki-laki, perempuan, campur) |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### Response Success (200)

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
  "message": "You do not have permission to view available beds",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Data Types Reference

### Room Class

| Class | Description | Base Price Range |
|-------|-------------|------------------|
| vvip | VVIP | Rp 1.500.000+ |
| vip | VIP | Rp 750.000 - Rp 1.500.000 |
| i | Kelas I | Rp 500.000 - Rp 750.000 |
| ii | Kelas II | Rp 300.000 - Rp 500.000 |
| iii | Kelas III | Rp 150.000 - Rp 300.000 |

### Room Type

| Type | Description |
|------|-------------|
| reguler | Regular Room |
| isolasi | Isolation Room |
| icu | Intensive Care Unit |
| nicu | Neonatal ICU |
| picu | Pediatric ICU |

### Bed Status

| Status | Description |
|--------|-------------|
| kosong | Empty/Available |
| terisi | Occupied |
| perbaikan | Under Maintenance |
| dibooking | Booked/Reserved |

### Room Status

| Status | Description |
|--------|-------------|
| aktif | Active |
| nonaktif | Inactive |
| perbaikan | Under Maintenance |

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `ROOM_NOT_FOUND` | 404 | Room with specified ID not found |
| `BED_NOT_FOUND` | 404 | Bed with specified ID not found |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks permission to view room data |
| `VALIDATION_ERROR` | 422 | Request validation failed |
