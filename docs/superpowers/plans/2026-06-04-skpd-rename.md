# SKPD Rename & Schema Extension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the `opds` table to `skpd`, extend its schema, rename the `Opd` model to `Skpd`, rename all FK columns (`opd_id` → `skpd_id`), and add a full CRUD API for `skpd`.

**Architecture:** Two-migration strategy — edit the original migration (for fresh installs) plus a new idempotent migration (for existing dev DBs). Thin controller delegates to a `SkpdService`. Form request validates; Eloquent resource shapes responses. Per project convention, controllers stay 20-30 lines and use `App\Helpers\ApiResponse`.

**Tech Stack:** Laravel 13, PHP 8.4, Pest 4, MySQL.

---

## File Structure

### New files
- `app/Models/Skpd.php` — replaces `Opd.php`
- `app/Http/Controllers/Api/V1/Ref/SkpdController.php` — replaces `OpdController.php`
- `app/Http/Requests/Skpd/SkpdRequest.php` — validation for store + update
- `app/Http/Resources/SkpdResource.php` — response shape
- `app/Services/Skpd/SkpdService.php` — business logic
- `database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php` — idempotent rename
- `database/factories/SkpdFactory.php` — model factory
- `database/seeders/SkpdSeeder.php` — sample data
- `tests/Feature/SkpdCrudTest.php` — full CRUD test coverage

### Modified files
- `database/migrations/2026_05_18_000100_add_opd_to_users_and_create_opds_table.php` — updated to create `skpd`
- `app/Models/User.php` — `opd_id` → `skpd_id`, `opd()` → `skpd()`
- `app/Models/RiwayatKgb.php` — `opd_id` → `skpd_id`, `opd()` → `skpd()`, `scopeByOpd` → `scopeBySkpd`
- `app/Models/RiwayatPmk.php` — `opd_id` → `skpd_id`, `opd()` → `skpd()`
- `app/Http/Controllers/Api/V1/KgbController.php` — `opd_id` → `skpd_id` in filters
- `app/Http/Controllers/Api/V1/PmkController.php` — `opd_id` → `skpd_id` in response
- `app/Http/Controllers/Api/V1/DashboardController.php` — `opd?->nama` → `skpd?->nama`, key rename
- `app/Http/Controllers/Api/V1/SimAsnController.php` — `opd_id` → `skpd_id` in allowed filters
- `app/Http/Controllers/Api/V1/SimAsn/PegawaiController.php` — `opd_id` → `skpd_id` in query param
- `app/Services/Kgb/KgbService.php` — `byOpd` → `bySkpd`, `opd_id` → `skpd_id`
- `app/Services/Pmk/PmkService.php` — `opd_id` → `skpd_id` in payload
- `app/Services/Auth/AuthService.php` — `opd_id` → `skpd_id` in user create + response
- `app/Http/Requests/Auth/RegisterFromSimAsnRequest.php` — `exists:opds,id` → `exists:skpd,id`
- `routes/api.php` — remove old `/ref/opd` route, add `apiResource('ref/skpd', ...)`
- `tests/Feature/AuthTest.php` — use `Skpd` model and `skpd_id` in payloads

### Deleted files
- `app/Models/Opd.php`
- `app/Http/Controllers/Api/V1/Ref/OpdController.php`

---

## Task 1: Edit Original Migration to Create `skpd`

**Files:**
- Modify: `database/migrations/2026_05_18_000100_add_opd_to_users_and_create_opds_table.php`

