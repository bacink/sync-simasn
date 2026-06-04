# SKPD Rename & Schema Extension — Design Spec
**Date:** 2026-06-04
**Author:** Claude Code + User Collaboration
**Status:** Draft — Pending User Review

---

## 1. Goal

Rename the `opds` table to `skpd`, extend its schema with new columns (`urutan`, `singkatan`, `logo`, `id_jabatan_kepala`, `is_active`), rename the `Opd` model to `Skpd`, rename all FK columns (`opd_id` → `skpd_id`) across the codebase, and add a full CRUD API for the new `skpd` resource.

---

## 2. Database Schema

### 2.1 New `skpd` Table

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint unsigned | PK, auto-increment | |
| `urutan` | unsigned integer | default `0`, not null | display/sort order |
| `nama` | varchar(255) | unique, not null | full name |
| `singkatan` | varchar(50) | unique, not null | replaces `kode` |
| `logo` | varchar(255) | nullable | storage path |
| `id_jabatan_kepala` | unsigned big integer | nullable, no FK | per user decision |
| `is_active` | boolean | default `true`, not null | per user decision |
| `created_at` | timestamp | nullable | |
| `updated_at` | timestamp | nullable | |

**Indexes:**
- Unique on `nama`
- Unique on `singkatan`
- Standard on `id` (PK)

### 2.2 FK Column Renames

| Table | Old Column | New Column | References |
|-------|-----------|-----------|------------|
| `users` | `opd_id` | `skpd_id` | `skpd.id`, nullOnDelete |
| `riwayat_kgb` | `opd_id` | `skpd_id` | `skpd.id`, nullOnDelete |
| `riwayat_pmk` | `opd_id` | `skpd_id` | `skpd.id`, nullOnDelete |

---

## 3. Migration Strategy

### 3.1 File 1 — Edit Original Migration

Edit `database/migrations/2026_05_18_000100_add_opd_to_users_and_create_opds_table.php`:

- Rename method to `add_skpd_to_users_and_create_skpd_table` (cosmetic).
- Replace `opds` table creation with new `skpd` schema.
- Rename `users.opd_id` → `users.skpd_id`, FK to `skpd.id`.
- Update `down()` to drop FK + column + table.

This makes fresh installs get the new schema directly.

### 3.2 File 2 — New Idempotent Migration

Create `database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php`:

- For existing dev DBs: rename `opds` → `skpd` via `Schema::rename()` if `opds` exists.
- Add new columns to `skpd` if missing (`Schema::hasColumn()` guards).
- Rename `users.opd_id` → `users.skpd_id` if old column exists, recreate FK.
- Same for `riwayat_kgb` and `riwayat_pmk`.
- Full `down()` to reverse each step.

This is a no-op on fresh DBs (where File 1 already created the right schema).

---

## 4. Models

### 4.1 `Skpd` Model (new, replaces `Opd`)

File: `app/Models/Skpd.php` (new file)

File: `app/Models/Opd.php` — **delete this file**

- `$table = 'skpd'`
- `$fillable = ['urutan', 'nama', 'singkatan', 'logo', 'id_jabatan_kepala', 'is_active']`
- `$casts = ['is_active' => 'boolean']`
- `users(): HasMany` — `hasMany(User::class)`
- `riwayatKgb(): HasMany` — `hasMany(RiwayatKgb::class)`
- `riwayatPmk(): HasMany` — `hasMany(RiwayatPmk::class)`
- `scopeActive($query)` — `where('is_active', true)`
- `scopeSearch($query, ?string $term)` — `where('nama', 'like', "%{$term}%")->orWhere('singkatan', 'like', "%{$term}%")`

### 4.2 `User` Model (edit)

- `$fillable`: replace `'opd_id'` with `'skpd_id'`
- Rename `opd()` → `skpd()`, return `belongsTo(Skpd::class)`

### 4.3 `RiwayatKgb` Model (edit)

- `$fillable`: replace `'opd_id'` with `'skpd_id'`
- Rename `opd()` → `skpd()`, return `belongsTo(Skpd::class)`
- Rename `scopeByOpd()` → `scopeBySkpd()`

### 4.4 `RiwayatPmk` Model (edit)

