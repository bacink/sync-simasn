# SIM-ASN OAuth + Sanctum Token Auth Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement complete dual-mode authentication: email/password via Sanctum token auth, plus SIM-ASN OAuth as an alternative login. All users get a Sanctum API token after any login method.

**Architecture:** Three-layer auth flow:
1. Email/password: POST /api/v1/auth/login → server validates, creates Sanctum token, returns it + user
2. SIM-ASN OAuth: /auth/sim-asn (backend) → SIM-ASN → /callback/sim-asn → server creates/finds user + Sanctum token → redirects to frontend with token in query param
3. Registration (SIM-ASN new users): /auth/register → POST /api/v1/auth/register-from-sim-asn → same flow as login

All protected API routes use `auth:sanctum` middleware. Sanctum token sent as `Authorization: Bearer <token>` header.

**Tech Stack:** Laravel 13 + Sanctum + `bkpsdm-karawang/sim-asn-php-client` (backend), Nuxt 3 + Pinia + TypeScript (frontend).

---

## Existing State

The following already exist (do NOT re-implement):
- `AuthController` with `login()`, `logout()`, `me()` — basic Sanctum auth working
- `SimAsnCallbackController` with `initiate()` + `callback()` — OAuth flow exists
- `auth.store.ts` with `login()`, `logout()`, `fetchUser()`, `loginWithToken()` — store exists
- `login/index.vue` with SIM-ASN button and token handling — page exists
- `useApi.ts` HTTP composable — HTTP client exists
- `User` model with `HasApiTokens`, `HasRoles`, `sim_asn_user_id`, `sim_asn_token` fields
- `AppServiceProvider` with SIM-ASN token refresh handler
- Spatie permission tables migration exists

**Current state per recent investigation:**
- `AuthController::me()` returns: `id`, `name`, `email`, `opd_id`, `roles`, `permissions`
- `AuthController::login()` returns same structure with `token`
- No SIM-ASN context in `me()` (no `is_sim_asn_authenticated`, no `sim_asn_user_id`)
- No registration endpoint for SIM-ASN users
- No OPD reference endpoint for registration dropdown
- `auth.store.ts` user type is stale (missing fields)
- `useApi.ts` has no 401 interceptor
- Login page doesn't handle `oauth_register` redirect

---

## File Change Map

| File | Action |
|---|---|
| `api/app/Http/Controllers/Api/V1/AuthController.php` | Modify: add `registerFromSimAsn()`, update `me()` with SIM-ASN context |
| `api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php` | Create: form request for registration |
| `api/app/Models/Opd.php` | Check: OPD model (may already exist) |
| `api/app/Http/Controllers/Api/V1/OpdController.php` | Create: `index()` for OPD list |
| `api/routes/api.php` | Modify: add `register-from-sim-asn` route, add `ref/opd` route |
| `client/app/types/api.ts` | Modify: update `User` interface with all backend fields |
| `client/app/stores/auth.store.ts` | Modify: add `_persistToken` helper, fix userRole getter, improve `fetchUser()` error handling |
| `client/app/composables/useApi.ts` | Modify: add 401 interceptor that clears auth store on 401 |
| `client/app/pages/login/index.vue` | Modify: handle `oauth_register` query param redirect |
| `client/app/pages/auth/register/index.vue` | Create: SIM-ASN registration page |
| `api/tests/Feature/AuthTest.php` | Create: comprehensive auth tests |

---

## Task 1: Backend — RegisterFromSimAsnRequest

**Files:**
- Create: `api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php`
- Reference: `api/app/Http/Requests/Auth/LoginRequest.php` (existing pattern)

- [ ] **Step 1: Create the form request**

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFromSimAsnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'opd_id' => ['required', 'integer', 'exists:opds,id'],
            'sim_asn_user_id' => ['required', 'string', 'uuid', 'unique:users,sim_asn_user_id'],
            'sim_asn_token' => ['required', 'array'],
            'sim_asn_token.access_token' => ['required', 'string'],
            'sim_asn_token.refresh_token' => ['nullable', 'string'],
            'sim_asn_token.expires_at' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php
