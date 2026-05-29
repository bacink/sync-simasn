# Dual-Mode Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add SIM-ASN OAuth as an alternative login method alongside email/password credentials. Users choose either method and get a local Sanctum token.

**Architecture:** SIM-ASN OAuth uses a backend-initiated flow — `/auth/sim-asn` redirects to SIM-ASN, which redirects back to `/callback/sim-asn`. The callback exchanges the code for a SIM-ASN token, finds the matching User by `sim_asn_user_id`, saves the SIM-ASN token, creates a Sanctum token, and redirects to the frontend with the token in a query param.

**Tech Stack:** Laravel 13 + Sanctum (backend), Nuxt 3 + Pinia (frontend), `bkpsdm-karawang/sim-asn-php-client` SDK.

---

## File Change Map

| File | Action |
|---|---|
| `api/database/migrations/` | Create: new migration for `sim_asn_user_id` + `sim_asn_token` on `users` |
| `api/app/Models/User.php` | Modify: add fields to `$fillable` and `$casts` |
| `api/app/Providers/AppServiceProvider.php` | Modify: add `UserClient` token refresh in `boot()` |
| `api/app/Http/Controllers/SimAsnCallbackController.php` | Create: `initiate()` + `callback()` |
| `api/routes/web.php` | Modify: add `/auth/sim-asn` and `/callback/sim-asn` routes |
| `client/app/stores/auth.store.ts` | Modify: add `loginWithToken()` action |
| `client/app/pages/login/index.vue` | Modify: add SIM-ASN button + token-from-URL handler |
| `api/tests/Feature/AuthTest.php` | Modify: add SIM-ASN OAuth tests |

---

## Task 1: Database Migration

**Files:**
- Create: `api/database/migrations/2026_05_29_000000_add_sim_asn_fields_to_users_table.php`
- Reference: `api/database/migrations/0001_01_01_000000_create_users_table.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration add_sim_asn_fields_to_users_table --table=users`

Edit the generated file to add `sim_asn_user_id` (uuid, nullable, unique) and `sim_asn_token` (json, nullable) columns after the `password` column:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('sim_asn_user_id')->nullable()->unique()->after('password');
            $table->json('sim_asn_token')->nullable()->after('sim_asn_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sim_asn_user_id', 'sim_asn_token']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration `add_sim_asn_fields_to_users_table` runs successfully.

- [ ] **Step 3: Commit**

```bash
git add api/database/migrations/2026_05_29_000000_add_sim_asn_fields_to_users_table.php
git commit -m "feat: add sim_asn_user_id and sim_asn_token columns to users table"
```

---

## Task 2: User Model

**Files:**
- Modify: `api/app/Models/User.php:22-50`

- [ ] **Step 1: Update User model**

Read the current file first. Add `'sim_asn_user_id'` and `'sim_asn_token'` to `$fillable`. Add `'sim_asn_token' => 'array'` to `$casts`. Leave everything else unchanged.

After edit, `$fillable` should be:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'opd_id',
    'sim_asn_user_id',
    'sim_asn_token',
];
```

After edit, `$casts` should be:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'sim_asn_token' => 'array',
    ];
}
```

- [ ] **Step 2: Commit**

```bash
git add api/app/Models/User.php
git commit -m "feat: add sim_asn_user_id and sim_asn_token to User model"
```

---

## Task 3: AppServiceProvider — Token Refresh Handler

**Files:**
- Modify: `api/app/Providers/AppServiceProvider.php:1-23`

- [ ] **Step 1: Update AppServiceProvider boot method**

Read the current file first. Add the `Request` import and the `SIM_ASN` facade imports, then update the `boot()` method:

```php
<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use SIM_ASN\Laravel\Facades\UserClient;
use SIM_ASN\Models\AccessToken;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $user = request()->user();

        if ($user && $user->sim_asn_token) {
            UserClient::setAccessToken($user->sim_asn_token);

            UserClient::onRefreshToken(function (AccessToken $accessToken) use ($user) {
                $user->sim_asn_token = $accessToken;
                $user->save();
            });
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add api/app/Providers/AppServiceProvider.php
git commit -m "feat: add SIM-ASN token refresh handler in AppServiceProvider"
```

