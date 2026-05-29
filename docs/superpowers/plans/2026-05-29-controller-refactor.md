# Controller Refactor Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement task-by-task.

**Goal:** Remove dead code, consolidate duplicate services, and establish consistent patterns across the Laravel backend.

**Architecture:** Identify orphaned controllers, consolidate duplicate services, fix DI patterns, and ensure the service layer is the single source of business logic.

**Tech Stack:** Laravel 13, Sanctum, PHP 8.4

---

## File Change Map

| File | Action |
|---|---|
| `api/app/Http/Controllers/KgbController.php` | DELETE (duplicate, root-level old version) |
| `api/app/Http/Controllers/Kgb/KgbController.php` | DELETE (duplicate, namespaced old version) |
| `api/app/Http/Controllers/PmkController.php` | DELETE (duplicate, root-level old version) |
| `api/app/Http/Controllers/Pmk/PmkController.php` | DELETE (duplicate, namespaced old version) |
| `api/app/Http/Controllers/SimAsnController.php` | DELETE (duplicate, root-level old version) |
| `api/app/Services/AuditService.php` | DELETE (duplicate, simple version unused) |
| `api/app/Services/SimAsn/SimAsnDownloadService.php` | DELETE (duplicate of ArchiveDownloaderService) |
| `api/app/Services/Pmk/PmkResource.php` | MOVE to `api/app/Http/Resources/PmkResource.php` |
| `api/app/Services/Kgb/KgbService.php` | MODIFY: fix FQCN in constructor to imported class |
| `api/app/Services/Pmk/PmkService.php` | MODIFY: fix FQCN in constructor to imported class |

---

## Task 1: Delete Orphaned Controllers

**Files:**
- DELETE: `api/app/Http/Controllers/KgbController.php`
- DELETE: `api/app/Http/Controllers/Kgb/KgbController.php`
- DELETE: `api/app/Http/Controllers/PmkController.php`
- DELETE: `api/app/Http/Controllers/Pmk/PmkController.php`
- DELETE: `api/app/Http/Controllers/SimAsnController.php`
- Verify: `api/app/Http/Controllers/Api/V1/KgbController.php` is still routed
- Verify: `api/app/Http/Controllers/Api/V1/PmkController.php` is still routed
- Verify: `api/app/Http/Controllers/Api/V1/SimAsnController.php` is still routed (check if actually used — grep api.php routes)

- [ ] **Step 1: Delete orphaned files**

```bash
rm api/app/Http/Controllers/KgbController.php
rm -r api/app/Http/Controllers/Kgb/
rm api/app/Http/Controllers/PmkController.php
rm -r api/app/Http/Controllers/Pmk/
rm api/app/Http/Controllers/SimAsnController.php
```

- [ ] **Step 2: Verify routes still resolve**

Run: `php artisan route:list --compact | grep -E "(kgb|pmk|sim-asn)"`
Expected: All KGB, PMK, SIM-ASN routes still listed pointing to `Api\V1\*Controller`

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "refactor: remove orphaned duplicate controllers"
```

---

## Task 2: Delete Duplicate & Unused Services

**Files:**
- DELETE: `api/app/Services/AuditService.php` (simple duplicate, unused)
- DELETE: `api/app/Services/SimAsn/SimAsnDownloadService.php` (duplicate of ArchiveDownloaderService)

- [ ] **Step 1: Verify unused before deleting**

Run: `grep -r "use App\\\\Services\\\\AuditService" api/app/ --include="*.php"`
Expected: No results (verify it really is unused)
Run: `grep -r "SimAsnDownloadService" api/app/ --include="*.php"`
Expected: No results (verify it really is unused)

- [ ] **Step 2: Delete files**

```bash
rm api/app/Services/AuditService.php
rm api/app/Services/SimAsn/SimAsnDownloadService.php
```

- [ ] **Step 3: Run tests**

Run: `cd api && php artisan test --compact`
Expected: All tests pass (no regressions from deleting unused files)

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: remove duplicate/unused service files"
```

---

## Task 3: Move PmkResource to Http/Resources

**Files:**
- CREATE: `api/app/Http/Resources/PmkResource.php`
- DELETE: `api/app/Services/Pmk/PmkResource.php`
- Modify: `api/app/Http/Controllers/Api/V1/PmkController.php` (update import path)

- [ ] **Step 1: Read PmkResource**

Read `api/app/Services/Pmk/PmkResource.php` to get its content.

- [ ] **Step 2: Create Http/Resources directory and move file**

Create directory `api/app/Http/Resources/` if not exists. Create the file with namespace updated:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmkResource extends JsonResource
{
    // (copy full content from the old file, update namespace)
    public function toArray(Request $request): array
    {
        // ... existing content
    }
}
```

- [ ] **Step 3: Update PmkController import**

Read `api/app/Http/Controllers/Api/V1/PmkController.php`. Update import:
```php
// FROM:
use App\Services\Pmk\PmkResource;
// TO:
use App\Http\Resources\PmkResource;
```

- [ ] **Step 4: Delete old file**

```bash
rm api/app/Services/Pmk/PmkResource.php
```

- [ ] **Step 5: Verify and test**

Run: `cd api && php artisan test --compact --filter=PmkTest`
Expected: All PmkTest tests pass

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor: move PmkResource to Http/Resources namespace"
```

---

## Task 4: Fix FQCN DI in KgbService and PmkService

**Files:**
- Modify: `api/app/Services/Kgb/KgbService.php` (constructor lines)
- Modify: `api/app/Services/Pmk/PmkService.php` (constructor lines)

- [ ] **Step 1: Fix KgbService constructor**

Read `api/app/Services/Kgb/KgbService.php`. The constructor currently has:
```php
public function __construct(
    // ...
    private readonly \App\Services\Audit\AuditService $auditService,
) {}
```

Change to use imported class. Add import at top of file:
```php
use App\Services\Audit\AuditService;
```

Then update constructor parameter type hint to use imported name (already imported if it was already there — check first).

- [ ] **Step 2: Fix PmkService constructor**

Read `api/app/Services/Pmk/PmkService.php`. Same pattern — fix FQCN to use imported `SimAsnService` and `AuditService`.

- [ ] **Step 3: Run tests**

Run: `cd api && php artisan test --compact`
Expected: All tests pass

- [ ] **Step 4: Commit**

```bash
git add api/app/Services/Kgb/KgbService.php api/app/Services/Pmk/PmkService.php
git commit -m "refactor: fix FQCN constructor dependencies to use imported classes"
```

---

## Task 5: Final Verification

- [ ] **Step 1: Run full test suite**

Run: `cd api && php artisan test`
Expected: All tests pass.

- [ ] **Step 2: Verify no orphaned files remain**

Run: `find api/app/Http/Controllers -name "*Controller.php" | sort`
Verify only these remain:
- `ApiController.php`
- `AuthController.php`
- `DashboardController.php`
- `KgbController.php`
- `PmkController.php`
- `GajiController.php`
- `OpdController.php`
- `SimAsnController.php`
- `SimAsn/PegawaiController.php`
- `SimAsn/ArchiveSyncController.php`
- `SimAsnCallbackController.php`
- `Controller.php`

All root-level `Kgb/`, `Pmk/` subdirectories should be gone.

Run: `find api/app/Services -name "*.php" | sort`
Verify only unique services remain.

- [ ] **Step 3: Run Pint**

Run: `cd api && vendor/bin/pint --dirty --format agent`