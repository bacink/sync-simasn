# KGB Schema Refactor Summary

## Overview

This refactoring eliminates denormalized data, replaces ENUMs with reference tables, and introduces polymorphic relationships for better scalability and auditability.

---

## Problem → Solution Mapping

| # | Problem | Solution | Migration File |
|---|---------|----------|----------------|
| 1 | Missing FK for golongan + peraturan | Added `golongan_id`, `peraturan_id` FKs | `100005_refactor_riwayat_kgb_fks.php` |
| 2 | Denormalized pegawai data (nip, nama, golongan) | Move to `kgb_snapshots` as source of truth | `100005_refactor_riwayat_kgb_fks.php` |
| 3 | ENUM usage (status, jenis_kgb) | Replace with `ref_status_kgb`, `ref_jenis_kgb` tables | `100001_create_ref_status_kgbs_table.php`, `100002_create_ref_jenis_kgbs_table.php` |
| 4 | Weak approval system | Add `step_order`, `is_required`, unique constraint | `100006_refactor_kgb_approvals.php` |
| 5 | String formula field | Add structured `formula_json` | `100007_refactor_kgb_calculations.php` |
| 6 | Single-file limitation per record | Polymorphic `fileables` table | `100003_create_fileables_table.php`, `100004_drop_file_fks_from_transaction_tables.php` |
| 7 | Non-polymorphic audit logs | Add `auditable_type`, `auditable_id` | `100008_polymorphic_audit_logs.php` |
| 8 | No business constraints | CHECK constraints on masa_kerja, gaji progression | `100009_add_business_constraints.php` |

---

## Relationship Map

