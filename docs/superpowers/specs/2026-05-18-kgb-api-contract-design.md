# KGB System — API Contract Design Spec
**Date:** 2026-05-18
**Author:** Claude Code + User Collaboration
**Status:** Draft — Pending User Review

---

## 1. Response Standard

### 1.1 Success Response

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2026-05-18T10:30:00Z",
    "version": "v1"
  }
}
```

**Pagination Response:**
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "timestamp": "2026-05-18T10:30:00Z",
    "version": "v1",
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "total_pages": 8,
      "from": 1,
      "to": 20
    }
  }
}
```

### 1.2 Error Response

```json
{
  "success": false,
  "error": {
    "code": "KGB_NOT_FOUND",
    "message": "Data KGB tidak ditemukan",
    "details": {}
  },
  "meta": {
    "timestamp": "2026-05-18T10:30:00Z",
    "version": "v1",
    "request_id": "req_abc123"
  }
}
```

**HTTP Status Codes:**
- `200` — Success
- `201` — Created
- `400` — Bad Request (validation failed)
- `401` — Unauthorized (no token / invalid token)
- `403` — Forbidden (insufficient role)
- `404` — Not Found
- `409` — Conflict (state transition invalid)
- `422` — Unprocessable Entity (business rule violation)
- `500` — Internal Server Error

### 1.3 Error Code Convention

Format: `{DOMAIN}_{CODE}`
Contoh:

| Code | HTTP | Description |
|------|------|-------------|
| `AUTH_INVALID_CREDENTIALS` | 401 | Login gagal |
| `AUTH_TOKEN_EXPIRED` | 401 | Token expired |
| `AUTH_FORBIDDEN` | 403 | Role tidak punya akses |
| `KGB_NOT_FOUND` | 404 | KGB tidak ditemukan |
| `KGB_INVALID_TRANSITION` | 409 | State transition tidak valid |
| `KGB_VALIDATION_FAILED` | 400 | Validasi gagal |
| `PMK_NOT_FOUND` | 404 | PMK tidak ditemukan |
| `SIMASN_CONNECTION_FAILED` | 503 | Gagal koneksi ke SIM-ASN |
| `REF_GAJI_NOT_FOUND` | 422 | Referensi gaji tidak ditemukan untuk golongan/masa kerja |

---

## 2. API Endpoints

### Base URL: `/api/v1`

### 2.1 Authentication

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/api/v1/auth/login` | Login | Public |
| POST | `/api/v1/auth/logout` | Logout | Authenticated |
| GET | `/api/v1/auth/me` | Get current user profile | Authenticated |

**POST /api/v1/auth/login**
```json
Request:
{
  "email": "admin@bpsdm.id",
  "password": "secret"
}

Response (200):
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin BKPSDM",
      "email": "admin@bpsdm.id",
      "role": "admin"
    },
    "token": "1|abc123..."
  }
}
```

**GET /api/v1/auth/me**
```json
Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin BKPSDM",
    "email": "admin@bpsdm.id",
    "role": "admin",
    "permissions": ["kgb.create", "kgb.verify", "kgb.approve", "pmk.create", "ref_gaji.manage"]
  }
}
```

---

### 2.2 KGB Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/v1/kgb` | List KGB (paginated + filter) | Authenticated |
| POST | `/api/v1/kgb/generate` | Generate KGB draft | Operator+ |
| GET | `/api/v1/kgb/{id}` | Get KGB detail | Authenticated |
| PUT | `/api/v1/kgb/{id}` | Update KGB draft | Operator (draft only) |
| DELETE | `/api/v1/kgb/{id}` | Delete KGB draft | Operator (draft only) |
| POST | `/api/v1/kgb/{id}/submit` | Submit KGB for verification | Operator (draft) |
| POST | `/api/v1/kgb/{id}/verify` | Verify KGB | Verifikator+ |
| POST | `/api/v1/kgb/{id}/approve` | Approve KGB | Admin |
| POST | `/api/v1/kgb/{id}/reject` | Reject KGB | Verifikator+ |
| GET | `/api/v1/kgb/{id}/snapshot` | Get KGB snapshot | Authenticated |
| GET | `/api/v1/kgb/{id}/document` | Download SK KGB | Authenticated |

**GET /api/v1/kgb — Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page (max 100) |
| `status` | string | - | Filter by status |
| `opd_id` | int | - | Filter by OPD |
| `pegawai_id` | int | - | Filter by pegawai |
| `tahun` | int | - | Filter by tahun KGB |
| `sort` | string | `created_at` | Sort field |
| `dir` | string | `desc` | Sort direction (asc/desc) |