git commit -m "feat: add RegisterFromSimAsnRequest form validation"
```

---

## Task 2: Backend — RegisterFromSimAsn endpoint

**Files:**
- Modify: `api/app/Http/Controllers/Api/V1/AuthController.php`
- Create: `api/tests/Feature/AuthTest.php` (test scaffold)

- [ ] **Step 1: Add RegisterFromSimAsn method to AuthController**

Read the current `AuthController.php` first. Add this method after `me()`:

```php
public function registerFromSimAsn(RegisterFromSimAsnRequest $request): JsonResponse
{
    $validated = $request->validated();

    $user = \App\Models\User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => $validated['password'],
        'opd_id' => $validated['opd_id'],
        'sim_asn_user_id' => $validated['sim_asn_user_id'],
        'sim_asn_token' => $validated['sim_asn_token'],
    ]);

    $token = $user->createToken('sim-asn-token')->plainTextToken;

    return ApiResponse::success([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'opd_id' => $user->opd_id,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'sim_asn_user_id' => $user->sim_asn_user_id,
            'is_sim_asn_authenticated' => true,
        ],
        'token' => $token,
    ], [], 201);
}
```

- [ ] **Step 2: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/app/Http/Controllers/Api/V1/AuthController.php
git commit -m "feat: add registerFromSimAsn endpoint to AuthController"
```

---

## Task 3: Backend — Update me() with SIM-ASN context

**Files:**
- Modify: `api/app/Http/Controllers/Api/V1/AuthController.php:54-66`

- [ ] **Step 1: Update me() method**

Replace the current `me()` body with:

```php
public function me(Request $request): JsonResponse
{
    $user = $request->user();

    return ApiResponse::success([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'opd_id' => $user->opd_id,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'sim_asn_user_id' => $user->sim_asn_user_id,
        'is_sim_asn_authenticated' => !empty($user->sim_asn_user_id),
    ]);
}
```

- [ ] **Step 2: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/app/Http/Controllers/Api/V1/AuthController.php
git commit -m "feat: add sim_asn_user_id and is_sim_asn_authenticated to me endpoint"
```

---

## Task 4: Backend — OPD Reference endpoint

**Files:**
- Check: `api/app/Models/Opd.php` (may already exist)
- Create: `api/app/Http/Controllers/Api/V1/OpdController.php`
- Modify: `api/routes/api.php`

- [ ] **Step 1: Check if Opd model exists**

Run: `ls /home/bacink/DevApps/2026/kgb/api/app/Models/Opd.php`

If it exists, skip to Step 3. If not, create it:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Create OpdController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use Illuminate\Http\JsonResponse;

class OpdController extends Controller
{
    public function index(): JsonResponse
    {
        $opds = Opd::orderBy('nama')->get(['id', 'nama', 'kode']);

        return ApiResponse::success($opds);
    }
}
```

- [ ] **Step 4: Add route to api.php**

Read `api/routes/api.php` first. Find the existing auth routes block (around `POST /auth/login`) and add a new route group for ref endpoints. Add this route:

```php
Route::get('/ref/opd', [OpdController::class, 'index']);
```

The `/ref/opd` route should be inside the `Route::middleware(['auth:sanctum', ...])` group so only authenticated users can fetch OPD list (the registration is only for authenticated SIM-ASN users).

- [ ] **Step 5: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/app/Models/Opd.php api/app/Http/Controllers/Api/V1/OpdController.php api/routes/api.php
git commit -m "feat: add OPD reference endpoint for registration form"
```

---

## Task 5: Backend — Add register-from-sim-asn route

**Files:**
- Modify: `api/routes/api.php`

- [ ] **Step 1: Add route to api.php**

Read `api/routes/api.php` first. Find the auth routes section. Add this route **before** the `auth:sanctum` protected routes:

```php
Route::post('/auth/register-from-sim-asn', [AuthController::class, 'registerFromSimAsn']);
```

This route should be public (no auth middleware) — the user has a SIM-ASN token in their browser but not yet a local account.

- [ ] **Step 2: Verify route**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan route:list --name=register-from-sim-asn`

Expected: Shows `POST /api/v1/auth/register-from-sim-asn` with no middleware.

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/routes/api.php
git commit -m "feat: add register-from-sim-asn route to api.php"
```

---

## Task 6: Frontend — Update User TypeScript type

**Files:**
- Modify: `client/app/types/api.ts`

- [x] **Step 1: Update User interface**

Replace the existing `User` interface with:

```typescript
export interface User {
  id: number
  name: string
  email: string
  opd_id: number | null
  role?: 'admin' | 'verifikator' | 'operator'
  roles: string[]
  permissions: string[]
  avatar_url?: string
  sim_asn_user_id: string | null
  is_sim_asn_authenticated: boolean
}
```

Also update the `ApiResponse` usage in the auth store to match the real API response shape (it wraps data in `{ user, token }`):

```typescript
export interface AuthResponse {
  user: User
  token: string
}
```

- [x] **Step 2: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add client/app/types/api.ts
git commit -m "feat: update User type with SIM-ASN fields and roles array"
```