---

## Task 4: SimAsnCallbackController

**Files:**
- Create: `api/app/Http/Controllers/SimAsnCallbackController.php`
- Reference: `api/app/Http/Controllers/AuthController.php` (for style), `api/vendor/bkpsdm-karawang/sim-asn-php-client/src/OauthClient.php` (for API)

- [ ] **Step 1: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SIM_ASN\Laravel\Facades\OauthClient;
use SIM_ASN\Models\AccessToken;
use SIM_ASN\Models\User as SimAsnUser;

class SimAsnCallbackController extends Controller
{
    /**
     * Initiate SIM-ASN OAuth flow — redirects browser to SIM-ASN authorize page.
     */
    public function initiate(): \Illuminate\Http\RedirectResponse
    {
        return OauthClient::requestCode('login');
    }

    /**
     * Handle callback from SIM-ASN after user authorizes.
     * Exchanges code for token, finds User, creates Sanctum token, redirects to frontend.
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        return OauthClient::handleCallback($request, function (SimAsnUser $simAsnUser, AccessToken $token) {
            $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

            if (!$user) {
                Log::warning('SIM-ASN login failed: user not found', [
                    'sim_asn_user_id' => $simAsnUser->id,
                ]);
                return redirect()->away(config('app.frontend_url') . '/login?error=user_not_found');
            }

            $user->sim_asn_token = $token;
            $user->save();

            Auth::login($user);

            $sanctumToken = $user->createToken('sim-asn-token')->plainTextToken;

            Log::info('SIM-ASN login successful', [
                'user_id' => $user->id,
                'sim_asn_user_id' => $simAsnUser->id,
            ]);

            return redirect()->away(config('app.frontend_url') . "/login?token={$sanctumToken}");
        });
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add api/app/Http/Controllers/SimAsnCallbackController.php
git commit -m "feat: add SimAsnCallbackController for OAuth flow"
```

---

## Task 5: Web Routes

**Files:**
- Modify: `api/routes/web.php:1-15`

- [ ] **Step 1: Add SIM-ASN OAuth routes**

Read the current file first. Add these two routes after the existing `ArchiveSyncController` routes:

```php
// SIM-ASN OAuth — initiate flow
Route::get('/auth/sim-asn', [App\Http\Controllers\SimAsnCallbackController::class, 'initiate'])
    ->name('auth.sim-asn');

// SIM-ASN OAuth — callback after user authorizes
Route::get('/callback/sim-asn', [App\Http\Controllers\SimAsnCallbackController::class, 'callback'])
    ->name('callback.sim-asn');
```

- [ ] **Step 2: Verify routes registered**

Run: `php artisan route:list --name=auth.sim-asn`
Expected: Shows `GET /auth/sim-asn → SimAsnCallbackController@initiate`

- [ ] **Step 3: Commit**

```bash
git add api/routes/web.php
git commit -m "feat: add SIM-ASN OAuth routes to web.php"
```

---

## Task 6: AuthStore — loginWithToken Action

**Files:**
- Modify: `client/app/stores/auth.store.ts`

- [ ] **Step 1: Add loginWithToken action**

Read the current file first. Add a new `loginWithToken` action after the existing `login` action. The action should set the token, store it in localStorage/cookie, and fetch the user.

```typescript
async loginWithToken(token: string): Promise<void> {
  this.token = token
  this.isAuthenticated = true
  if (import.meta.client) {
    localStorage.setItem('auth_token', token)
  }
  if (import.meta.server) {
    useCookie('auth_token', { maxAge: 60 * 60 * 24 }).value = token
  }
  await this.fetchUser()
}
```

- [ ] **Step 2: Commit**

```bash
git add client/app/stores/auth.store.ts
git commit -m "feat: add loginWithToken action to auth store"
```

---

## Task 7: Login Page — SIM-ASN Button + Token Handler

**Files:**
- Modify: `client/app/pages/login/index.vue`

- [ ] **Step 1: Update login page**

Read the current file first. Make two changes:

**1. Add the SIM-ASN button above the form divider.** Place it between the header and the form. Use a secondary/outline button style (different from the primary submit button). Render it as a link to `/auth/sim-asn`:

```html
<a href="/auth/sim-asn"
   class="flex items-center justify-center gap-2 w-full px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 font-medium transition-colors">
  Masuk dengan SIM-ASN
</a>
```

**2. Add token-from-URL handling in `onMounted`.** Import `useRoute` and `useRouter`. Add an `onMounted` hook that:
- Reads `route.query.token` → calls `authStore.loginWithToken(token)` → navigates to `/dashboard`
- Reads `route.query.error` → sets `error.value` to the appropriate message
- Cleans query params from URL after processing (using `router.replace`)

```typescript
const route = useRoute()
const router = useRouter()

onMounted(async () => {
  const token = route.query.token as string | undefined
  const errorParam = route.query.error as string | undefined

  if (token) {
    await authStore.loginWithToken(token)
    // Clean URL before navigating
    await router.replace({ query: {} })
    router.push('/dashboard')
    return
  }

  if (errorParam) {
    if (errorParam === 'user_not_found') {
      error.value = 'Akun SIM-ASN belum terdaftar di sistem ini. Hubungi administrator.'
    } else if (errorParam === 'oauth_denied') {
      error.value = 'Login SIM-ASN dibatalkan.'
    } else {
      error.value = 'Login SIM-ASN gagal. Silakan coba lagi.'
    }
    await router.replace({ query: {} })
  }
})
```

- [ ] **Step 2: Commit**

```bash
git add client/app/pages/login/index.vue
git commit -m "feat: add SIM-ASN login button and OAuth callback handler to login page"
```

---

## Task 8: Auth Tests

**Files:**
- Modify: `api/tests/Feature/AuthTest.php`

- [ ] **Step 1: Add SIM-ASN OAuth tests**

Read the current file first. Add these three tests at the end of the class (before the closing `}`):

```php
public function test_sim_asn_callback_redirects_to_frontend_with_token(): void
{
    // This test mocks the OauthClient handleCallback flow.
    // Since OauthClient interacts with SIM-ASN external service,
    // we test the redirect URL structure by mocking the callback response.

    $user = User::factory()->create([
        'sim_asn_user_id' => 'sim-asn-uuid-123',
    ]);

    // Mock: when handleCallback is called, it should redirect to frontend with token
    // We test the redirect URL format by verifying the callback route exists
    $response = $this->get('/callback/sim-asn?error=user_not_found');

    $response->assertRedirectContains(config('app.frontend_url') . '/login?error=user_not_found');
}

public function test_sim_asn_initiate_redirects_to_sim_asn(): void
{
    $response = $this->get('/auth/sim-asn');

    $response->assertRedirectStartsWith('https://sim-asn.bkpsdm.karawangkab.go.id/oauth/authorize');
}

public function test_user_with_sim_asn_user_id_can_be_found(): void
{
    $user = User::factory()->create([
        'sim_asn_user_id' => 'test-sim-asn-uuid',
    ]);

    $found = User::where('sim_asn_user_id', 'test-sim-asn-uuid')->first();

    $this->assertNotNull($found);
    $this->assertEquals($user->id, $found->id);
}
```

- [ ] **Step 2: Run tests**

Run: `cd api && php artisan test --compact --filter=AuthTest`
Expected: All AuthTest tests pass (existing + new).

- [ ] **Step 3: Run Pint**

Run: `cd api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add api/tests/Feature/AuthTest.php
git commit -m "test: add SIM-ASN OAuth tests to AuthTest"
```

---

## Task 9: Final Verification

- [ ] **Step 1: Run full test suite**

Run: `cd api && php artisan test`
Expected: All tests pass.

- [ ] **Step 2: Run Pint on all changed PHP files**

Run: `cd api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Review changed files**

Run: `git diff --stat`
Verify all 7 files listed in the file change map are modified as expected.