```
┌──────────────────────────────────────────────────────────────────────┐
│                            TRANSACTIONAL                              │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│   riwayat_kgb                                                         │
│   ├── belongsTo → ref_golongan (FK, NOT NULL after migration)        │
│   ├── belongsTo → ref_peraturan (FK, nullable during transition)     │
│   ├── hasOne    → kgb_snapshot (immutable employee data)             │
│   ├── hasOne    → kgb_calculation (salary breakdown)                  │
│   ├── hasMany   → kgb_approvals (workflow history)                    │
│   └── morphMany → fileables (multiple files per record)              │
│                                                                        │
│   kgb_approvals                                                        │
│   ├── belongsTo → riwayat_kgb                                         │
│   └── belongsTo → user                                                │
│                                                                        │
│   fileables (polymorphic pivot)                                        │
│   ├── belongsTo → file                                                │
│   └── morphTo   → fileable (riwayat_kgb, riwayat_pmk, etc.)          │
│                                                                        │
│   audit_logs                                                           │
│   ├── belongsTo → user                                                │
│   └── morphTo   → auditable (riwayat_kgb, riwayat_pmk, etc.)         │
│                                                                        │
├──────────────────────────────────────────────────────────────────────┤
│                             REFERENCE                                  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│   ref_golongan                                                         │
│   └── hasMany → riwayat_kgb (as target of FK)                        │
│                                                                        │
│   ref_peraturan                                                        │
│   ├── hasMany → riwayat_kgb                                           │
│   └── hasMany → ref_gaji_asn                                          │
│                                                                        │
│   ref_gaji_asn                                                         │
│   ├── belongsTo → ref_peraturan                                       │
│   └── hasMany   → kgb_calculations                                    │
│                                                                        │
│   ref_status_kgb                                                       │
│   └── lookup table for workflow states                                │
│                                                                        │
│   ref_jenis_kgb                                                        │
│   └── lookup table for KGB types (reguler, penyesuaian)               │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

### Relation Types Summary

| Type | From | To | Example |
|------|------|-----|---------|
| **belongsTo** | riwayat_kgb | ref_golongan | A KGB record has one rank |
| **hasOne** | riwayat_kgb | kgb_snapshot | One snapshot per KGB |
| **hasMany** | riwayat_kgb | kgb_approvals | Multiple approvals in workflow |
| **morphMany** | riwayat_kgb | fileables | Any number of attached files |
| **morphTo** | fileables | fileable | A file attachment points back to owner |

---

## Before vs After Comparison

### Normalization Gains

| Aspect | Before | After |
|--------|--------|-------|
| Employee name storage | In `riwayat_kgb.nama` (duplicate) | Only in `kgb_snapshots.jabatan_nama` |
| NIP storage | In `riwayat_kgb.nip` (duplicate) | Only in `kgb_snapshots.data_json->nip` |
| Golongan value | String `'III/a'` in transaction table | FK `golongan_id` → `ref_golongan.id` |
| Status values | Hardcoded ENUM | Lookup table `ref_status_kgb.kode` |
| SK file | Single FK column `file_sk_id` | Polymorphic `fileables` (many files allowed) |
| Audit target | `table_name` + `record_id` string | Polymorphic `auditable_type` + `auditable_id` |

### Integrity Improvements

| Constraint | Before | After |
|------------|--------|-------|
| FK to golangan | None | `FOREIGN KEY (golongan_id) REFERENCES ref_golongan(id)` |
| Unique workflow step | None | `UNIQUE (riwayat_kgb_id, user_id, step_order)` |
| Masa kerja range | Application only | `CHECK (masa_kerja_tahun BETWEEN 0 AND 32)` |
| Salary progression | None | `CHECK (gaji_baru >= gaji_lama)` |
| Duplicate TMT per employee | None | `UNIQUE (pegawai_id, tmt_kgb)` |

### Performance Indexes Added

- `idx_riwayat_kgb_golongan_id`
- `idx_riwayat_kgb_peraturan_id`
- `idx_fileables_fileable` (composite: type, id)
- `idx_audit_logs_auditable` (composite: type, id)

---

## Eloquent Model Relationships

### RiwayatKgb
```php
public function golongan(): BelongsTo { }
public function peraturan(): BelongsTo { }
public function snapshot(): HasOne { }
public function calculation(): HasOne { }
public function approvals(): HasMany { }
public function files(): MorphMany { }
```

### KgbApproval
```php
public function riwayatKgb(): BelongsTo { }
public function user(): BelongsTo { }
```

### KgbSnapshot
```php
public function riwayatKgb(): BelongsTo { }
```

### KgbCalculation
```php
public function riwayatKgb(): BelongsTo { }
public function gajiRef(): BelongsTo { }
```

### File
```php
public function fileables(): MorphMany { }
```

### Fileable
```php
public function file(): BelongsTo { }
public function fileable(): MorphTo { }
```

### AuditLog
```php
public function user(): BelongsTo { }
public function auditable(): MorphTo { }
```

---

## Breaking Changes & Migration Path

### What Changed in Code

| Location | Old API | New API |
|----------|---------|---------|
| `RiwayatKgb` model | `$kgb->nip` | `$kgb->snapshot->getRawData('nip')` |
| `RiwayatKgb` model | `$kgb->nama` | `$kgb->snapshot->jabatan_nama` |
| `RiwayatKgb` model | `$kgb->golongan` | `$kgb->golongan->pangkat` or `$kgb->golongan_id` |
| File upload | `$kgb->fileSk()->save($file)` | `$kgb->files()->create(['file_id' => $id, 'kategori' => 'sk'])` |
| Create KGB | `RiwayatKgb::create([...])` with all fields | Must also create linked `golongan_id`, `peraturan_id` records |

### Service Updates Required

1. **KgbService::generateDraft()** - Remove direct setting of `nip`, `nama`, `golongan`; populate via FK + snapshot
2. **KgbSnapshotService::store()** - Already correct; no changes needed
3. **Controllers** - Update any code that accesses deprecated columns

### Migration Execution Order

```bash
# 1. Create new tables (ref_status, ref_jenis, fileables)
php artisan migrate --path=database/migrations/2026_04_23_100001_*
php artisan migrate --path=database/migrations/2026_04_23_100002_*
php artisan migrate --path=database/migrations/2026_04_23_100003_*

# 2. Drop old foreign keys
php artisan migrate --path=database/migrations/2026_04_23_100004_*

# 3. Alter riwayat_kgb structure
php artisan migrate --path=database/migrations/2026_04_23_100005_*

# 4. Refactor other transactional tables
php artisan migrate --path=database/migrations/2026_04_23_100006_*
php artisan migrate --path=database/migrations/2026_04_23_100007_*
php artisan migrate --path=database/migrations/2026_04_23_100008_*

# 5. Add business constraints
php artisan migrate --path=database/migrations/2026_04_23_100009_*
```

---

## Future Enhancements (Not Implemented Yet)

1. **Migrate existing data**: Populate `ref_status_kgb` and `ref_jenis_kgb` with current ENUM values
2. **Update services**: Rewrite `KgbService` to use FKs instead of strings
3. **Delete old columns**: After data migration, drop `nip`, `nama`, `golongan` from `riwayat_kgb` (not done yet to preserve backwards compatibility)
4. **API versioning**: Consider bumping API version if external consumers exist