- [ ] **Step 1: Replace the migration file content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create skpd table
        Schema::create('skpd', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('urutan')->default(0);
            $table->string('nama')->unique();
            $table->string('singkatan', 50)->unique();
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('id_jabatan_kepala')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add skpd_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('skpd_id')->nullable()->constrained('skpd')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['skpd_id']);
            $table->dropColumn('skpd_id');
        });
        Schema::dropIfExists('skpd');
    }
};
```

- [ ] **Step 2: Verify file content**

Run: `php -l database/migrations/2026_05_18_000100_add_opd_to_users_and_create_opds_table.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_18_000100_add_opd_to_users_and_create_opds_table.php
git commit -m "feat(migration): create skpd table in original migration"
```

---

## Task 2: Add Idempotent Rename Migration

**Files:**
- Create: `database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php`

- [ ] **Step 1: Generate migration file**

Run: `php artisan make:migration rename_opd_to_skpd_references --no-interaction`
Expected: file `database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php` created

- [ ] **Step 2: Replace migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename opds table to skpd if old table still exists
        if (Schema::hasTable('opds') && ! Schema::hasTable('skpd')) {
            Schema::rename('opds', 'skpd');
        }

        // Add new columns to skpd if missing
        if (Schema::hasTable('skpd')) {
            Schema::table('skpd', function (Blueprint $table) {
                if (! Schema::hasColumn('skpd', 'urutan')) {
                    $table->unsignedInteger('urutan')->default(0)->after('id');
                }
                if (! Schema::hasColumn('skpd', 'singkatan')) {
                    $table->string('singkatan', 50)->nullable()->after('nama');
                }
                if (! Schema::hasColumn('skpd', 'logo')) {
                    $table->string('logo')->nullable()->after('singkatan');
                }
                if (! Schema::hasColumn('skpd', 'id_jabatan_kepala')) {
                    $table->unsignedBigInteger('id_jabatan_kepala')->nullable()->after('logo');
                }
                if (! Schema::hasColumn('skpd', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('id_jabatan_kepala');
                }
            });

            // Drop old kode column if present
            if (Schema::hasColumn('skpd', 'kode')) {
                Schema::table('skpd', function (Blueprint $table) {
                    $table->dropColumn('kode');
                });
            }

            // Add unique constraints (skip if already present)
            $existingIndexes = collect(Schema::getIndexListing('skpd'));
            if (! $existingIndexes->contains('skpd_nama_unique')) {
                Schema::table('skpd', function (Blueprint $table) {
                    $table->unique('nama');
                });
            }
            if (! $existingIndexes->contains('skpd_singkatan_unique')) {
                Schema::table('skpd', function (Blueprint $table) {
                    $table->unique('singkatan');
                });
            }
        }

        // Rename opd_id → skpd_id on users, riwayat_kgb, riwayat_pmk
        $this->renameForeignKey('users', 'opd_id', 'skpd_id', 'skpd');
        $this->renameForeignKey('riwayat_kgb', 'opd_id', 'skpd_id', 'skpd');
        $this->renameForeignKey('riwayat_pmk', 'opd_id', 'skpd_id', 'skpd');
    }

    public function down(): void
    {
        // Reverse FK renames
        $this->renameForeignKey('users', 'skpd_id', 'opd_id', 'opds');
        $this->renameForeignKey('riwayat_kgb', 'skpd_id', 'opd_id', 'opds');
        $this->renameForeignKey('riwayat_pmk', 'skpd_id', 'opd_id', 'opds');

        // Reverse skpd → opds rename
        if (Schema::hasTable('skpd') && ! Schema::hasTable('opds')) {
            Schema::rename('skpd', 'opds');
        }
    }

    private function renameForeignKey(string $table, string $fromCol, string $toCol, string $refTable): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, $fromCol) && ! Schema::hasColumn($table, $toCol)) {
            Schema::table($table, function (Blueprint $t) use ($table, $fromCol, $toCol, $refTable) {
                $t->dropForeign([$fromCol]);
            });
            Schema::table($table, function (Blueprint $t) use ($fromCol, $toCol) {
                $t->renameColumn($fromCol, $toCol);
            });
            Schema::table($table, function (Blueprint $t) use ($table, $toCol, $refTable) {
                $t->foreign($toCol)->references('id')->on($refTable)->nullOnDelete();
            });
        }
    }
};
```

- [ ] **Step 3: Verify file**

Run: `php -l database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_06_04_000000_rename_opd_to_skpd_references.php
git commit -m "feat(migration): add idempotent rename opds→skpd migration"
```

---

## Task 3: Create `Skpd` Model and Delete `Opd` Model

**Files:**
- Create: `app/Models/Skpd.php`
- Delete: `app/Models/Opd.php`

- [ ] **Step 1: Create the new model file**

Run: `php artisan make:model Skpd --no-interaction`
Expected: file `app/Models/Skpd.php` created