**Status enum values:** `draft`, `diajukan`, `divverifikasi`, `disetujui`, `ditolak`

**GET /api/v1/kgb — Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "pegawai_id": 10,
      "pegawai": {
        "nip": "197001011990011001",
        "nama": "Budi Santoso"
      },
      "status": "draft",
      "golongan": "IV/a",
      "masa_kerja_tahun": 16,
      "gaji_lama": 5400000,
      "gaji_baru": 5850000,
      "tmt_kgb_baru": "2026-06-01",
      "created_at": "2026-05-15T10:00:00Z"
    }
  ],
  "meta": {
    "timestamp": "2026-05-18T10:30:00Z",
    "version": "v1",
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45,
      "total_pages": 3,
      "from": 1,
      "to": 20
    }
  }
}
```

**POST /api/v1/kgb/generate — Request:**
```json
{
  "pegawai_id": 10,
  "pmk_id": null
}
```

**POST /api/v1/kgb/generate — Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "pegawai_id": 10,
    "status": "draft",
    "golongan": "IV/a",
    "masa_kerja_tahun": 16,
    "masa_kerja_bulan": 3,
    "gaji_lama": 5400000,
    "gaji_baru": 5850000,
    "tmt_kgb_baru": "2026-06-01",
    "tmt_kgb_lama": "2024-06-01",
    "masa_kerja_golongan": "16 tahun 3 bulan",
    "snapshot_id": 8,
    "created_at": "2026-05-18T10:30:00Z"
  }
}
```

**POST /api/v1/kgb/{id}/verify — Request:**
```json
{
  "action": "approve",
  "notes": "Data sudah sesuai"
}
```
`action`: `approve` | `reject`

**POST /api/v1/kgb/{id}/approve — Request:**
```json
{
  "notes": "Approved"
}
```

---

### 2.3 PMK Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/v1/pmk` | List PMK (paginated + filter) | Authenticated |
| POST | `/api/v1/pmk` | Create PMK | Operator+ |
| GET | `/api/v1/pmk/{id}` | Get PMK detail | Authenticated |
| PUT | `/api/v1/pmk/{id}` | Update PMK | Operator (draft only) |
| DELETE | `/api/v1/pmk/{id}` | Delete PMK | Operator (draft only) |

**GET /api/v1/pmk — Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `pegawai_id` | int | - | Filter by pegawai |
| `tahun` | int | - | Filter by tahun PMK |
| `sort` | string | `created_at` | Sort field |
| `dir` | string | `desc` | Sort direction |

**POST /api/v1/pmk — Request:**
```json
{
  "pegawai_id": 10,
  "no_sk": "821.3/005/2026",
  "tanggal_sk": "2026-01-15",
  "masa_kerja_lama_tahun": 14,
  "masa_kerja_lama_bulan": 0,
  "masa_kerja_baru_tahun": 16,
  "masa_kerja_baru_bulan": 3,
  "file_sk": "base64_encoded_pdf_or_image",
  "keterangan": "PMK karena kenaikan pangkat"
}
```

---

### 2.4 SIM-ASN Proxy Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/v1/sim-asn/pegawai` | List pegawai from SIM-ASN | Authenticated |
| GET | `/api/v1/sim-asn/pegawai/{id}` | Get pegawai detail | Authenticated |
| GET | `/api/v1/sim-asn/pegawai/{id}/golongan` | Get riwayat golongan | Authenticated |
| GET | `/api/v1/sim-asn/pegawai/{id}/jabatan` | Get riwayat jabatan | Authenticated |

**GET /api/v1/sim-asn/pegawai — Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page |
| `opd_id` | int | - | Filter by OPD |
| `search` | string | - | Search by nama/NIP |
| `nama` | string | - | Filter by nama |

---

### 2.5 Reference Data Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/v1/ref/gaji` | List referensi gaji | Authenticated |
| POST | `/api/v1/ref/gaji` | Create referensi gaji | Admin |
| GET | `/api/v1/ref/gaji/{id}` | Get referensi gaji detail | Authenticated |
| PUT | `/api/v1/ref/gaji/{id}` | Update referensi gaji | Admin |
| DELETE | `/api/v1/ref/gaji/{id}` | Delete referensi gaji | Admin |

**GET /api/v1/ref/gaji — Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 50 | Items per page |
| `golongan` | string | - | Filter by golongan |
| `masa_kerja_tahun` | int | - | Filter by masa kerja tahun |

---

### 2.6 Dashboard / Monitoring Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/v1/dashboard/stats` | Get dashboard statistics | Authenticated |
| GET | `/api/v1/dashboard/pending` | List KGB pending verification | Authenticated |
| GET | `/api/v1/dashboard/upcoming` | List KGB upcoming (60 days) | Authenticated |