---

## Task 7: Frontend — Update auth store

**Files:**
- Modify: `client/app/stores/auth.store.ts`

- [ ] **Step 1: Rewrite auth store**

Read the current `auth.store.ts` first. Replace it entirely with this updated version:

```typescript
import { defineStore } from "pinia";
import type { User, AuthResponse } from "~/types/api";
import type { ApiResponse } from "~/types/api";

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  /** Base64-encoded SIM-ASN payload from OAuth callback, for pre-filling registration */
  oauthRegistrationPayload: string | null;
}

export const useAuthStore = defineStore("auth", {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    oauthRegistrationPayload: null,
  }),

  getters: {
    userRole: (state): string | null =>
      (state.user?.roles?.[0]) ?? state.user?.role ?? null,
    isAdmin: (state) => state.user?.roles?.includes("admin") ?? state.user?.role === "admin",
    isVerifikator: (state) =>
      state.user?.roles?.includes("verifikator") ?? state.user?.role === "verifikator",
    isOperator: (state) =>
      state.user?.roles?.includes("operator") ?? state.user?.role === "operator",
    isLoggedIn: (state) => state.isAuthenticated,
    isSimAsnAuthenticated: (state) => state.user?.is_sim_asn_authenticated ?? false,
  },

  actions: {
    _persistToken(token: string): void {
      this.token = token;
      this.isAuthenticated = true;
      if (import.meta.client) {
        localStorage.setItem("auth_token", token);
      }
      if (import.meta.server) {
        useCookie("auth_token", { maxAge: 60 * 60 * 24 }).value = token;
      }
    },

    _clearToken(): void {
      this.token = null;
      this.isAuthenticated = false;
      this.user = null;
      if (import.meta.client) {
        localStorage.removeItem("auth_token");
      }
      if (import.meta.server) {
        useCookie("auth_token").value = null;
      }
    },

    _loadToken(): void {
      if (import.meta.client && !this.token) {
        this.token = localStorage.getItem("auth_token") || null;
      }
    },

    async login(credentials: {
      email: string;
      password: string;
    }): Promise<void> {
      const api = useApi();
      const res = await api.post<ApiResponse<AuthResponse>>(
        "/api/v1/auth/login",
        credentials,
      );
      this.user = res.data.user;
      this._persistToken(res.data.token);
    },

    async loginWithToken(token: string): Promise<void> {
      this._persistToken(token);
      await this.fetchUser();
    },

    async logout(): Promise<void> {
      const api = useApi();
      try {
        await api.post("/api/v1/auth/logout");
      } catch {
        // ignore errors on logout
      }
      this._clearToken();
    },

    async fetchUser(): Promise<void> {
      this._loadToken();
      if (!this.token) return;

      const api = useApi();
      try {
        const res = await api.get<ApiResponse<User>>("/api/v1/auth/me");
        this.user = res.data;
        this.isAuthenticated = true;
      } catch {
        this._clearToken();
      }
    },

    setToken(token: string): void {
      this._persistToken(token);
    },

    setOAuthRegistrationPayload(payload: string): void {
      this.oauthRegistrationPayload = payload;
    },

    clearOAuthRegistrationPayload(): void {
      this.oauthRegistrationPayload = null;
    },
  },
});
```

- [ ] **Step 2: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add client/app/stores/auth.store.ts
git commit -m "feat: rewrite auth store with helpers and SIM-ASN OAuth support"
```

---

## Task 8: Frontend — Update useApi with 401 interceptor

**Files:**
- Modify: `client/app/composables/useApi.ts`

- [ ] **Step 1: Update useApi request function**

Read the current `useApi.ts` first. Find the `request` function's error handling block (where it throws `createError`). Add a call to `authStore._clearToken()` in the 401 catch block:

Replace the existing `if (!response.ok)` block with:

```typescript
if (!response.ok) {
  const error: ApiError = {
    status: response.status,
    message: response.statusText,
    errors: {},
  };

  try {
    const body = await response.json();
    error.message = body.message || body.error || response.statusText;
    error.errors = body.errors || {};
  } catch {
    // use status text as fallback
  }

  // Clear auth store on 401 so stale tokens are discarded
  if (response.status === 401) {
    const authStore = useAuthStore();
    authStore._clearToken();
  }

  throw createError({
    statusCode: response.status,
    message: error.message,
    data: error,
  });
}
```

- [ ] **Step 2: Verify no type errors**

The `authStore._clearToken()` call must be inside the `if (response.status === 401)` block.

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add client/app/composables/useApi.ts
git commit -m "feat: add 401 interceptor to clear expired auth tokens"
```