- [ ] **Step 2: Replace model content**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skpd extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'skpd';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'urutan',
        'nama',
        'singkatan',
        'logo',
        'id_jabatan_kepala',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan' => 'integer',
            'id_jabatan_kepala' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function riwayatKgb(): HasMany
    {
        return $this->hasMany(RiwayatKgb::class);
    }

    public function riwayatPmk(): HasMany
    {
        return $this->hasMany(RiwayatPmk::class);
    }

    /**
     * Scope: only active SKPD records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: search by nama or singkatan.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                    ->orWhere('singkatan', 'like', "%{$term}%");
            });
        }

        return $query;
    }
}
```

- [ ] **Step 3: Delete the old Opd model**

Run: `rm app/Models/Opd.php`
Expected: file removed

- [ ] **Step 4: Verify no remaining references in models dir**

Run: `grep -rn "Opd" app/Models/ || echo "clean"`
Expected: `clean`

- [ ] **Step 5: Commit**

```bash
git add app/Models/Skpd.php
git rm app/Models/Opd.php
git commit -m "feat(model): rename Opd to Skpd with extended schema"
```

---

## Task 4: Update `User` Model

**Files:**
- Modify: `app/Models/User.php:22-29` (`$fillable` array)
- Modify: `app/Models/User.php:55-58` (`opd()` method)

- [ ] **Step 1: Replace `opd_id` with `skpd_id` in `$fillable`**

In `app/Models/User.php`, change line 26 from:
```php
        'opd_id',
```
to:
```php
        'skpd_id',
```

- [ ] **Step 2: Rename `opd()` method to `skpd()`**

In `app/Models/User.php`, replace the method block:

```php
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
```

with:

```php
    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class);
    }
```

- [ ] **Step 3: Verify file**

Run: `php -l app/Models/User.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Models/User.php
git commit -m "refactor(model): rename opd() to skpd() in User model"
```

---

## Task 5: Update `RiwayatKgb` Model

**Files:**
- Modify: `app/Models/RiwayatKgb.php:34` (`$fillable` array)
- Modify: `app/Models/RiwayatKgb.php:70-73` (`opd()` method)
- Modify: `app/Models/RiwayatKgb.php:120-125` (`scopeByOpd` method)

- [ ] **Step 1: Replace `opd_id` with `skpd_id` in `$fillable`**

In `app/Models/RiwayatKgb.php`, change line 34 from:
```php
        'opd_id',
```
to:
```php
        'skpd_id',
```

- [ ] **Step 2: Rename `opd()` to `skpd()`**

In `app/Models/RiwayatKgb.php`, replace:

```php
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
```

with:

```php
    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class);
    }
```

- [ ] **Step 3: Rename `scopeByOpd` to `scopeBySkpd`**

In `app/Models/RiwayatKgb.php`, replace:

```php
    public function scopeByOpd($query, ?int $opdId): void
    {
        if ($opdId) {
            $query->where('opd_id', $opdId);
        }
    }
```

with:

```php
    public function scopeBySkpd($query, ?int $skpdId): void
    {
        if ($skpdId) {
            $query->where('skpd_id', $skpdId);
        }
    }
```

- [ ] **Step 4: Verify file**

Run: `php -l app/Models/RiwayatKgb.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add app/Models/RiwayatKgb.php
git commit -m "refactor(model): rename opd to skpd in RiwayatKgb model"
```

---

## Task 6: Update `RiwayatPmk` Model

**Files:**
- Modify: `app/Models/RiwayatPmk.php:32` (`$fillable`)
- Modify: `app/Models/RiwayatPmk.php:64` (`opd()` method)

- [ ] **Step 1: Replace `opd_id` with `skpd_id` in `$fillable`**

In `app/Models/RiwayatPmk.php`, change line 32 from:
```php
        'opd_id',
```
to:
```php
        'skpd_id',
```

- [ ] **Step 2: Rename `opd()` to `skpd()`**

In `app/Models/RiwayatPmk.php`, replace:

```php
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
```

with:

```php
    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class);
    }
```

- [ ] **Step 3: Verify file**

Run: `php -l app/Models/RiwayatPmk.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Models/RiwayatPmk.php
git commit -m "refactor(model): rename opd to skpd in RiwayatPmk model"
```

---

## Task 7: Create `SkpdResource`

**Files:**
- Create: `app/Http/Resources/SkpdResource.php`

- [ ] **Step 1: Create the resource file**

Run: `php artisan make:resource SkpdResource --no-interaction`
Expected: file `app/Http/Resources/SkpdResource.php` created

- [ ] **Step 2: Replace resource content**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SkpdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'urutan' => $this->urutan,
            'nama' => $this->nama,
            'singkatan' => $this->singkatan,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? Storage::url($this->logo) : null,
            'id_jabatan_kepala' => $this->id_jabatan_kepala,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 3: Verify file**

Run: `php -l app/Http/Resources/SkpdResource.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Resources/SkpdResource.php
git commit -m "feat(resource): add SkpdResource with logo_url"
```

---

## Task 8: Create `SkpdRequest` Form Request

**Files:**
- Create: `app/Http/Requests/Skpd/SkpdRequest.php`

- [ ] **Step 1: Create the request file**

Run: `mkdir -p app/Http/Requests/Skpd && touch app/Http/Requests/Skpd/SkpdRequest.php`
Expected: file created

- [ ] **Step 2: Write the request content**

```php
<?php

namespace App\Http\Requests\Skpd;

use Illuminate\Foundation\Http\FormRequest;

class SkpdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skpdId = $this->route('skpd');

        return [
            'urutan' => ['nullable', 'integer', 'min:0'],
            'nama' => ['required', 'string', 'max:255', 'unique:skpd,nama,'.($skpdId ?? 'NULL').',id'],
            'singkatan' => ['required', 'string', 'max:50', 'unique:skpd,singkatan,'.($skpdId ?? 'NULL').',id'],
            'logo' => ['nullable', 'string', 'max:255'],
            'id_jabatan_kepala' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.unique' => 'Nama SKPD sudah digunakan.',
            'singkatan.unique' => 'Singkatan sudah digunakan.',
        ];
    }
}
```

- [ ] **Step 3: Verify file**

Run: `php -l app/Http/Requests/Skpd/SkpdRequest.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/Skpd/SkpdRequest.php
git commit -m "feat(request): add SkpdRequest for store/update validation"
```

---

## Task 9: Create `SkpdService`

**Files:**
- Create: `app/Services/Skpd/SkpdService.php`

- [ ] **Step 1: Create the service file**

Run: `mkdir -p app/Services/Skpd && touch app/Services/Skpd/SkpdService.php`
Expected: file created

- [ ] **Step 2: Write the service content**

```php
<?php

namespace App\Services\Skpd;

use App\Models\Skpd;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SkpdService
{
    /**
     * Paginate SKPD records with filters.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Skpd::query()
            ->search($filters['search'] ?? null)
            ->when(isset($filters['active']), fn (Builder $q) => $q->where('is_active', $filters['active']))
            ->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc');

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min(100, $perPage));

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Skpd
    {
        return Skpd::find($id);
    }

    /**
     * Create a new SKPD.
     */
    public function create(array $data): Skpd
    {
        return DB::transaction(function () use ($data) {
            return Skpd::create($data);
        });
    }

    /**
     * Update an existing SKPD.
     */
    public function update(Skpd $skpd, array $data): Skpd
    {
        return DB::transaction(function () use ($skpd, $data) {
            $skpd->update($data);

            return $skpd->fresh();
        });
    }

    /**
     * Delete an SKPD. Returns false if referenced by users, KGB, or PMK.
     */
    public function delete(Skpd $skpd): bool
    {
        return DB::transaction(function () use ($skpd) {
            if ($skpd->users()->exists()) {
                return false;
            }
            if ($skpd->riwayatKgb()->exists()) {
                return false;
            }
            if ($skpd->riwayatPmk()->exists()) {
                return false;
            }

            $skpd->delete();

            return true;
        });
    }
}
```

- [ ] **Step 3: Verify file**

Run: `php -l app/Services/Skpd/SkpdService.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Services/Skpd/SkpdService.php
git commit -m "feat(service): add SkpdService with paginate, create, update, delete"
```

---

## Task 10: Create `SkpdController` and Delete `OpdController`

**Files:**
- Create: `app/Http/Controllers/Api/V1/Ref/SkpdController.php`
- Delete: `app/Http/Controllers/Api/V1/Ref/OpdController.php`

- [ ] **Step 1: Create the controller file**

Run: `php artisan make:controller Api/V1/Ref/SkpdController --no-interaction`
Expected: file `app/Http/Controllers/Api/V1/Ref/SkpdController.php` created

- [ ] **Step 2: Replace controller content**

```php
<?php

