# API Bedah

Dokumen ini menjelaskan semua endpoint untuk manajemen bedah/ruang operasi (OK) dalam sistem SIMRS.

## Daftar Isi

- [Daftar Operasi Bedah](#daftar-operasi-bedah)
- [Jadwalkan Operasi Bedah](#jadwalkan-operasi-bedah)
- [Dapatkan Detail Operasi Bedah](#dapatkan-detail-operasi-bedah)
- [Mulai Operasi Bedah](#mulai-operasi-bedah)
- [Selesaikan Operasi Bedah](#selesaikan-operasi-bedah)
- [Batalkan Operasi Bedah](#batalkan-operasi-bedah)

---

## Daftar Operasi Bedah

Mengambil daftar operasi bedah dengan filter opsional.

```http
GET /api/surgeries
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter berdasarkan status (dijadwalkan, persiapan, berlangsung, selesai, batal) |
| patient_id | integer | No | Filter berdasarkan ID pasien |
| surgery_type | string | No | Filter berdasarkan tipe (elektif, urgent, cito) |
| date_from | date | No | Filter dari tanggal (YYYY-MM-DD) |
| date_to | date | No | Filter sampai tanggal (YYYY-MM-DD) |
| operating_room_id | integer | No | Filter berdasarkan ID ruang operasi |
| surgeon_id | integer | No | Filter berdasarkan ID dokter bedah |
| page | integer | No | Nomor halaman (default: 1) |
| per_page | integer | No | Item per halaman (default: 20, max: 100) |

### Response Sukses (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 89,
      "surgery_number": "OK-20240115-0089",
      "surgery_type": "elektif",
      "surgery_type_display": "Elektif",
      "patient": {
        "id": 45,
        "name": "Jane Smith",
        "medical_record_number": "20240105-0045",
        "gender": "P",
        "age": 45,
        "blood_type": "A"
      },
      "schedule": {
        "date": "2024-01-15",
        "start_time": "08:00:00",
        "estimated_end_time": "10:00:00",
        "estimated_duration_minutes": 120
      },
      "operating_room": {
        "id": 1,
        "name": "OK 1",
        "code": "OK-01"
      },
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "procedure": {
        "primary": "Appendectomy",
        "icd9_code": "47.0",
        "icd9_description": "Laparoscopic appendectomy"
      },
      "status": "dijadwalkan",
      "inpatient": {
        "id": 78,
        "inpatient_number": "RI-20240114-0078",
        "room": "Melati VIP (301)"
      },
      "created_at": "2024-01-14T16:00:00Z",
      "updated_at": "2024-01-14T16:00:00Z"
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
    "first": "/api/surgeries?page=1",
    "last": "/api/surgeries?page=5",
    "prev": null,
    "next": "/api/surgeries?page=2"
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
  "message": "You do not have permission to view surgeries",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "details": {}
  }
}
```

---

## Jadwalkan Operasi Bedah

Menjadwalkan operasi bedah baru.

```http
POST /api/surgeries
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
| patient_id | integer | Yes | ID pasien |
| surgery_type | string | Yes | Tipe operasi bedah (elektif, urgent, cito) |
| operating_room_id | integer | Yes | ID ruang operasi |
| schedule_date | date | Yes | Tanggal terjadwal (YYYY-MM-DD) |
| schedule_start_time | time | Yes | Waktu mulai terjadwal (HH:MM:SS) |
| estimated_duration_minutes | integer | Yes | Durasi estimasi dalam menit |
| main_surgeon_id | integer | Yes | ID dokter bedah utama |
| assistant_surgeon_ids | array | No | Daftar ID asisten dokter bedah |
| anesthesiologist_id | integer | Yes | ID dokter anestesi |
| procedure_icd9_code | string | Yes | Kode prosedur ICD-9-CM |
| procedure_name | string | Yes | Nama prosedur |
| diagnosis_pre | string | Yes | Diagnosis pre-operasi |
| icd10_code | string | No | Kode diagnosis ICD-10 |
| anesthesia_type | string | Yes | Tipe anestesi |
| inpatient_id | integer | No | ID pasien rawat inap (jika pasien di rawat inap) |
| special_equipment | string | No | Peralatan khusus yang dibutuhkan |
| implants_needed | array | No | Daftar implant yang dibutuhkan |
| blood_reserve_units | integer | No | Unit darah yang direservasi |
| blood_type_needed | string | No | Golongan darah yang dibutuhkan |
| fasting_required | boolean | No | Apakah puasa diperlukan - default: true |
| fasting_hours | integer | No | Durasi puasa dalam jam - default: 8 |
| notes | string | No | Catatan tambahan |
| requested_by | integer | Yes | ID dokter yang meminta operasi bedah |

### Tipe Operasi Bedah

| Type | Description |
|------|-------------|
| elektif | Elektif - dijadwalkan sebelumnya |
| urgent | Urgent - dalam 24 jam |
| cito | Darurat - segera |

### Tipe Anestesi

| Type | Description |
|------|-------------|
| general | Anestesi umum |
| spinal | Anestesi spinal |
| epidural | Anestesi epidural |
| local | Anestesi lokal |
| regional | Blok regional |

### Contoh Request

```json
{
  "patient_id": 45,
  "surgery_type": "elektif",
  "operating_room_id": 1,
  "schedule_date": "2024-01-15",
  "schedule_start_time": "08:00:00",
  "estimated_duration_minutes": 120,
  "main_surgeon_id": 12,
  "assistant_surgeon_ids": [13],
  "anesthesiologist_id": 15,
  "procedure_icd9_code": "47.0",
  "procedure_name": "Laparoscopic Appendectomy",
  "diagnosis_pre": "Acute Appendicitis",
  "icd10_code": "K35",
  "anesthesia_type": "general",
  "inpatient_id": 78,
  "special_equipment": "Laparoscopy set",
  "implants_needed": [],
  "blood_reserve_units": 2,
  "blood_type_needed": "A+",
  "fasting_required": true,
  "fasting_hours": 8,
  "notes": "Pasien sudah puasa sejak tengah malam",
  "requested_by": 8
}
```

### Response Sukses (201)

```json
{
  "success": true,
  "message": "Surgery scheduled successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "surgery_type": "elektif",
    "surgery_type_display": "Elektif",
    "patient": {
      "id": 45,
      "name": "Jane Smith",
      "medical_record_number": "20240105-0045",
      "gender": "P",
      "age": 45,
      "blood_type": "A"
    },
    "schedule": {
      "date": "2024-01-15",
      "start_time": "08:00:00",
      "estimated_end_time": "10:00:00",
      "estimated_duration_minutes": 120
    },
    "operating_room": {
      "id": 1,
      "name": "OK 1",
      "code": "OK-01"
    },
    "team": {
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "assistant_surgeons": [
        {
          "id": 13,
          "name": "Dr. Assistant Surgeon",
          "specialization": "Bedah Umum"
        }
      ],
      "anesthesiologist": {
        "id": 15,
        "name": "Dr. Anesthesiologist",
        "specialization": "Anestesi"
      },
      "nurses": []
    },
    "procedure": {
      "primary": "Laparoscopic Appendectomy",
      "icd9_code": "47.0",
      "icd9_description": "Laparoscopic appendectomy"
    },
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "icd10_code": "K35",
      "icd10_description": "Acute appendicitis"
    },
    "anesthesia": {
      "type": "general",
      "type_display": "General"
    },
    "preparation": {
      "fasting_required": true,
      "fasting_hours": 8,
      "blood_reserved_units": 2,
      "blood_type": "A+"
    },
    "status": "dijadwalkan",
    "status_display": "Dijadwalkan",
    "inpatient": {
      "id": 78,
      "inpatient_number": "RI-20240114-0078"
    },
    "created_at": "2024-01-14T16:30:00Z"
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

### Response Error (422) - Ruang Tidak Tersedia

```json
{
  "success": false,
  "message": "Operating room not available at selected time",
  "error": {
    "code": "ROOM_NOT_AVAILABLE",
    "details": {
      "operating_room_id": 1,
      "room_name": "OK 1",
      "conflicting_surgery": "OK-20240115-0089",
      "time": "2024-01-15 08:00"
    }
  }
}
```

### Response Error (422) - Dokter Bedah Tidak Tersedia

```json
{
  "success": false,
  "message": "Surgeon not available at selected time",
  "error": {
    "code": "SURGEON_NOT_AVAILABLE",
    "details": {
      "surgeon_id": 12,
      "surgeon_name": "Dr. Surgeon Specialist",
      "conflicting_surgery": "OK-20240115-0085"
    }
  }
}
```

---

## Dapatkan Detail Operasi Bedah

Mengambil informasi detail tentang operasi bedah tertentu.

```http
GET /api/surgeries/{id}
```

### Headers

| Name | Value |
|------|-------|
| Authorization | Bearer {token} |
| Accept | application/json |

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | ID operasi bedah |

### Response Sukses (200)

```json
{
  "success": true,
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "surgery_type": "elektif",
    "surgery_type_display": "Elektif",
    "patient": {
      "id": 45,
      "name": "Jane Smith",
      "medical_record_number": "20240105-0045",
      "gender": "P",
      "age": 45,
      "date_of_birth": "1979-03-15",
      "blood_type": "A",
      "weight_kg": 65,
      "height_cm": 165,
      "allergies": ["Penicillin"]
    },
    "schedule": {
      "date": "2024-01-15",
      "start_time": "08:00:00",
      "estimated_end_time": "10:00:00",
      "estimated_duration_minutes": 120
    },
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z",
      "surgery_end": "2024-01-15T09:45:00Z",
      "patient_out": "2024-01-15T10:00:00Z"
    },
    "operating_room": {
      "id": 1,
      "name": "OK 1",
      "code": "OK-01"
    },
    "team": {
      "main_surgeon": {
        "id": 12,
        "name": "Dr. Surgeon Specialist",
        "specialization": "Bedah Umum"
      },
      "assistant_surgeons": [
        {
          "id": 13,
          "name": "Dr. Assistant Surgeon",
          "specialization": "Bedah Umum"
        }
      ],
      "anesthesiologist": {
        "id": 15,
        "name": "Dr. Anesthesiologist",
        "specialization": "Anestesi"
      },
      "nurses": [
        {
          "id": 20,
          "name": "Suster A",
          "role": "Instrument"
        },
        {
          "id": 21,
          "name": "Suster B",
          "role": "Sirkuler"
        }
      ]
    },
    "procedure": {
      "primary": "Laparoscopic Appendectomy",
      "icd9_code": "47.0",
      "icd9_description": "Laparoscopic appendectomy"
    },
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "post": "Acute Suppurative Appendicitis",
      "icd10_code": "K35",
      "icd10_description": "Acute appendicitis"
    },
    "anesthesia": {
      "type": "general",
      "type_display": "General",
      "notes": "Induksi dengan Propofol, maintenance dengan Sevoflurane"
    },
    "safety_checklist": {
      "sign_in": {
        "completed": true,
        "completed_at": "2024-01-15T08:10:00Z",
        "identity_verified": true,
        "procedure_verified": true,
        "consent_signed": true,
        "site_marked": true,
        "allergy_checked": true
      },
      "time_out": {
        "completed": true,
        "completed_at": "2024-01-15T08:15:00Z",
        "team_introduced": true,
        "procedure_confirmed": true,
        "anticipated_problems": "None"
      },
      "sign_out": {
        "completed": true,
        "completed_at": "2024-01-15T09:45:00Z",
        "instrument_count_correct": true,
        "specimen_labeled": true,
        "equipment_problems": false
      }
    },
    "implants_used": [],
    "specimens": [
      {
        "type": "Appendix",
        "sent_to_pathology": true,
        "pathology_number": "PATH-20240115-0045"
      }
    ],
    "complications": null,
    "blood_transfusion": {
      "units_used": 0,
      "blood_loss_ml": 50
    },
    "status": "selesai",
    "status_display": "Selesai",
    "report": "Appendiks yang mengalami inflamasi akut dengan eksudat purulen dihilangkan dengan teknik laparoskopi. Tidak ada komplikasi intraoperatif. Luka ditutup primer.",
    "inpatient": {
      "id": 78,
      "inpatient_number": "RI-20240114-0078",
      "room": "Melati VIP (301)"
    },
    "timeline": [
      {
        "timestamp": "2024-01-14T16:30:00Z",
        "event": "Surgery scheduled",
        "user": "Dr. Surgeon Specialist"
      },
      {
        "timestamp": "2024-01-15T07:45:00Z",
        "event": "Patient entered OR",
        "user": "Suster A"
      },
      {
        "timestamp": "2024-01-15T08:15:00Z",
        "event": "Surgery started",
        "user": "Dr. Surgeon Specialist"
      },
      {
        "timestamp": "2024-01-15T09:45:00Z",
        "event": "Surgery completed",
        "user": "Dr. Surgeon Specialist"
      }
    ],
    "created_at": "2024-01-14T16:30:00Z",
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
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

---

## Mulai Operasi Bedah

Menandai operasi bedah sebagai dimulai/sedang berlangsung.

```http
PUT /api/surgeries/{id}/start
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
| id | integer | ID operasi bedah |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| patient_in_at | datetime | No | Waktu pasien masuk OK (default: waktu saat ini) |
| anesthesia_start_at | datetime | No | Waktu mulai anestesi |
| surgery_start_at | datetime | No | Waktu insisi operasi |
| started_by | integer | Yes | ID user yang memulai operasi |
| safety_checklist_sign_in | object | Yes | Checklist sign-in |
| safety_checklist_sign_in.identity_verified | boolean | Yes | Identitas pasien diverifikasi |
| safety_checklist_sign_in.procedure_verified | boolean | Yes | Prosedur diverifikasi |
| safety_checklist_sign_in.consent_signed | boolean | Yes | Formulir persetujuan ditandatangani |
| safety_checklist_sign_in.site_marked | boolean | Yes | Lokasi operasi ditandai |
| safety_checklist_sign_in.allergy_checked | boolean | Yes | Alergi dicek |

### Contoh Request

```json
{
  "patient_in_at": "2024-01-15T07:45:00Z",
  "anesthesia_start_at": "2024-01-15T08:00:00Z",
  "surgery_start_at": "2024-01-15T08:15:00Z",
  "started_by": 12,
  "safety_checklist_sign_in": {
    "identity_verified": true,
    "procedure_verified": true,
    "consent_signed": true,
    "site_marked": true,
    "allergy_checked": true
  }
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Surgery started successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "berlangsung",
    "status_display": "Berlangsung",
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z"
    },
    "safety_checklist": {
      "sign_in": {
        "completed": true,
        "completed_at": "2024-01-15T08:10:00Z",
        "identity_verified": true,
        "procedure_verified": true,
        "consent_signed": true,
        "site_marked": true,
        "allergy_checked": true
      }
    },
    "started_by": {
      "id": 12,
      "name": "Dr. Surgeon Specialist"
    },
    "started_at": "2024-01-15T08:15:00Z"
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
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Status Tidak Valid

```json
{
  "success": false,
  "message": "Cannot start surgery with status: selesai",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["dijadwalkan", "persiapan"]
    }
  }
}
```

---

## Selesaikan Operasi Bedah

Menandai operasi bedah sebagai selesai.

```http
PUT /api/surgeries/{id}/complete
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
| id | integer | ID operasi bedah |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| surgery_end_at | datetime | No | Waktu selesai operasi (default: waktu saat ini) |
| patient_out_at | datetime | No | Waktu pasien keluar |
| diagnosis_post | string | Yes | Diagnosis pasca-operasi |
| procedure_description | string | Yes | Deskripsi prosedur detail |
| complications | string | No | Komplikasi selama operasi |
| blood_loss_ml | integer | No | Perkiraan kehilangan darah dalam ml |
| blood_transfusion_units | integer | No | Unit darah yang ditransfusikan |
| implants_used | array | No | Daftar implant yang digunakan |
| specimens | array | No | Daftar spesimen yang diambil |
| safety_checklist_sign_out | object | Yes | Checklist sign-out |
| safety_checklist_sign_out.instrument_count_correct | boolean | Yes | Hitungan instrumen benar |
| safety_checklist_sign_out.specimen_labeled | boolean | Yes | Spesimen dilabeli |
| safety_checklist_sign_out.equipment_problems | boolean | Yes | Masalah peralatan |
| completed_by | integer | Yes | ID user yang menyelesaikan operasi |
| report | string | Yes | Laporan operasi |

### Contoh Request

```json
{
  "surgery_end_at": "2024-01-15T09:45:00Z",
  "patient_out_at": "2024-01-15T10:00:00Z",
  "diagnosis_post": "Acute Suppurative Appendicitis",
  "procedure_description": "Laparoscopic appendectomy dengan 3 port. Appendiks dihilangkan menggunakan stapler endoscopic. Abdomen diirrigation dan hemostasis tercapai.",
  "complications": null,
  "blood_loss_ml": 50,
  "blood_transfusion_units": 0,
  "implants_used": [],
  "specimens": [
    {
      "type": "Appendix",
      "sent_to_pathology": true
    }
  ],
  "safety_checklist_sign_out": {
    "instrument_count_correct": true,
    "specimen_labeled": true,
    "equipment_problems": false
  },
  "completed_by": 12,
  "report": "Appendiks yang mengalami inflamasi akut dengan eksudat purulen dihilangkan dengan teknik laparoskopi. Tidak ada komplikasi intraoperatif. Luka ditutup primer."
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Surgery completed successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "selesai",
    "status_display": "Selesai",
    "actual_times": {
      "patient_in": "2024-01-15T07:45:00Z",
      "anesthesia_start": "2024-01-15T08:00:00Z",
      "surgery_start": "2024-01-15T08:15:00Z",
      "surgery_end": "2024-01-15T09:45:00Z",
      "patient_out": "2024-01-15T10:00:00Z"
    },
    "actual_duration_minutes": 90,
    "diagnosis": {
      "pre": "Acute Appendicitis",
      "post": "Acute Suppurative Appendicitis"
    },
    "blood_transfusion": {
      "units_used": 0,
      "blood_loss_ml": 50
    },
    "specimens": [
      {
        "type": "Appendix",
        "sent_to_pathology": true,
        "pathology_number": "PATH-20240115-0045"
      }
    ],
    "safety_checklist": {
      "sign_out": {
        "completed": true,
        "completed_at": "2024-01-15T09:45:00Z",
        "instrument_count_correct": true,
        "specimen_labeled": true,
        "equipment_problems": false
      }
    },
    "completed_by": {
      "id": 12,
      "name": "Dr. Surgeon Specialist"
    },
    "completed_at": "2024-01-15T10:00:00Z"
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
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Status Tidak Valid

```json
{
  "success": false,
  "message": "Cannot complete surgery with status: dijadwalkan",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "dijadwalkan",
      "allowed_statuses": ["berlangsung"]
    }
  }
}
```

---

## Batalkan Operasi Bedah

Membatalkan operasi bedah yang terjadwal.

```http
PUT /api/surgeries/{id}/cancel
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
| id | integer | ID operasi bedah |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| cancel_reason | string | Yes | Alasan pembatalan |
| cancel_reason_detail | string | No | Penjelasan detail |
| cancelled_by | integer | Yes | ID user yang membatalkan operasi |

### Alasan Pembatalan

| Reason | Description |
|--------|-------------|
| kondisi_pasien | Kondisi pasien |
| permintaan_pasien | Permintaan pasien |
| alasan_medis | Alasan medis |
| kamar_tidak_tersedia | Ruangan tidak tersedia |
| dokter_tidak_tersedia | Dokter tidak tersedia |
| jadwal_ulang | Dijadwal ulang |
| administrasi | Administrasi |

### Contoh Request

```json
{
  "cancel_reason": "kondisi_pasien",
  "cancel_reason_detail": "Pasien mengalami demam tinggi, operasi ditunda hingga kondisi stabil",
  "cancelled_by": 12
}
```

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Surgery cancelled successfully",
  "data": {
    "id": 90,
    "surgery_number": "OK-20240115-0090",
    "status": "batal",
    "status_display": "Dibatalkan",
    "cancellation": {
      "reason": "kondisi_pasien",
      "reason_display": "Kondisi Pasien",
      "detail": "Pasien mengalami demam tinggi, operasi ditunda hingga kondisi stabil",
      "cancelled_at": "2024-01-14T20:00:00Z",
      "cancelled_by": {
        "id": 12,
        "name": "Dr. Surgeon Specialist"
      }
    },
    "reschedule_info": {
      "can_reschedule": true,
      "new_schedule_link": "/api/surgeries?reschedule_from=90"
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
  "message": "Surgery not found",
  "error": {
    "code": "SURGERY_NOT_FOUND",
    "details": {}
  }
}
```

### Response Error (422) - Status Tidak Valid

```json
{
  "success": false,
  "message": "Cannot cancel surgery with status: selesai",
  "error": {
    "code": "INVALID_SURGERY_STATUS",
    "details": {
      "current_status": "selesai",
      "allowed_statuses": ["dijadwalkan", "persiapan"]
    }
  }
}
```

## Referensi Tipe Data

### Tipe Operasi Bedah

| Type | Description |
|------|-------------|
| elektif | Elektif - dijadwalkan sebelumnya |
| urgent | Urgent - dalam 24 jam |
| cito | Darurat - segera |

### Status Operasi Bedah

| Status | Description |
|--------|-------------|
| dijadwalkan | Terjadwal |
| persiapan | Persiapan |
| berlangsung | Sedang berlangsung |
| selesai | Selesai |
| batal | Dibatalkan |

### Tipe Anestesi

| Type | Description |
|------|-------------|
| general | Anestesi umum |
| spinal | Anestesi spinal |
| epidural | Anestesi epidural |
| local | Anestesi lokal |
| regional | Blok regional |

### Alasan Pembatalan

| Reason | Description |
|--------|-------------|
| kondisi_pasien | Kondisi pasien |
| permintaan_pasien | Permintaan pasien |
| alasan_medis | Alasan medis |
| kamar_tidak_tersedia | Ruangan tidak tersedia |
| dokter_tidak_tersedia | Dokter tidak tersedia |
| jadwal_ulang | Dijadwal ulang |
| administrasi | Administrasi |

## Referensi Kode Error

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `SURGERY_NOT_FOUND` | 404 | Operasi bedah dengan ID yang ditentukan tidak ditemukan |
| `ROOM_NOT_AVAILABLE` | 422 | Ruang operasi tidak tersedia pada waktu yang dipilih |
| `SURGEON_NOT_AVAILABLE` | 422 | Dokter bedah tidak tersedia pada waktu yang dipilih |
| `INVALID_SURGERY_STATUS` | 422 | Tidak dapat melakukan aksi dengan status saat ini |
| `INSUFFICIENT_PERMISSIONS` | 403 | User tidak memiliki izin |
| `VALIDATION_ERROR` | 422 | Validasi request gagal |