---

## 3. Request Validation Structure

### 3.1 Naming Convention

```
StoreKgbRequest.php     — Validasi POST /api/v1/kgb/generate
UpdateKgbRequest.php     — Validasi PUT /api/v1/kgb/{id}
SubmitKgbRequest.php     — Validasi POST /api/v1/kgb/{id}/submit
VerifyKgbRequest.php     — Validasi POST /api/v1/kgb/{id}/verify
ApproveKgbRequest.php    — Validasi POST /api/v1/kgb/{id}/approve
RejectKgbRequest.php     — Validasi POST /api/v1/kgb/{id}/reject

StorePmkRequest.php      — Validasi POST /api/v1/pmk
UpdatePmkRequest.php     — Validasi PUT /api/v1/pmk/{id}

StoreRefGajiRequest.php   — Validasi POST /api/v1/ref/gaji
UpdateRefGajiRequest.php — Validasi PUT /api/v1/ref/gaji/{id}

LoginRequest.php         — Validasi POST /api/v1/auth/login
```

### 3.2 Location

```
app/Http/Requests/
├── Auth/
│   └── LoginRequest.php
├── Kgb/
│   ├── StoreKgbRequest.php
│   ├── UpdateKgbRequest.php
│   ├── VerifyKgbRequest.php
│   ├── ApproveKgbRequest.php
│   └── RejectKgbRequest.php
├── Pmk/
│   ├── StorePmkRequest.php
│   └── UpdatePmkRequest.php
├── RefGaji/
│   ├── StoreRefGajiRequest.php
│   └── UpdateRefGajiRequest.php
└── SimAsn/
    └── SearchPegawaiRequest.php
```

---

## 4. Error Code Reference

### 4.1 Auth Errors

| Code | HTTP | Message |
|------|------|---------|
| `AUTH_INVALID_CREDENTIALS` | 401 | Email atau password salah |
| `AUTH_TOKEN_EXPIRED` | 401 | Token sudah expire, silakan login ulang |
| `AUTH_TOKEN_INVALID` | 401 | Token tidak valid |
| `AUTH_FORBIDDEN` | 403 | Anda tidak memiliki akses ke resource ini |

### 4.2 KGB Errors

| Code | HTTP | Message |
|------|------|---------|
| `KGB_NOT_FOUND` | 404 | Data KGB tidak ditemukan |
| `KGB_ALREADY_SUBMITTED` | 409 | KGB sudah diajukan, tidak bisa diedit |
| `KGB_INVALID_TRANSITION` | 409 | Transisi status tidak valid |
| `KGB_CANNOT_MODIFY` | 409 | KGB sudah diverifikasi/disetujui, tidak bisa diubah |
| `KGB_VALIDATION_FAILED` | 422 | Data KGB tidak valid |
| `KGB_PEGAWAI_NOT_FOUND` | 404 | Data pegawai tidak ditemukan di SIM-ASN |

### 4.3 PMK Errors

| Code | HTTP | Message |
|------|------|---------|
| `PMK_NOT_FOUND` | 404 | Data PMK tidak ditemukan |
| `PMK_VALIDATION_FAILED` | 422 | Data PMK tidak valid |

### 4.4 Calculation Errors

| Code | HTTP | Message |
|------|------|---------|
| `REF_GAJI_NOT_FOUND` | 422 | Referensi gaji tidak ditemukan untuk golongan {golongan} masa kerja {tahun} tahun |
| `SIMASN_CONNECTION_FAILED` | 503 | Gagal terhubung ke sistem SIM-ASN |
| `SIMASN_DATA_NOT_FOUND` | 404 | Data tidak ditemukan di SIM-ASN |

### 4.5 System Errors

| Code | HTTP | Message |
|------|------|---------|
| `SYSTEM_ERROR` | 500 | Terjadi kesalahan sistem |
| `VALIDATION_ERROR` | 400 | Validasi request gagal |

---

## 5. Implementation Checklist

- [ ] Create `ApiResponse` helper class
- [ ] Create `ApiError` exception class
- [ ] Create `ApiErrorCode` enum
- [ ] Setup Form Request base class
- [ ] Setup exception handler for API
- [ ] Implement Auth endpoints
- [ ] Implement KGB endpoints
- [ ] Implement PMK endpoints
- [ ] Implement SIM-ASN proxy endpoints
- [ ] Implement Reference data endpoints
- [ ] Create Postman collection

---

*Document ini akan dipakai sebagai dasar implementasi. Review dan approve sebelum proceed ke Step 2 (Service Implementation).*