---

## Task 9: Frontend — Update login page for oauth_register redirect

**Files:**
- Modify: `client/app/pages/login/index.vue`

- [ ] **Step 1: Update onMounted**

Read the current `login/index.vue` first. In the `onMounted` hook, update the handling order to check `oauth_register` first (before `access_token`):

Replace the entire `onMounted` block with:

```typescript
onMounted(() => {
  const oauthRegister = route.query.oauth_register as string | undefined;
  const accessToken = route.query.access_token as string | undefined;
  const errorParam = route.query.error as string | undefined;

  // Priority 1: SIM-ASN OAuth new user — redirect to registration page
  if (oauthRegister) {
    authStore.setOAuthRegistrationPayload(oauthRegister);
    router.replace({ query: {} });
    router.push("/auth/register");
    return;
  }

  // Priority 2: Existing user returning with Sanctum token
  if (accessToken) {
    authStore.loginWithToken(accessToken).then(() => {
      router.replace({ query: {} });
      router.push("/dashboard");
    }).catch((e: any) => {
      error.value = e.data?.message || "Login gagal. Silakan coba lagi.";
    });
    return;
  }

  // Priority 3: OAuth error from callback
  if (errorParam) {
    if (errorParam === "user_not_found") {
      error.value = "Akun Anda belum terdaftar di sistem ini.";
    } else if (errorParam === "oauth_denied") {
      error.value = "Login SIM-ASN dibatalkan.";
    } else {
      error.value = "Login SIM-ASN gagal. Silakan coba lagi.";
    }
    router.replace({ query: {} });
  }
});
```

- [ ] **Step 2: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add client/app/pages/login/index.vue
git commit -m "feat: handle oauth_register redirect on login page"
```

---

## Task 10: Frontend — Create registration page

**Files:**
- Create: `client/app/pages/auth/register/index.vue`
- Create: `client/app/pages/auth/index.vue` (parent layout redirect)

- [ ] **Step 1: Create registration page**

Create the file `client/app/pages/auth/register/index.vue`:

```vue
<script setup lang="ts">
import type { ApiResponse } from "~/types/api";
import type { AuthResponse } from "~/types/api";

definePageMeta({
  layout: "auth",
  middleware: [],
});

const authStore = useAuthStore();
const api = useApi();
const router = useRouter();

const oauthPayload = computed(() => {
  if (!authStore.oauthRegistrationPayload) return null;
  try {
    return JSON.parse(atob(authStore.oauthRegistrationPayload));
  } catch {
    return null;
  }
});

// Redirect to login if no OAuth payload
onMounted(() => {
  if (!authStore.oauthRegistrationPayload) {
    router.replace("/login");
  }
});