namespace App\Http\Controllers\Api\V1\Ref;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Skpd\SkpdRequest;
use App\Http\Resources\SkpdResource;
use App\Models\Skpd;
use App\Services\Skpd\SkpdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkpdController extends Controller
{
    public function __construct(private readonly SkpdService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->paginate([
            'search' => $request->string('search')->value() ?: null,
            'active' => $request->has('active') ? $request->boolean('active') : null,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return ApiResponse::paginated($paginator, SkpdResource::collection($paginator->items())->resolve());
    }

    public function store(SkpdRequest $request): JsonResponse
    {
        $skpd = $this->service->create($request->validated());

        return ApiResponse::created((new SkpdResource($skpd))->resolve());
    }

    public function show(int $id): JsonResponse
    {
        $skpd = $this->service->find($id);

        if (! $skpd) {
            return ApiResponse::error('Data SKPD tidak ditemukan', 404);
        }

        return ApiResponse::success((new SkpdResource($skpd))->resolve());
    }

    public function update(SkpdRequest $request, int $id): JsonResponse
    {
        $skpd = $this->service->find($id);

        if (! $skpd) {
            return ApiResponse::error('Data SKPD tidak ditemukan', 404);
        }

        $skpd = $this->service->update($skpd, $request->validated());

        return ApiResponse::success((new SkpdResource($skpd))->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $skpd = $this->service->find($id);

        if (! $skpd) {
            return ApiResponse::error('Data SKPD tidak ditemukan', 404);
        }

        $deleted = $this->service->delete($skpd);

        if (! $deleted) {
            return ApiResponse::error('SKPD tidak dapat dihapus karena masih memiliki data terkait (users/KGB/PMK)', 409);
        }

        return ApiResponse::success(null, ['message' => 'Data SKPD berhasil dihapus']);
    }
}
```

- [ ] **Step 3: Verify `ApiResponse::error` exists**

Run: `grep -n "function error" app/Helpers/ApiResponse.php`
Expected: a line like `public static function error(...` is shown. If not present, add it:

```php
    public static function error(string $message, int $status = 400, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message,
            ],
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ], $meta),
        ], $status);
    }
```

Add this method to `app/Helpers/ApiResponse.php` if missing.

- [ ] **Step 4: Delete the old controller**

Run: `rm app/Http/Controllers/Api/V1/Ref/OpdController.php`
Expected: file removed

- [ ] **Step 5: Verify controller file**

Run: `php -l app/Http/Controllers/Api/V1/Ref/SkpdController.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/V1/Ref/SkpdController.php
git rm app/Http/Controllers/Api/V1/Ref/OpdController.php
git commit -m "feat(controller): add SkpdController with full CRUD"
```

---

## Task 11: Update Routes

**Files:**
- Modify: `routes/api.php:8` (use statement)
- Modify: `routes/api.php:62` (route definition)

- [ ] **Step 1: Replace the use statement**

In `routes/api.php`, change line 8 from:
```php
use App\Http\Controllers\Api\V1\Ref\OpdController;
```
to:
```php
use App\Http\Controllers\Api\V1\Ref\SkpdController;
```

- [ ] **Step 2: Replace the route definition**

In `routes/api.php`, change line 62 from:
```php
        Route::get('/ref/opd', [OpdController::class, 'index']);
```
to:
```php
        Route::apiResource('ref/skpd', SkpdController::class);
```

- [ ] **Step 3: Verify routes**

Run: `php artisan route:list --path=ref/skpd`
Expected: 5 routes listed (`index`, `store`, `show`, `update`, `destroy`)

- [ ] **Step 4: Commit**

```bash
git add routes/api.php
git commit -m "feat(routes): add ref/skpd apiResource route"
```

---

## Task 12: Create `SkpdFactory`

**Files:**
- Create: `database/factories/SkpdFactory.php`

- [ ] **Step 1: Create the factory file**

Run: `php artisan make:factory SkpdFactory --model=Skpd --no-interaction`
Expected: file `database/factories/SkpdFactory.php` created

- [ ] **Step 2: Replace factory content**

```php
<?php

