# KGB System API — Postman Collection Guide

## Setup

1. Import `KGB_System_API_v1.postman_collection.json` ke Postman
2. Import `KGB_System_Environment.postman_environment.json` ke Postman
3. Pilih environment **"KGB System - Local"**

## Testing Flow

### 1. Authentication
1. Buka folder **Auth**
2. Jalankan request **Login** — token akan otomatis tersimpan di `{{token}}`
3. Test **Get Current User** untuk verifikasi user yang login

### 2. KGB Testing Flow
Urutannya:
1. **Generate KGB Draft** → dapat KGB ID baru
2. **Get KGB Detail** → cek data hasil generate
3. **Update KGB** → edit jika perlu (draft only)
4. **Submit KGB** → ubah status: draft → diajukan
5. **Verify KGB (Approve/Reject)** → verifikator approve → diajukan → diverifikasi
6. **Approve KGB** → admin approve → diverifikasi → disetujui
7. **Get KGB Snapshot** → lihat data snapshot
8. **Get KGB Document** → download SK (jika sudah ada)

### 3. PMK Testing Flow
1. **Create PMK** → buat PMK baru
2. **Get PMK Detail** → lihat detail
3. **Update PMK** → edit jika perlu (draft only)
4. **List PMK** → lihat semua PMK

### 4. Error Testing
Buka folder **Error Responses** untuk test berbagai case:
- `401 - Invalid Credentials` → login dengan kredensial salah
- `404 - KGB Not Found` → akses ID yang tidak ada
- `422 - Validation Error` → request dengan data tidak valid

## Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@bpsdm.id | password |
| Operator | operator@bpsdm.id | password |
| Verifikator | verifikator@bpsdm.id | password |

## API Endpoints Summary

### Authentication
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | /api/v1/auth/login | Login |
| POST | /api/v1/auth/logout | Logout |
| GET | /api/v1/auth/me | User profile |

### KGB
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/kgb | List KGB |
| POST | /api/v1/kgb/generate | Generate draft |
| GET | /api/v1/kgb/{id} | Detail KGB |
| PUT | /api/v1/kgb/{id} | Update KGB |
| DELETE | /api/v1/kgb/{id} | Hapus KGB |
| POST | /api/v1/kgb/{id}/submit | Ajukan KGB |
| POST | /api/v1/kgb/{id}/verify | Verifikasi KGB |
| POST | /api/v1/kgb/{id}/approve | Approve KGB |
| POST | /api/v1/kgb/{id}/reject | Tolak KGB |
| GET | /api/v1/kgb/{id}/snapshot | Snapshot KGB |
| GET | /api/v1/kgb/{id}/document | Download SK |

### PMK
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/pmk | List PMK |
| POST | /api/v1/pmk | Create PMK |
| GET | /api/v1/pmk/{id} | Detail PMK |
| PUT | /api/v1/pmk/{id} | Update PMK |
| DELETE | /api/v1/pmk/{id} | Hapus PMK |

### SIM-ASN Proxy
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/sim-asn/pegawai | List pegawai |
| GET | /api/v1/sim-asn/pegawai/{id} | Detail pegawai |
| GET | /api/v1/sim-asn/pegawai/{id}/golongan | Riwayat golongan |
| GET | /api/v1/sim-asn/pegawai/{id}/jabatan | Riwayat jabatan |

### Reference Data
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/ref/gaji | List referensi gaji |
| POST | /api/v1/ref/gaji | Create referensi gaji |
| GET | /api/v1/ref/gaji/{id} | Detail referensi gaji |
| PUT | /api/v1/ref/gaji/{id} | Update referensi gaji |
| DELETE | /api/v1/ref/gaji/{id} | Nonaktifkan referensi |

### Dashboard
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/v1/dashboard/stats | Statistik KGB |
| GET | /api/v1/dashboard/pending | KGB pending verification |
| GET | /api/v1/dashboard/upcoming | KGB upcoming (60 hari) |

## KGB Status Flow

```
draft → diajukan → diverifikasi → disetujui
                 ↘ ditolak ↙
```

## Query Parameters (List Endpoints)

| Parameter | Default | Deskripsi |
|-----------|---------|-----------|
| page | 1 | Halaman |
| per_page | 20 | Item per halaman (max 100) |
| status | - | Filter status |
| opd_id | - | Filter OPD |
| tahun | - | Filter tahun |
| sort | created_at | Sorting field |
| dir | desc | Sort direction |