- `$fillable`: replace `'opd_id'` with `'skpd_id'`
- Rename `opd()` → `skpd()`, return `belongsTo(Skpd::class)`

---

## 5. CRUD API

### 5.1 Endpoints

| Method | Path | Action | Auth |
|--------|------|--------|------|
| GET | `/api/v1/ref/skpd` | `index()` | `auth:sanctum` |
| GET | `/api/v1/ref/skpd/{id}` | `show()` | `auth:sanctum` |
| POST | `/api/v1/ref/skpd` | `store()` | `auth:sanctum` |
| PUT | `/api/v1/ref/skpd/{id}` | `update()` | `auth:sanctum` |
| DELETE | `/api/v1/ref/skpd/{id}` | `destroy()` | `auth:sanctum` |

**Query parameters for `index()`:**
- `?active=true|false` — filter by `is_active`
- `?search=dinas` — search by `nama` or `singkatan`
- `?per_page=20` — pagination (default 20, max 100)

### 5.2 Response Shape

All endpoints return `SkpdResource` with:
```json
{
  "id": 1,
  "urutan": 1,
  "nama": "Dinas Pendidikan",
  "singkatan": "Disdik",
  "logo": "skpd-logos/disdik.png",
  "logo_url": "https://api.example.com/storage/skpd-logos/disdik.png",
  "id_jabatan_kepala": 5,
  "is_active": true,
  "created_at": "2026-06-04T10:00:00Z",
  "updated_at": "2026-06-04T10:00:00Z"
}
```

`index()` returns paginated list using the project's standard pagination meta (see existing API contract spec).

### 5.3 Controller

File: `app/Http/Controllers/Api/V1/Ref/SkpdController.php` (new, replaces `OpdController`)

Thin controller — delegates to `SkpdService`. Per project convention (CLAUDE.md: "controllers are thin, 20-30 lines").

### 5.4 Form Request

File: `app/Http/Requests/Skpd/SkpdRequest.php` (new)

Rules:
- `urutan`: `nullable`, `integer`, `min:0`
- `nama`: `required`, `string`, `max:255`, `unique:skpd,nama,{id}` (ignore on update)
- `singkatan`: `required`, `string`, `max:50`, `unique:skpd,singkatan,{id}`
- `logo`: `nullable`, `string`, `max:255`
- `id_jabatan_kepala`: `nullable`, `integer`
- `is_active`: `nullable`, `boolean`

### 5.5 Resource

File: `app/Http/Resources/SkpdResource.php` (new)

Shapes the response, computes `logo_url` via `Storage::url($this->logo)` when not null.

---

## 6. Service Layer

### 6.1 `SkpdService`

File: `app/Services/Skpd/SkpdService.php` (new)

| Method | Signature | Description |
|--------|-----------|-------------|
| `paginate` | `paginate(array $filters): LengthAwarePaginator` | applies search, active filter, ordering by urutan, nama |
| `find` | `find(int $id): ?Skpd` | |
| `create` | `create(array $data): Skpd` | `DB::transaction` |
| `update` | `update(Skpd $skpd, array $data): Skpd` | `DB::transaction` |
| `delete` | `delete(Skpd $skpd): bool` | `DB::transaction`; returns false if has users/kgb/pmk (refuses to delete referenced SKPD) |

---

## 7. Routes

Update `routes/api.php`:

- Remove `Route::get('/ref/opd', [OpdController::class, 'index']);`
- Add RESTful resource route:
  ```php
  Route::apiResource('ref/skpd', SkpdController::class);
  ```

---

## 8. Seeders & Factories

### 8.1 `SkpdFactory`

File: `database/factories/SkpdFactory.php` (new)

- Realistic Indonesian gov OPD names (Dinas Pendidikan, BPKAD, Dinkes, etc.)
- States: `inactive()`, `withLogo()`

### 8.2 `SkpdSeeder`

File: `database/seeders/SkpdSeeder.php` (new)

- 5-10 representative SKPD with realistic `urutan`, `singkatan`, `is_active`
- Examples:
  - `Dinas Pendidikan` / `Disdik` / urutan 1
  - `Badan Pengelolaan Keuangan dan Aset Daerah` / `BPKAD` / urutan 2
  - `Dinas Kesehatan` / `Dinkes` / urutan 3
  - `Dinas Kependudukan dan Pencatatan Sipil` / `Disdukcapil` / urutan 4
  - `Sekretariat Daerah` / `Setda` / urutan 5