namespace Database\Factories;

use App\Models\Skpd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skpd>
 */
class SkpdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'Dinas Pendidikan' => 'Disdik',
            'Badan Pengelolaan Keuangan dan Aset Daerah' => 'BPKAD',
            'Dinas Kesehatan' => 'Dinkes',
            'Dinas Kependudukan dan Pencatatan Sipil' => 'Disdukcapil',
            'Sekretariat Daerah' => 'Setda',
            'Dinas Pekerjaan Umum dan Penataan Ruang' => 'DPUPR',
            'Dinas Perhubungan' => 'Dishub',
            'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia' => 'BKPSDM',
        ];

        $nama = fake()->unique()->randomElement(array_keys($names));
        $singkatan = $names[$nama];

        return [
            'urutan' => fake()->numberBetween(0, 100),
            'nama' => $nama,
            'singkatan' => $singkatan,
            'logo' => null,
            'id_jabatan_kepala' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withLogo(): static
    {
        return $this->state(fn () => [
            'logo' => 'skpd-logos/'.fake()->uuid().'.png',
        ]);
    }
}
```

- [ ] **Step 3: Verify file**

Run: `php -l database/factories/SkpdFactory.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add database/factories/SkpdFactory.php
git commit -m "feat(factory): add SkpdFactory with realistic Indonesian gov OPD data"
```

---

## Task 13: Create `SkpdSeeder`

**Files:**
- Create: `database/seeders/SkpdSeeder.php`

- [ ] **Step 1: Create the seeder file**

Run: `php artisan make:seeder SkpdSeeder --no-interaction`
Expected: file `database/seeders/SkpdSeeder.php` created

- [ ] **Step 2: Replace seeder content**

```php
<?php

namespace Database\Seeders;

use App\Models\Skpd;
use Illuminate\Database\Seeder;

class SkpdSeeder extends Seeder
{
    public function run(): void
    {
        $skpds = [
            ['urutan' => 1, 'nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik', 'is_active' => true],
            ['urutan' => 2, 'nama' => 'Badan Pengelolaan Keuangan dan Aset Daerah', 'singkatan' => 'BPKAD', 'is_active' => true],
            ['urutan' => 3, 'nama' => 'Dinas Kesehatan', 'singkatan' => 'Dinkes', 'is_active' => true],
            ['urutan' => 4, 'nama' => 'Dinas Kependudukan dan Pencatatan Sipil', 'singkatan' => 'Disdukcapil', 'is_active' => true],
            ['urutan' => 5, 'nama' => 'Sekretariat Daerah', 'singkatan' => 'Setda', 'is_active' => true],
            ['urutan' => 6, 'nama' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'singkatan' => 'DPUPR', 'is_active' => true],
            ['urutan' => 7, 'nama' => 'Dinas Perhubungan', 'singkatan' => 'Dishub', 'is_active' => true],
            ['urutan' => 8, 'nama' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'singkatan' => 'BKPSDM', 'is_active' => true],
            ['urutan' => 9, 'nama' => 'Dinas Komunikasi dan Informatika', 'singkatan' => 'Diskominfo', 'is_active' => false],
        ];

        foreach ($skpds as $skpd) {
            Skpd::updateOrCreate(
                ['singkatan' => $skpd['singkatan']],
                $skpd
            );
        }
    }
}
```

- [ ] **Step 3: Verify file**

Run: `php -l database/seeders/SkpdSeeder.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add database/seeders/SkpdSeeder.php
git commit -m "feat(seeder): add SkpdSeeder with 9 representative SKPD records"
```

---

## Task 14: Write `SkpdCrudTest` (TDD)

**Files:**
- Create: `tests/Feature/SkpdCrudTest.php`

- [ ] **Step 1: Create the test file**

Run: `php artisan make:test SkpdCrudTest --pest --no-interaction`
Expected: file `tests/Feature/SkpdCrudTest.php` created

- [ ] **Step 2: Replace test content**

```php
<?php

use App\Models\Skpd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'operator', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
});

it('requires authentication to list skpd', function () {
    $response = $this->getJson('/api/v1/ref/skpd');
    $response->assertStatus(401);
});