const opds = ref<{ id: number; nama: string; kode: string | null }[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const form = ref({
  name: oauthPayload.value?.name || "",
  email: "",
  password: "",
  password_confirmation: "",
  opd_id: null as number | null,
});

onMounted(async () => {
  try {
    const res = await api.get<ApiResponse<typeof opds.value>>("/api/v1/ref/opd");
    opds.value = res.data;
  } catch (e: any) {
    error.value = "Gagal memuat daftar OPD.";
  }
});

async function register() {
  if (!authStore.oauthRegistrationPayload) {
    error.value = "Sesi pendaftaran tidak valid.";
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const payload = JSON.parse(atob(authStore.oauthRegistrationPayload));

    const res = await api.post<ApiResponse<AuthResponse>>(
      "/api/v1/auth/register-from-sim-asn",
      {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
        opd_id: form.value.opd_id,
        sim_asn_user_id: payload.sim_asn_user_id,
        sim_asn_token: payload.sim_asn_token,
      },
    );

    authStore.clearOAuthRegistrationPayload();
    await authStore.loginWithToken(res.data.token);
    router.push("/dashboard");
  } catch (e: any) {
    const msg = e.data?.errors
      ? Object.values(e.data.errors).flat().join(", ")
      : e.data?.message || "Pendaftaran gagal. Silakan coba lagi.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pendaftaran Akun</h1>
        <p class="text-gray-500 mt-1">
          Lengkapi data di bawah untuk mengaktifkan akun SIM-ASN Anda
        </p>
      </div>

      <div
        v-if="oauthPayload"
        class="mb-4 p-3 bg-indigo-50 text-indigo-700 text-sm rounded-lg"
      >
        Masuk sebagai: <strong>{{ oauthPayload.name }}</strong>
        (NIP: {{ oauthPayload.nip }})
      </div>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="email@example.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja (OPD)</label>
          <select
            v-model="form.opd_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          >
            <option :value="null" disabled>Pilih Unit Kerja</option>
            <option v-for="opd in opds" :key="opd.id" :value="opd.id">
              {{ opd.nama }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            minlength="8"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Konfirmasi Password
          </label>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            minlength="8"
          />
        </div>

        <div v-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium"
        >
          {{ loading ? "Mendaftarkan..." : "Daftarkan Akun" }}
        </button>

        <p class="text-center text-sm text-gray-500">
          Sudah punya akun?
          <NuxtLink to="/login" class="text-indigo-600 hover:underline">
            Masuk di sini
          </NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Create auth directory redirect**

Create `client/app/pages/auth/index.vue` to prevent 404 on /auth/:

```vue
<script setup lang="ts">
definePageMeta({ middleware: [] });
navigateTo("/login", { replace: true });
</script>
<template><div></div></template>
```

- [ ] **Step 3: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add client/app/pages/auth/register/index.vue client/app/pages/auth/index.vue
git commit -m "feat: add SIM-ASN OAuth registration page"
```

---

## Task 11: Tests — Comprehensive AuthTest

**Files:**
- Create: `api/tests/Feature/AuthTest.php`

- [ ] **Step 1: Create AuthTest**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_user_and_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'opd_id', 'roles', 'permissions', 'sim_asn_user_id', 'is_sim_asn_authenticated'],
                    'token',
                ],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'test@example.com',
                        'sim_asn_user_id' => null,
                        'is_sim_asn_authenticated' => false,
                    ],
                ],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID',
                ],
            ]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_me_returns_user_with_sim_asn_fields(): void
    {
        $user = User::factory()->create([
            'sim_asn_user_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'sim_asn_user_id' => '550e8400-e29b-41d4-a716-446655440000',
                    'is_sim_asn_authenticated' => true,
                ],
            ]);
    }

    public function test_me_returns_is_sim_asn_authenticated_false_for_non_oauth_user(): void
    {
        $user = User::factory()->create([
            'sim_asn_user_id' => null,
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_sim_asn_authenticated' => false,
                ],
            ]);
    }

    public function test_register_from_sim_asn_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'opd_id' => 1,
            'sim_asn_user_id' => '660e8400-e29b-41d4-a716-446655440001',
            'sim_asn_token' => [
                'access_token' => 'sim_asn_access_token_xyz',
                'refresh_token' => 'sim_asn_refresh_token_xyz',
                'expires_at' => '2027-01-01T00:00:00Z',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'opd_id', 'roles', 'permissions', 'sim_asn_user_id', 'is_sim_asn_authenticated'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'sim_asn_user_id' => '660e8400-e29b-41d4-a716-446655440001',
        ]);
    }

    public function test_register_from_sim_asn_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'opd_id' => 1,
            'sim_asn_user_id' => '660e8400-e29b-41d4-a716-446655440001',
            'sim_asn_token' => [
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_at' => null,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_from_sim_asn_fails_with_duplicate_sim_asn_user_id(): void
    {
        User::factory()->create(['sim_asn_user_id' => '660e8400-e29b-41d4-a716-446655440001']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'name' => 'Budi Santoso',
            'email' => 'budi2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'opd_id' => 1,
            'sim_asn_user_id' => '660e8400-e29b-41d4-a716-446655440001',
            'sim_asn_token' => [
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_at' => null,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sim_asn_user_id']);
    }

    public function test_register_from_sim_asn_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'password_confirmation', 'opd_id', 'sim_asn_user_id', 'sim_asn_token']);
    }

    public function test_protected_route_requires_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run tests**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan test --compact --filter=AuthTest`
Expected: All tests pass.

- [ ] **Step 3: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
cd /home/bacink/DevApps/2026/kgb
git add api/tests/Feature/AuthTest.php
git commit -m "test: add comprehensive AuthTest for dual-mode auth"
```

---

## Verification

- [ ] Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan route:list --name=auth`
  Expected: `POST login`, `POST logout`, `GET me`, `POST register-from-sim-asn`
- [ ] Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan test`
  Expected: All tests pass
- [ ] Run: `cd /home/bacink/DevApps/2026/kgb && git diff --stat`
  Expected: 12 files modified/created per File Change Map