---

## 9. Tests

### 9.1 New: `tests/Feature/SkpdCrudTest.php`

- `index()` returns paginated list ordered by urutan
- `index()` filters by `?active=true` and `?search=`
- `index()` requires auth (401 without token)
- `show()` returns 200 for valid id, 404 for missing
- `store()` validates required fields (422 on missing nama/singkatan)
- `store()` rejects duplicate nama (422)
- `store()` rejects duplicate singkatan (422)
- `store()` creates record and returns 201
- `update()` modifies fields, ignores self on unique check
- `destroy()` removes record, returns 204
- `destroy()` returns 409 Conflict if SKPD has related users/kgb/pmk (controller maps service `false` return to 409)
- All endpoints require auth (401 without token)

### 9.2 Update: `tests/Feature/AuthTest.php`

- Replace `Opd::create([...])` with `Skpd::create([...])` in setUp
- Update variable name `$opd` → `$skpd` and `opd_id` → `skpd_id` in payload assertions

### 9.3 Other Tests

- Grep for any other test using `Opd` or `opd_id` and update similarly.

---

## 10. Service-Layer Touchpoints (callers of old API)

Update the following files to use the new `Skpd` model and `skpd_id` FK:

| File | Change |
|------|--------|
| `app/Http/Controllers/Api/V1/SimAsnController.php` | `opd_id` → `skpd_id` in allowed filters |
| `app/Http/Controllers/Api/V1/SimAsn/PegawaiController.php` | `opd_id` → `skpd_id` in query param |
| `app/Http/Controllers/Api/V1/DashboardController.php` | `$kgb->opd?->nama` → `$kgb->skpd?->nama`, key `opd_nama` → `skpd_nama` |
| `app/Http/Controllers/Api/V1/KgbController.php` | `opd_id` → `skpd_id` in allowed filters |
| `app/Http/Controllers/Api/V1/PmkController.php` | `opd_id` → `skpd_id` in response |
| `app/Services/Kgb/KgbService.php` | `byOpd` → `bySkpd`, `opd_id` → `skpd_id` |
| `app/Services/Pmk/PmkService.php` | `opd_id` → `skpd_id` in payload |
| `app/Services/Auth/AuthService.php` | `opd_id` → `skpd_id` in user create + response |
| `app/Http/Requests/Auth/RegisterFromSimAsnRequest.php` | `exists:opds,id` → `exists:skpd,id` |

---

## 11. Frontend Touchpoints (Nuxt)

Update `client/app/pages/auth/register/index.vue` and any related type files:
- `Opd` interface → `Skpd` interface
- Field `opd_id` → `skpd_id` in register payload
- Update type definitions in `client/app/types/` if present

**Out of scope:** The frontend does not currently consume the OPD CRUD endpoints (only registration form). No additional frontend changes are required beyond the type/payload updates above.

---

## 12. Out of Scope

- **Authorization for write endpoints** — current decision is to rely on `auth:sanctum` only. Role-based gating (e.g., admin-only for write) is a separate concern and can be added later.
- **`id_jabatan_kepala` FK constraint** — stored as plain integer per user decision; no `ref_jabatan` table creation.
- **Logo upload endpoint** — `logo` is a string path only; file upload handling is a separate feature.
- **Frontend admin UI for SKPD CRUD** — not requested; backend-only changes.

---

## 13. Verification

After implementation:

1. `php artisan migrate:fresh --seed` — fresh DB, all seeds run, no errors.
2. `php artisan test --compact --filter=SkpdCrudTest` — all new tests pass.
3. `php artisan test --compact` — full test suite passes (no regressions in AuthTest, KgbWorkflowTest, SimAsnServiceTest).
4. `vendor/bin/pint --dirty --format agent` — formatting clean.
5. `php artisan route:list --path=ref/skpd` — all 5 routes present.
6. `grep -rn "opd_id\|Opd::" app/ tests/` returns no matches except for `id_jabatan_kepala` context.