it('returns paginated list of skpd ordered by urutan', function () {
    Skpd::factory()->create(['urutan' => 2, 'nama' => 'Z-Last', 'singkatan' => 'ZL']);
    Skpd::factory()->create(['urutan' => 1, 'nama' => 'A-First', 'singkatan' => 'AF']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/ref/skpd');

    $response->assertOk()
        ->assertJsonPath('success', true);

    $data = $response->json('data');
    expect($data[0]['nama'])->toBe('A-First');
    expect($data[1]['nama'])->toBe('Z-Last');
});

it('filters skpd by active status', function () {
    Skpd::factory()->create(['nama' => 'Active', 'singkatan' => 'A1', 'is_active' => true]);
    Skpd::factory()->inactive()->create(['nama' => 'Inactive', 'singkatan' => 'I1']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/ref/skpd?active=true');

    $response->assertOk();
    $data = $response->json('data');
    expect(collect($data)->pluck('nama')->toArray())->toContain('Active')
        ->and(collect($data)->pluck('nama')->toArray())->not->toContain('Inactive');
});

it('searches skpd by nama or singkatan', function () {
    Skpd::factory()->create(['nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik']);
    Skpd::factory()->create(['nama' => 'Dinas Kesehatan', 'singkatan' => 'Dinkes']);

    $response = $this->actingAs($this->user)->getJson('/api/v1/ref/skpd?search=pendidikan');

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['nama'])->toBe('Dinas Pendidikan');
});

it('returns 200 for valid skpd id on show', function () {
    $skpd = Skpd::factory()->create();

    $response = $this->actingAs($this->user)->getJson("/api/v1/ref/skpd/{$skpd->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $skpd->id);
});

it('returns 404 for missing skpd id on show', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/ref/skpd/99999');

    $response->assertStatus(404);
});

it('creates a new skpd with valid data', function () {
    $payload = [
        'urutan' => 1,
        'nama' => 'Dinas Pendidikan',
        'singkatan' => 'Disdik',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)->postJson('/api/v1/ref/skpd', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.nama', 'Dinas Pendidikan')
        ->assertJsonPath('data.singkatan', 'Disdik');

    $this->assertDatabaseHas('skpd', ['singkatan' => 'Disdik']);
});

it('rejects skpd creation with missing nama', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/ref/skpd', [
        'singkatan' => 'Disdik',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama']);
});

it('rejects skpd creation with duplicate nama', function () {
    Skpd::factory()->create(['nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik']);

    $response = $this->actingAs($this->user)->postJson('/api/v1/ref/skpd', [
        'nama' => 'Dinas Pendidikan',
        'singkatan' => 'Other',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama']);
});

it('rejects skpd creation with duplicate singkatan', function () {
    Skpd::factory()->create(['nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik']);

    $response = $this->actingAs($this->user)->postJson('/api/v1/ref/skpd', [
        'nama' => 'Other',
        'singkatan' => 'Disdik',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['singkatan']);
});

it('updates an existing skpd', function () {
    $skpd = Skpd::factory()->create(['nama' => 'Old Name', 'singkatan' => 'OLD']);

    $response = $this->actingAs($this->user)->putJson("/api/v1/ref/skpd/{$skpd->id}", [
        'nama' => 'New Name',
        'singkatan' => 'NEW',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.nama', 'New Name');

    $this->assertDatabaseHas('skpd', ['id' => $skpd->id, 'nama' => 'New Name', 'singkatan' => 'NEW']);
});

it('allows update to keep same nama on the same record', function () {
    $skpd = Skpd::factory()->create(['nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik']);

    $response = $this->actingAs($this->user)->putJson("/api/v1/ref/skpd/{$skpd->id}", [
        'nama' => 'Dinas Pendidikan',
        'singkatan' => 'Disdik',
        'urutan' => 5,
    ]);

    $response->assertOk();
});

it('deletes an unreferenced skpd', function () {
    $skpd = Skpd::factory()->create();

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/ref/skpd/{$skpd->id}");

    $response->assertOk()
        ->assertJsonPath('meta.message', 'Data SKPD berhasil dihapus');

    $this->assertDatabaseMissing('skpd', ['id' => $skpd->id]);
});

it('returns 409 when deleting skpd referenced by a user', function () {
    $skpd = Skpd::factory()->create();
    User::factory()->create(['skpd_id' => $skpd->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/v1/ref/skpd/{$skpd->id}");

    $response->assertStatus(409);

    $this->assertDatabaseHas('skpd', ['id' => $skpd->id]);
});
```

- [ ] **Step 3: Run tests to verify they fail (controller not yet wired into full flow)**

Run: `php artisan test --compact --filter=SkpdCrudTest`
Expected: Some tests fail. This is expected at this stage because `app/Http/Requests/Skpd/SkpdRequest.php` route binding parameter is `skpd` (we registered `apiResource('ref/skpd', SkpdController::class)`) — the form-request route resolution for `skpd` works. We proceed.

If any tests fail with route-related errors, check that the route is registered:
Run: `php artisan route:list --path=ref/skpd`

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/SkpdCrudTest.php
git commit -m "test(skpd): add full CRUD feature tests for skpd endpoints"
```

---

## Task 15: Update Service & Controller References

**Files:**
- Modify: `app/Services/Kgb/KgbService.php:35` (filter chain)
- Modify: `app/Services/Kgb/KgbService.php:116-124` (opdId variables)
- Modify: `app/Services/Pmk/PmkService.php:56` (payload)
- Modify: `app/Services/Auth/AuthService.php:102-151` (opd_id in create + response)
- Modify: `app/Http/Requests/Auth/RegisterFromSimAsnRequest.php:21` (exists rule)
- Modify: `app/Http/Controllers/Api/V1/KgbController.php:26` (allowed filters)
- Modify: `app/Http/Controllers/Api/V1/PmkController.php:39,83` (response key)
- Modify: `app/Http/Controllers/Api/V1/DashboardController.php:45,74` (relation + key)
- Modify: `app/Http/Controllers/Api/V1/SimAsnController.php:20` (allowed filter)
- Modify: `app/Http/Controllers/Api/V1/SimAsn/PegawaiController.php:23-24` (query param)

- [ ] **Step 1: Update `KgbService`**

In `app/Services/Kgb/KgbService.php`:

- Change line 35: `->byOpd($filters['opd_id'] ?? null)` → `->bySkpd($filters['skpd_id'] ?? null)`
- Change line 116: `// 7. Determine opd_id` → `// 7. Determine skpd_id`
- Change line 117: `$opdId = $pegawai['opd_id'] ?? null;` → `$skpdId = $pegawai['skpd_id'] ?? null;`
- Change line 124: `'opd_id' => $opdId,` → `'skpd_id' => $skpdId,`

(If `$opdId` is referenced in any further logic in this method, rename consistently. Verify the rest of the method body uses `$skpdId`.)

- [ ] **Step 2: Update `PmkService`**

In `app/Services/Pmk/PmkService.php`:
- Change line 56: `'opd_id' => $pegawai['opd_id'] ?? null,` → `'skpd_id' => $pegawai['skpd_id'] ?? null,`

- [ ] **Step 3: Update `AuthService`**

In `app/Services/Auth/AuthService.php`:
- In the docblock (line 102): `'opd_id?: int|null'` → `'skpd_id?: int|null'`
- Line 113: `'opd_id' => $data['opd_id'] ?? null,` → `'skpd_id' => $data['skpd_id'] ?? null,`
- Line 151: `'opd_id' => $user->opd_id,` → `'skpd_id' => $user->skpd_id,`

- [ ] **Step 4: Update `RegisterFromSimAsnRequest`**

In `app/Http/Requests/Auth/RegisterFromSimAsnRequest.php`:
- Change line 21: `'opd_id' => ['required', 'integer', 'exists:opds,id'],` → `'skpd_id' => ['required', 'integer', 'exists:skpd,id'],`

- [ ] **Step 5: Update `KgbController`**

In `app/Http/Controllers/Api/V1/KgbController.php`:
- Change line 26: `'opd_id'` → `'skpd_id'` (in the allowed filters array)

- [ ] **Step 6: Update `PmkController`**

In `app/Http/Controllers/Api/V1/PmkController.php`:
- Change lines 39 and 83: `'opd_id' => $pmk->opd_id,` → `'skpd_id' => $pmk->skpd_id,`

- [ ] **Step 7: Update `DashboardController`**

In `app/Http/Controllers/Api/V1/DashboardController.php`:
- Lines 45 and 74: `'opd_nama' => $kgb->opd?->nama,` → `'skpd_nama' => $kgb->skpd?->nama,`

- [ ] **Step 8: Update `SimAsnController`**

In `app/Http/Controllers/Api/V1/SimAsnController.php`:
- Line 20: replace `'opd_id'` → `'skpd_id'` in allowed filters

- [ ] **Step 9: Update `PegawaiController`**

In `app/Http/Controllers/Api/V1/SimAsn/PegawaiController.php`:
- Line 23: `if ($request->filled('opd_id'))` → `if ($request->filled('skpd_id'))`
- Line 24: `$params['opd_id'] = $request->integer('opd_id');` → `$params['skpd_id'] = $request->integer('skpd_id');`

- [ ] **Step 10: Verify all files**

Run:
```bash
php -l app/Services/Kgb/KgbService.php
php -l app/Services/Pmk/PmkService.php
php -l app/Services/Auth/AuthService.php
php -l app/Http/Requests/Auth/RegisterFromSimAsnRequest.php
php -l app/Http/Controllers/Api/V1/KgbController.php
php -l app/Http/Controllers/Api/V1/PmkController.php
php -l app/Http/Controllers/Api/V1/DashboardController.php
php -l app/Http/Controllers/Api/V1/SimAsnController.php
php -l app/Http/Controllers/Api/V1/SimAsn/PegawaiController.php
```
Expected: All report `No syntax errors detected`

- [ ] **Step 11: Grep for any remaining `opd_id` / `Opd::` / `->opd` in modified code**

Run:
```bash
grep -rn "opd_id\|Opd::\|->opd" app/ --include="*.php" | grep -v "id_jabatan_kepala" | grep -v "Opd_id" || echo "clean"
```
Expected: `clean`

- [ ] **Step 12: Commit**

```bash
git add app/Services/ app/Http/Controllers/ app/Http/Requests/Auth/
git commit -m "refactor: rename opd to skpd across services, controllers, requests"
```

---

## Task 16: Update `AuthTest`

**Files:**
- Modify: `tests/Feature/AuthTest.php`

- [ ] **Step 1: Replace `Opd` import with `Skpd`**

In `tests/Feature/AuthTest.php`, change line 5 from:
```php
use App\Models\Opd;
```
to:
```php
use App\Models\Skpd;
```

- [ ] **Step 2: Update property type and setUp**

In `tests/Feature/AuthTest.php`:

- Line 15: `protected ?Opd $opd = null;` → `protected ?Skpd $skpd = null;`
- Line 23 comment: `// Create a default OPD for tests that need opd_id` → `// Create a default SKPD for tests that need skpd_id`
- Line 24: `$this->opd = Opd::create(['nama' => 'Dinas Pendidikan', 'kode' => '01']);` → `$this->skpd = Skpd::create(['nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik']);`

- [ ] **Step 3: Update assertion field name and payload**

In `tests/Feature/AuthTest.php`:
- Line 47: `'opd_id',` → `'skpd_id',`
- Line 107: `'opd_id' => $this->opd->id,` → `'skpd_id' => $this->skpd->id,`
- Line 140: `'opd_id' => $this->opd->id,` → `'skpd_id' => $this->skpd->id,`
- Line 158: `'opd_id' => $this->opd->id,` → `'skpd_id' => $this->skpd->id,`

- [ ] **Step 4: Run AuthTest to verify it passes**

Run: `php artisan test --compact --filter=AuthTest`
Expected: All tests pass

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/AuthTest.php
git commit -m "test(auth): update AuthTest to use Skpd model and skpd_id"
```

---

## Task 17: Update Frontend (Nuxt)

**Files:**
- Modify: `client/app/pages/auth/register/index.vue` (or wherever `opd_id` field is used)

- [ ] **Step 1: Find frontend references**

Run:
```bash
cd ../client && grep -rn "opd_id\|Opd\|opd" app/ --include="*.vue" --include="*.ts" 2>/dev/null | head -30
```
Expected: A list of files. Focus on:
- `client/app/pages/auth/register/index.vue`
- `client/app/types/*.ts`
- `client/app/services/*.ts`
- `client/app/stores/*.ts`

- [ ] **Step 2: Replace `opd_id` with `skpd_id` in payload**

In each frontend file containing `opd_id` as a payload field, rename to `skpd_id`. If the file uses a TypeScript type with `opd_id: number`, update the type accordingly.

Example: in `client/app/pages/auth/register/index.vue`, change:
```ts
const payload = { opd_id: selectedOpdId.value, ... }
```
to:
```ts
const payload = { skpd_id: selectedSkpdId.value, ... }
```

- [ ] **Step 3: Verify frontend type-checks**

Run: `cd ../client && npm run typecheck` (or whatever the project's typecheck command is — check `package.json`)
Expected: no TypeScript errors related to opd/skpd

- [ ] **Step 4: Commit**

```bash
git add ../client/app/
git commit -m "refactor(frontend): rename opd_id to skpd_id in register flow"
```

---

## Task 18: Run Full Test Suite and Format

- [ ] **Step 1: Run fresh migration with seeding**

Run:
```bash
php artisan migrate:fresh --seed
```
Expected: All migrations run, seeder creates 9 SKPD records, no errors.

- [ ] **Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass (AuthTest, KgbWorkflowTest, SimAsnServiceTest, SkpdCrudTest, etc.)

- [ ] **Step 3: Format PHP code**

Run: `vendor/bin/pint --dirty --format agent`
Expected: All files formatted cleanly. If pint reports fixes, run again to confirm clean.

- [ ] **Step 4: Verify final state of `opd` references**

Run:
```bash
grep -rn "opd_id\|Opd::\|->opd" app/ tests/ database/ --include="*.php" 2>/dev/null | grep -v "id_jabatan_kepala" || echo "clean"
```
Expected: `clean`

- [ ] **Step 5: Verify routes**

Run: `php artisan route:list --path=ref/skpd`
Expected: 5 routes listed

- [ ] **Step 6: Commit any pint fixes**

```bash
git add -A
git commit -m "style: pint format" || echo "no changes to commit"
```

---

## Self-Review

**Spec coverage:**
- ✅ Section 2.1 (new `skpd` table) → Task 1, Task 2
- ✅ Section 2.2 (FK renames) → Task 1, Task 2, Task 4, Task 5, Task 6, Task 15
- ✅ Section 3.1 (edit original migration) → Task 1
- ✅ Section 3.2 (idempotent rename migration) → Task 2
- ✅ Section 4.1 (Skpd model) → Task 3
- ✅ Section 4.2 (User model) → Task 4
- ✅ Section 4.3 (RiwayatKgb model) → Task 5
- ✅ Section 4.4 (RiwayatPmk model) → Task 6
- ✅ Section 5.1 (endpoints) → Task 11
- ✅ Section 5.2 (response shape) → Task 7
- ✅ Section 5.3 (controller) → Task 10
- ✅ Section 5.4 (form request) → Task 8
- ✅ Section 5.5 (resource) → Task 7
- ✅ Section 6.1 (service) → Task 9
- ✅ Section 7 (routes) → Task 11
- ✅ Section 8.1 (factory) → Task 12
- ✅ Section 8.2 (seeder) → Task 13
- ✅ Section 9.1 (new tests) → Task 14
- ✅ Section 9.2 (update AuthTest) → Task 16
- ✅ Section 10 (service/controller touchpoints) → Task 15
- ✅ Section 11 (frontend) → Task 17
- ✅ Section 13 (verification) → Task 18

**Placeholder scan:** No "TBD"/"TODO" in any step. All code shown in full.

**Type consistency:** `skpd_id` used consistently; `Skpd` model class used everywhere; `SkpdService` methods match controller calls; `SkpdRequest` validation matches the unique-column pattern.

**Ambiguity check:** The `delete()` 409 mapping in Task 10 is explicit. The idempotent migration in Task 2 uses `Schema::hasTable/hasColumn` guards as expected.
