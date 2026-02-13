# API Rawat Inap

Dokumen ini menjelaskan semua endpoint untuk manajemen pasien rawat inap dalam sistem SIMRS.

## Daftar Isi

- [Daftar Pasien Rawat Inap](#daftar-pasien-rawat-inap)
- [Pendaftaran Pasien Rawat Inap](#pendaftaran-pasien-rawat-inap)
- [Pindah Kamar/Tempat Tidur](#pindah-kamartempat-tidur)
- [Pulangkan Pasien](#pulangkan-pasien)
- [Ambil Tagihan Pasien](#ambil-tagihan-pasien)

---

## Daftar Pasien Rawat Inap

Mengambil daftar semua pasien rawat inap dengan filter opsional.

```http
GET /api/inpatients
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter Query

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter berdasarkan status (aktif, dipindahkan, pulang, dirujuk, meninggal) |
| room_id | integer | No | Filter berdasarkan ID kamar |
| doctor_id | integer | No | Filter berdasarkan ID dokter |
| admission_date_from | date | No | Filter berdasarkan tanggal masuk dari (YYYY-MM-DD) |
| admission_date_to | date | No | Filter berdasarkan tanggal masuk sampai (YYYY-MM-DD) |
| page | integer | No | Nomor halaman (default: 1) |
| per_page | integer | No | Item per halaman (default: 20, max: 100) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 125,
      "inpatient_number": "RI-20240115-0125",
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001",
        "nik": "1234567890123456",
        "gender": "L",
        "age": 34,
        "phone": "08123456789"
      },
      "admission": {
        "date": "2024-01-10",
        "time": "14:30:00",
        "type": "igd",
        "referring_doctor": null
      },
      "current_location": {
        "room": {
          "id": 1,
          "room_number": "301",
          "room_name": "Melati VIP",
          "room_class": "vip",
          "room_class_display": "VIP"
        },
        "bed": {
          "id": 1,
          "bed_number": "301-A"
        },
        "floor": 3,
        "building": "Gedung A"
      },
      "attending_doctor": {
        "id": 8,
        "name": "Dr. Michael Chen",
        "specialization": "Penyakit Dalam"
      },
      "diagnosis": {
        "primary": "Demam Berdarah Dengue",
        "secondary": "Dehidrasi",
        "icd10_code": "A90",
        "icd10_description": "Dengue fever [classical dengue]"
      },
      "status": "aktif",
      "length_of_stay_days": 5,
      "estimated_discharge_date": "2024-01-15",
      "visit_references": [
        {
          "visit_type": "igd",
          "visit_number": "IGD-20240110-0089",
          "visit_date": "2024-01-10"
        }
      ],
      "insurance": {
        "type": "bpjs",
        "number": "0001234567890",
        "sep_number": "0123456789012"
      },
      "created_at": "2024-01-10T14:30:00Z",
      "updated_at": "2024-01-14T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 8,
    "per_page": 20,
    "total": 155,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/inpatients?page=1",
    "last": "/api/inpatients?page=8",
    "prev": null,
    "next": "/api/inpatients?page=2"
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
  "message": "You do not have permission to view inpatients",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Pendaftaran Pasien Rawat Inap

Mendaftarkan pasien baru untuk perawatan rawat inap.

```http
POST /api/inpatients/admit
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_id | integer | Yes | ID Pasien |
| bed_id | integer | Yes | ID Tempat tidur yang akan ditempati |
| doctor_id | integer | Yes | ID Dokter yang menangani |
| admission_date | date | No | Tanggal masuk (default: hari ini) |
| admission_time | time | No | Waktu masuk (default: waktu saat ini) |
| admission_type | string | Yes | Jenis pendaftaran (igd, poliklinik, rujukan, rawat_jalan) |
| referring_doctor | string | No | Nama dokter merujuk (jika ada) |
| referral_letter | string | No | Nomor surat rujukan |
| primary_diagnosis | string | Yes | Diagnosa utama |
| secondary_diagnosis | string | No | Diagnosa sekunder |
| icd10_code | string | No | Kode diagnosa ICD-10 |
| complaint | string | Yes | Keluhan utama pasien |
| history_of_illness | string | No | Riwayat penyakit sekarang |
| visit_id | integer | No | ID Kunjungan referensi (jika dari IGD/poli) |
| companion_name | string | No | Nama penanggung jawab/pendamping |
| companion_relation | string | No | Hubungan dengan pasien |
| companion_phone | string | No | Telepon penanggung jawab |
| estimated_stay_days | integer | No | Perkiraan lama rawat inap |
| deposit_amount | decimal | No | Jumlah deposit awal |

### Contoh Request

```json
{
  "patient_id": 23,
  "bed_id": 2,
  "doctor_id": 8,
  "admission_type": "igd",
  "primary_diagnosis": "Demam Berdarah Dengue",
  "secondary_diagnosis": "Dehidrasi",
  "icd10_code": "A90",
  "complaint": "Demam tinggi sejak 3 hari, muntah, nyeri otot",
  "history_of_illness": "Pasien datang dengan keluhan demam tinggi sejak 3 hari yang lalu, muntah 4x, perdarahan gusi",
  "visit_id": 89,
  "companion_name": "Jane Doe",
  "companion_relation": "Istri",
  "companion_phone": "08198765432",
  "estimated_stay_days": 5,
  "deposit_amount": 5000000
}
```

### Response Sukses (201)

```json
{
  "success": true,
  "message": "Patient admitted successfully",
  "data": {
    "id": 126,
    "inpatient_number": "RI-20240115-0126",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "admission": {
      "date": "2024-01-15",
      "time": "10:30:00",
      "type": "igd",
      "referring_doctor": null
    },
    "location": {
      "room": {
        "id": 2,
        "room_number": "201",
        "room_name": "Mawar Kelas I",
        "room_class": "i"
      },
      "bed": {
        "id": 2,
        "bed_number": "201-A"
      }
    },
    "attending_doctor": {
      "id": 8,
      "name": "Dr. Michael Chen",
      "specialization": "Penyakit Dalam"
    },
    "diagnosis": {
      "primary": "Demam Berdarah Dengue",
      "secondary": "Dehidrasi",
      "icd10_code": "A90"
    },
    "status": "aktif",
    "length_of_stay_days": 0,
    "estimated_discharge_date": "2024-01-20",
    "created_at": "2024-01-15T10:30:00Z"
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

### Response Error (422) - Tempat Tidur Tidak Tersedia

```json
{
  "success": false,
  "message": "Selected bed is not available",
  "error": {
    "code": "BED_NOT_AVAILABLE",
    "details": {
      "bed_id": 2,
      "bed_number": "201-A",
      "current_status": "terisi"
    }
  }
}
```

### Response Error (422) - Pasien Sudah Rawat Inap

```json
{
  "success": false,
  "message": "Patient already has active inpatient admission",
  "error": {
    "code": "ACTIVE_INPATIENT_EXISTS",
    "details": {
      "current_admission_id": 125,
      "inpatient_number": "RI-20240110-0125",
      "admission_date": "2024-01-10"
    }
  }
}
```

---

## Pindah Kamar/Tempat Tidur

Memindahkan pasien rawat inap ke kamar atau tempat tidur lain.

```http
POST /api/inpatients/{id}/transfer
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Parameter URL

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID Rawat Inap |

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| bed_id | integer | Yes | ID Tempat tidur baru |
| transfer_reason | string | Yes | Alasan pemindahan |
| transfer_reason_detail | string | No | Penjelasan detail |
| approved_by | integer | Yes | ID Dokter yang menyetujui pemindahan |
| notes | string | No | Catatan tambahan |

### Opsi Alasan Pemindahan

| Alasan | Deskripsi |
|--------|-------------|
| naik_kelas | Naik kelas kamar |
| turun_kelas | Turun kelas kamar |
| isolasi | Pindah ke kamar isolasi |
| kondisi_kritis | Kondisi kritis memerlukan ICU |
| permintaan_pasien | Permintaan pasien |
| kamar_penuh | Kamar penuh |
| perbaikan | Kamar dalam perbaikan |
| kondisi_medis | Persyaratan kondisi medis |

### Contoh Request

```json
{
  "bed_id": 15,
  "transfer_reason": "naik_kelas",
  "transfer_reason_detail": "Pasien meminta upgrade ke kelas VIP",
  "approved_by": 8,
  "notes": "Upgrade disetujui setelah kondisi stabil"
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Patient transferred successfully",
  "data": {
    "id": 125,
    "inpatient_number": "RI-20240110-0125",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "previous_location": {
      "room": {
        "id": 2,
        "room_number": "201",
        "room_name": "Mawar Kelas I",
        "room_class": "i"
      },
      "bed": {
        "id": 2,
        "bed_number": "201-A"
      }
    },
    "new_location": {
      "room": {
        "id": 1,
        "room_number": "301",
        "room_name": "Melati VIP",
        "room_class": "vip"
      },
      "bed": {
        "id": 15,
        "bed_number": "301-B"
      }
    },
    "transfer": {
      "date": "2024-01-14",
      "time": "09:00:00",
      "reason": "naik_kelas",
      "reason_display": "Naik Kelas",
      "approved_by": {
        "id": 8,
        "name": "Dr. Michael Chen"
      }
    },
    "transfer_history": [
      {
        "transfer_date": "2024-01-14",
        "from_bed": "201-A",
        "to_bed": "301-B",
        "reason": "naik_kelas"
      }
    ],
    "status": "aktif",
    "updated_at": "2024-01-14T09:00:00Z"
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Tempat Tidur Baru Tidak Tersedia

```json
{
  "success": false,
  "message": "Target bed is not available",
  "error": {
    "code": "BED_NOT_AVAILABLE",
    "details": {
      "bed_id": 15,
      "bed_number": "301-B",
      "current_status": "terisi"
    }
  }
}
```

---

## Pulangkan Pasien

Memulangkan pasien rawat inap dari rumah sakit.

```http
POST /api/inpatients/{id}/discharge
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |
| Content-Type | application/json |

### Parameter URL

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID Rawat Inap |

### Body Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| discharge_date | date | No | Tanggal pulang (default: hari ini) |
| discharge_time | time | No | Waktu pulang (default: waktu saat ini) |
| discharge_type | string | Yes | Jenis pulang (pulang, rujuk, meninggal, kabur) |
| discharge_condition | string | Yes | Kondisi pasien saat pulang (sembuh, membaik, belum_sembuh, mati) |
| discharge_notes | string | No | Catatan tambahan saat pulang |
| final_diagnosis | string | Yes | Diagnosa akhir saat pulang |
| icd10_code | string | No | Kode ICD-10 untuk diagnosa akhir |
| follow_up_plan | string | No | Rencana perawatan lanjutan |
| follow_up_date | date | No | Tanggal janji kontrol ulang |
| follow_up_doctor_id | integer | No | ID Dokter untuk kontrol ulang |
| referral_hospital | string | No | Nama rumah sakit rujukan (jika rujuk) |
| referral_reason | string | No | Alasan rujukan (jika rujuk) |
| death_certificate_number | string | No | Nomor surat kematian (jika meninggal) |
| death_cause | string | No | Penyebab kematian (jika meninggal) |
| death_time | time | No | Waktu kematian (jika meninggal) |
| discharged_by | integer | Yes | ID Dokter yang memulangkan pasien |

### Jenis Pulang

| Jenis | Deskripsi |
|------|-------------|
| pulang | Pulang reguler |
| rujuk | Dirujuk ke rumah sakit lain |
| meninggal | Pasien meninggal |
| kabur | Pasien pulang atas permintaan sendiri |

### Kondisi Pulang

| Kondisi | Deskripsi |
|-----------|-------------|
| sembuh | Sembuh total |
| membaik | Membaik |
| belum_sembuh | Belum sembuh total |
| mati | Meninggal |

### Contoh Request

```json
{
  "discharge_type": "pulang",
  "discharge_condition": "membaik",
  "final_diagnosis": "Demam Berdarah Dengue, Sembuh",
  "icd10_code": "A90",
  "discharge_notes": "Kondisi membaik, demam turun, makan dan minum normal",
  "follow_up_plan": "Kontrol ulang 1 minggu, minum obat teratur",
  "follow_up_date": "2024-01-22",
  "follow_up_doctor_id": 8,
  "discharged_by": 8
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Patient discharged successfully",
  "data": {
    "id": 125,
    "inpatient_number": "RI-20240110-0125",
    "patient": {
      "id": 23,
      "name": "John Doe",
      "medical_record_number": "20240101-0001"
    },
    "admission": {
      "date": "2024-01-10",
      "time": "14:30:00"
    },
    "discharge": {
      "date": "2024-01-15",
      "time": "10:00:00",
      "type": "pulang",
      "type_display": "Pulang",
      "condition": "membaik",
      "condition_display": "Membaik",
      "final_diagnosis": "Demam Berdarah Dengue, Sembuh",
      "icd10_code": "A90",
      "length_of_stay_days": 5,
      "discharged_by": {
        "id": 8,
        "name": "Dr. Michael Chen"
      }
    },
    "location_history": [
      {
        "room": "Mawar Kelas I (201-A)",
        "from": "2024-01-10 14:30",
        "to": "2024-01-14 09:00",
        "days": 3
      },
      {
        "room": "Melati VIP (301-B)",
        "from": "2024-01-14 09:00",
        "to": "2024-01-15 10:00",
        "days": 1
      }
    ],
    "follow_up": {
      "date": "2024-01-22",
      "doctor": {
        "id": 8,
        "name": "Dr. Michael Chen"
      },
      "plan": "Kontrol ulang 1 minggu, minum obat teratur"
    },
    "status": "pulang",
    "updated_at": "2024-01-15T10:00:00Z"
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Status Tidak Valid

```json
{
  "success": false,
  "message": "Cannot discharge patient with status: pulang",
  "error": {
    "code": "INVALID_INPATIENT_STATUS",
    "details": {
      "current_status": "pulang",
      "allowed_statuses": ["aktif", "dipindahkan"]
    }
  }
}
```

---

## Ambil Tagihan Pasien

Mengambil tagihan untuk pasien rawat inap.

```http
GET /api/inpatients/{id}/bill
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Parameter URL

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID Rawat Inap |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "inpatient": {
      "id": 125,
      "inpatient_number": "RI-20240110-0125",
      "patient": {
        "id": 23,
        "name": "John Doe",
        "medical_record_number": "20240101-0001"
      },
      "admission_date": "2024-01-10",
      "discharge_date": "2024-01-15",
      "length_of_stay": 5
    },
    "summary": {
      "total_amount": 8750000,
      "discount_amount": 0,
      "tax_amount": 0,
      "grand_total": 8750000,
      "deposit_amount": 5000000,
      "remaining_amount": 3750000,
      "payment_status": "partial"
    },
    "breakdown": [
      {
        "category": "room",
        "category_display": "Kamar",
        "items": [
          {
            "description": "Kamar Mawar Kelas I (201-A)",
            "quantity": 3,
            "unit": "hari",
            "unit_price": 500000,
            "total": 1500000
          },
          {
            "description": "Kamar Melati VIP (301-B)",
            "quantity": 1,
            "unit": "hari",
            "unit_price": 750000,
            "total": 750000
          }
        ],
        "subtotal": 2250000
      },
      {
        "category": "doctor",
        "category_display": "Dokter",
        "items": [
          {
            "description": "Visite Dokter Spesialis",
            "quantity": 5,
            "unit": "kali",
            "unit_price": 200000,
            "total": 1000000
          },
          {
            "description": "Konsultasi",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 150000,
            "total": 150000
          }
        ],
        "subtotal": 1150000
      },
      {
        "category": "laboratory",
        "category_display": "Laboratorium",
        "items": [
          {
            "description": "Darah Lengkap",
            "quantity": 2,
            "unit": "kali",
            "unit_price": 150000,
            "total": 300000
          },
          {
            "description": "Dengue IgG/IgM",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 250000,
            "total": 250000
          }
        ],
        "subtotal": 550000
      },
      {
        "category": "pharmacy",
        "category_display": "Farmasi",
        "items": [
          {
            "description": "Obat-obatan selama rawat inap",
            "quantity": 1,
            "unit": "paket",
            "unit_price": 1500000,
            "total": 1500000
          }
        ],
        "subtotal": 1500000
      },
      {
        "category": "medical_supplies",
        "category_display": "BHP",
        "items": [
          {
            "description": "Infus set, dll",
            "quantity": 1,
            "unit": "paket",
            "unit_price": 800000,
            "total": 800000
          }
        ],
        "subtotal": 800000
      },
      {
        "category": "administration",
        "category_display": "Administrasi",
        "items": [
          {
            "description": "Biaya Administrasi",
            "quantity": 1,
            "unit": "kali",
            "unit_price": 500000,
            "total": 500000
          }
        ],
        "subtotal": 500000
      }
    ],
    "payments": [
      {
        "date": "2024-01-10",
        "amount": 5000000,
        "method": "cash",
        "method_display": "Tunai",
        "description": "Deposit awal"
      }
    ],
    "generated_at": "2024-01-15T10:00:00Z"
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
  "message": "Inpatient not found",
  "error": {
    "code": "INPATIENT_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (403)

```json
{
  "success": false,
  "message": "You do not have permission to view patient bills",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

## Referensi Tipe Data

### Jenis Pendaftaran

| Tipe | Deskripsi |
|------|-------------|
| igd | Instalasi Gawat Darurat |
| poliklinik | Poliklinik |
| rujukan | Rujukan dari fasilitas lain |
| rawat_jalan | Transfer dari rawat jalan |

### Status Rawat Inap

| Status | Deskripsi |
|--------|-------------|
| aktif | Pendaftaran aktif |
| dipindahkan | Dipindahkan |
| pulang | Pulang |
| dirujuk | Dirujuk keluar |
| meninggal | Meninggal dunia |

### Alasan Pemindahan

| Alasan | Deskripsi |
|--------|-------------|
| naik_kelas | Naik kelas kamar |
| turun_kelas | Turun kelas kamar |
| isolasi | Pindah ke isolasi |
| kondisi_kritis | Kondisi kritis |
| permintaan_pasien | Permintaan pasien |
| kamar_penuh | Kamar penuh |
| perbaikan | Perbaikan kamar |
| kondisi_medis | Persyaratan medis |

## Referensi Kode Error

| Code | HTTP Status | Deskripsi |
|------|-------------|-------------|
| `INPATIENT_NOT_FOUND` | 404 | Pasien rawat inap dengan ID yang ditentukan tidak ditemukan |
| `BED_NOT_AVAILABLE` | 422 | Tempat tidur yang dipilih tidak tersedia |
| `ACTIVE_INPATIENT_EXISTS` | 422 | Pasien sudah memiliki pendaftaran rawat inap aktif |
| `INVALID_INPATIENT_STATUS` | 422 | Tidak dapat melakukan aksi dengan status saat ini |
| `INSUFFICIENT_PERMISSIONS` | 403 | Pengguna tidak memiliki izin |
| `VALIDATION_ERROR` | 422 | Validasi request gagal |
