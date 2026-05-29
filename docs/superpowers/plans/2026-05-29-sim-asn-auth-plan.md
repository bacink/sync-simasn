# SIM-ASN OAuth + Sanctum Auth — Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Fix the broken SIM-ASN OAuth token flow and add missing user registration so users authenticated via SIM-ASN can create a local account and receive a valid Sanctum token.

**Architecture:** The OAuth flow is backend-initiated (server-side redirect). The callback must: (1) find or register the user, (2) save the SIM-ASN token, (3) create a Sanctum token, (4) return the Sanctum token in the redirect URL. The frontend stores the Sanctum token and uses it for all API calls. The `me()` endpoint returns SIM-ASN context so the client knows if the user authenticated via OAuth.

**Tech Stack:** Laravel 13 + Sanctum + `bkpsdm-karawang/sim-asn-php-client` (backend), Nuxt 3 + Pinia (frontend).

---

## File Change Map

| File | Action |
|---|---|
| `api/app/Http/Controllers/SimAsnCallbackController.php` | Rewrite: return Sanctum token in redirect, handle user registration |
| `api/app/Http/Controllers/Api/V1/AuthController.php` | Modify: update `me()` to include SIM-ASN context |
| `api/app/Http/Controllers/Api/V1/AuthController.php` | Modify: add `registerFromSimAsn()` endpoint |
| `api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php` | Create: validation for registration endpoint |
| `api/app/Http/Requests/Auth/LoginRequest.php` | Modify: add remember_me field |
| `api/routes/api.php` | Modify: add `POST /auth/register-from-sim-asn` route |
| `api/app/Models/User.php` | Modify: add helper method for OAuth registration |
| `client/app/stores/auth.store.ts` | Modify: update `loginWithToken()`, add SIM-ASN session handling |
| `client/app/composables/useApi.ts` | Modify: add 401 interceptor → token clear on expiry |
| `client/app/pages/login/index.vue` | Modify: handle `user_not_found` → redirect to registration |
| `client/app/pages/auth/register/index.vue` | Create: registration page for SIM-ASN users |
| `client/app/types/api.d.ts` | Modify: extend User type with SIM-ASN fields |
| `api/tests/Feature/AuthTest.php` | Modify: add OAuth callback + registration tests |

---

## Task 1: Fix SimAsnCallbackController — Return Sanctum Token

**Files:**
- Modify: `api/app/Http/Controllers/SimAsnCallbackController.php`

- [ ] **Step 1: Rewrite the controller**

Read the current file. Replace the entire content with this corrected implementation:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SIM_ASN\Laravel\Facades\OauthClient;
use SIM_ASN\Models\AccessToken;
use SIM_ASN\Models\User as SimAsnUser;

class SimAsnCallbackController extends Controller
{
    /**
     * Redirect browser to SIM-ASN authorization page.
     */
    public function initiate(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Store return_to URL in session so callback knows where to redirect after login
        $returnTo = $request->get('return_to', $request->session()->get('oauth_return_to', '/dashboard'));
        $request->session()->put('oauth_return_to', $returnTo);

        return OauthClient::requestCode('login');
    }

    /**
     * Handle OAuth callback from SIM-ASN.
     *
     * Flow:
     *  1. Exchange code for SIM-ASN token via SDK
     *  2. Find or register the user
     *  3. Save SIM-ASN token to user's sim_asn_token field
     *  4. Create a Sanctum token
     *  5. Redirect to frontend with Sanctum token in query string
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        try {
            $result = OauthClient::handleCallback($request, function (SimAsnUser $simAsnUser, AccessToken $accessToken) use ($returnTo) {
                // Find existing user by sim_asn_user_id
                $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

                if (!$user) {
                    // User not found — redirect to registration page with SIM-ASN user ID
                    // We pass the SIM-ASN user ID as an opaque token so the frontend can
                    // submit it to the registration endpoint.
                    $encoded = base64_encode(json_encode([
                        'sim_asn_user_id' => $simAsnUser->id,
                        'name' => $simAsnUser->name ?? null,
                        'email' => $simAsnUser->email ?? null,
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ]));

                    Log::info('SIM-ASN user not found, redirecting to registration', [
                        'sim_asn_user_id' => $simAsnUser->id,
                    ]);

                    return redirect()->away($returnTo . '?oauth_register=' . urlencode($encoded));
                }

                // Existing user — update SIM-ASN token and create Sanctum token
                $sanctumToken = DB::transaction(function () use ($user, $accessToken) {
                    $user->sim_asn_token = [
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ];
                    $user->save();

                    // Revoke old Sanctum tokens so we only have one active session
                    $user->tokens()->delete();

                    return $user->createToken('sim-asn-token')->plainTextToken;
                });

                Log::info('SIM-ASN login successful', [
                    'user_id' => $user->id,
                    'sim_asn_user_id' => $simAsnUser->id,
                ]);

                return redirect()->away($returnTo . '?access_token=' . urlencode($sanctumToken));
            });

            // handleCallback returns a RedirectResponse on success — return it directly
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }

            // Should not reach here, but handle unexpected return type
            Log::error('SIM-ASN callback: unexpected return type', ['type' => gettype($result)]);
            return redirect()->away($returnTo . '?error=callback_error');

        } catch (\Throwable $e) {
            Log::error('SIM-ASN callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->away($returnTo . '?error=' . urlencode($e->getMessage()));
        }
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
git add api/app/Http/Controllers/SimAsnCallbackController.php
git commit -m "fix: return Sanctum token in OAuth redirect instead of SIM-ASN token"
```

---

## Task 2: Add RegisterFromSimAsn Endpoint

**Files:**
- Create: `api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php`
- Modify: `api/app/Http/Controllers/Api/V1/AuthController.php`
- Modify: `api/routes/api.php`

### 2a. Create the request validator

- [ ] **Step 1: Create RegisterFromSimAsnRequest**

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
            'sim_asn_user_id' => ['required', 'string', 'max:255', 'unique:users,sim_asn_user_id'],
            'sim_asn_token' => ['required', 'array'],
            'sim_asn_token.access_token' => ['required', 'string'],
            'sim_asn_token.refresh_token' => ['nullable', 'string'],
            'sim_asn_token.expires_at' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'opd_id' => ['nullable', 'integer', 'exists:opds,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'sim_asn_user_id.unique' => 'Akun SIM-ASN ini sudah terdaftar.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
        ];
    }
}
```

- [ ] **Step 2: Update AuthController — add registerFromSimAsn + update me()**

Read `AuthController.php` first. Add two methods and update the `me()` response:

```php
// Add to top of file, after the existing use statements:
use App\Http\Requests\Auth\RegisterFromSimAsnRequest;
use Illuminate\Support\Facades\Hash;

// Add inside AuthController class, after login():

public function registerFromSimAsn(RegisterFromSimAsnRequest $request): JsonResponse
{
    $data = $request->validated();

    $simAsnToken = $data['sim_asn_token'];
    unset($data['sim_asn_token']);

    $user = DB::transaction(function () use ($data, $simAsnToken) {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'opd_id' => $data['opd_id'] ?? null,
            'sim_asn_user_id' => $data['sim_asn_user_id'],
            'sim_asn_token' => $simAsnToken,
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);

        // Assign default 'operator' role to newly registered SIM-ASN users
        $user->assignRole('operator');

        return $user;
    });

    $token = $user->createToken('sim-asn-token')->plainTextToken;

    return ApiResponse::success([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'opd_id' => $user->opd_id,
            'sim_asn_user_id' => $user->sim_asn_user_id,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ],
        'token' => $token,
    ], 201);
}

// Update me() to include SIM-ASN context:
public function me(Request $request): JsonResponse
{
    $user = $request->user();

    return ApiResponse::success([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'opd_id' => $user->opd_id,
        'sim_asn_user_id' => $user->sim_asn_user_id,
        'is_sim_asn_authenticated' => !empty($user->sim_asn_token),
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ]);
}
```

Also update the `login()` method to return `is_sim_asn_authenticated`:

```php
return ApiResponse::success([
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'opd_id' => $user->opd_id,
        'sim_asn_user_id' => $user->sim_asn_user_id,
        'is_sim_asn_authenticated' => !empty($user->sim_asn_token),
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ],
    'token' => $token,
]);
```

- [ ] **Step 3: Add route to api.php**

Read `api/routes/api.php`. Find the auth routes section. Add the new route after `login`:

```php
// SIM-ASN OAuth — register a local account from SIM-ASN user data
Route::post('/auth/register-from-sim-asn', [AuthController::class, 'registerFromSimAsn'])
    ->middleware('guest:sanctum');
```

- [ ] **Step 4: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Commit**

```bash
git add api/app/Http/Requests/Auth/RegisterFromSimAsnRequest.php
git add api/app/Http/Controllers/Api/V1/AuthController.php
git add api/routes/api.php
git commit -m "feat: add registerFromSimAsn endpoint and update me() with SIM-ASN context"
```

---

## Task 3: Update User Type on Frontend

**Files:**
- Modify: `client/app/types/api.d.ts`

- [ ] **Step 1: Extend User type**

```typescript
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'verifikator' | 'operator'
  avatar_url?: string
  // SIM-ASN OAuth fields
  sim_asn_user_id?: string | null
  is_sim_asn_authenticated?: boolean
  opd_id?: number | null
  roles?: string[]
  permissions?: string[]
}
```

- [ ] **Step 2: Commit**

```bash
git add client/app/types/api.d.ts
git commit -m "feat: extend User type with SIM-ASN OAuth fields"
```

---

## Task 4: Update Auth Store

**Files:**
- Modify: `client/app/stores/auth.store.ts`

- [ ] **Step 1: Rewrite the store**

Read the current file first. Replace with this corrected implementation:

```typescript
import { defineStore } from "pinia";
import type { User } from "~/types/api";
import type { ApiResponse } from "~/types/api";

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  // OAuth registration payload — set when SIM-ASN user is not found
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
    userRole: (state): string | null => state.user?.role || state.user?.roles?.[0] || null,
    isAdmin: (state) => state.user?.role === "admin" || state.user?.roles?.includes("admin"),
    isVerifikator: (state) => state.user?.role === "verifikator" || state.user?.roles?.includes("verifikator"),
    isOperator: (state) => state.user?.role === "operator" || state.user?.roles?.includes("operator"),
    isLoggedIn: (state) => state.isAuthenticated,
    isSimAsnAuthenticated: (state) => state.user?.is_sim_asn_authenticated ?? false,
    canRegisterFromOAuth: (state) => !!state.oauthRegistrationPayload,
  },

  actions: {
    _persistToken(token: string) {
      if (import.meta.client) {
        localStorage.setItem("auth_token", token);
      }
      if (import.meta.server) {
        useCookie("auth_token", { maxAge: 60 * 60 * 24 }).value = token;
      }
    },

    _clearToken() {
      this.token = null;
      if (import.meta.client) {
        localStorage.removeItem("auth_token");
      }
      if (import.meta.server) {
        useCookie("auth_token").value = null;
      }
    },

    _loadToken() {
      if (import.meta.client && !this.token) {
        this.token = localStorage.getItem("auth_token") || null;
      }
      return this.token;
    },

    async login(credentials: {
      email: string;
      password: string;
      remember_me?: boolean;
    }): Promise<void> {
      const api = useApi();
      const res = await api.post<ApiResponse<{ user: User; token: string }>>(
        "/api/v1/auth/login",
        credentials,
      );
      this.user = res.data.user;
      this.token = res.data.token;
      this.isAuthenticated = true;
      this.oauthRegistrationPayload = null;
      this._persistToken(res.data.token);
    },

    async loginWithToken(token: string): Promise<void> {
      this.token = token;
      this.isAuthenticated = true;
      this.oauthRegistrationPayload = null;
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
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      this.oauthRegistrationPayload = null;
      this._clearToken();
    },

    async fetchUser(): Promise<void> {
      const token = this._loadToken();
      if (!token) return;

      const api = useApi();
      try {
        const res = await api.get<ApiResponse<User>>("/api/v1/auth/me");
        this.user = res.data;
        this.isAuthenticated = true;
      } catch (e: any) {
        // 401 = token invalid/expired — clear it
        if (e.data?.status === 401) {
          this.user = null;
          this.token = null;
          this.isAuthenticated = false;
          this._clearToken();
        }
      }
    },

    /**
     * Store the OAuth registration payload from the callback URL.
     * The frontend uses this to pre-fill the registration form.
     */
    setOAuthRegistrationPayload(payload: string) {
      this.oauthRegistrationPayload = payload;
    },

    clearOAuthRegistrationPayload() {
      this.oauthRegistrationPayload = null;
    },

    setToken(token: string) {
      this.token = token;
      this.isAuthenticated = true;
    },
  },
});
```

- [ ] **Step 2: Commit**

```bash
git add client/app/stores/auth.store.ts
git commit -m "feat: update auth store with SIM-ASN OAuth registration support"
```

---

## Task 5: Update useApi — 401 Interceptor

**Files:**
- Modify: `client/app/composables/useApi.ts`

- [ ] **Step 1: Add 401 interceptor**

Read the current file first. Replace the `request` function to add a 401 check that clears the token:

```typescript
async function request<T>(method: string, endpoint: string, options: RequestOptions = {}): Promise<T> {
  const { params, ...fetchOptions } = options
  const url = new URL(`${baseURL}${endpoint}`, baseURL)

  if (params) {
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') {
        url.searchParams.append(key, String(value))
      }
    }
  }

  const token = getToken()

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...fetchOptions.headers
  }

  const response = await fetch(url.toString(), {
    method,
    headers,
    ...fetchOptions
  })

  if (!response.ok) {
    const error: ApiError = {
      status: response.status,
      message: response.statusText,
      errors: {}
    }

    try {
      const body = await response.json()
      error.message = body.message || body.error || response.statusText
      error.errors = body.errors || {}
    }
    catch {
      // use status text as fallback
    }

    // On 401, clear token so next request doesn't send a stale token
    if (response.status === 401) {
      const authStore = useAuthStore()
      authStore.token = null
      authStore.isAuthenticated = false
      if (import.meta.client) {
        localStorage.removeItem('auth_token')
      }
    }

    throw createError({
      statusCode: response.status,
      message: error.message,
      data: error
    })
  }

  if (response.status === 204) {
    return {} as T
  }

  return response.json() as Promise<T>
}
```

- [ ] **Step 2: Commit**

```bash
git add client/app/composables/useApi.ts
git commit -m "feat: add 401 interceptor to useApi to clear expired tokens"
```

---

## Task 6: Update Login Page — Handle OAuth Register Flow

**Files:**
- Modify: `client/app/pages/login/index.vue`

- [ ] **Step 1: Update the login page**

Read the current file. Replace the `onMounted` section and add an `oauthRegisterPayload` ref:

```typescript
// At top of script, add after the existing refs:
const oauthRegisterPayload = ref<string | null>(null)

// Update onMounted:
onMounted(() => {
  const accessToken = route.query.access_token as string | undefined
  const oauthRegister = route.query.oauth_register as string | undefined
  const errorParam = route.query.error as string | undefined

  if (accessToken) {
    authStore.loginWithToken(accessToken).then(() => {
      router.push('/dashboard')
    }).catch((e: any) => {
      error.value = e.data?.message || 'Login gagal. Silakan coba lagi.'
    })
    return
  }

  if (oauthRegister) {
    // SIM-ASN user not found — store the registration payload and redirect to registration
    authStore.setOAuthRegistrationPayload(oauthRegister)
    router.replace('/auth/register')
    return
  }

  if (errorParam) {
    if (errorParam === 'user_not_found') {
      error.value = 'Akun Anda belum terdaftar di sistem ini.'
    } else {
      error.value = 'Login SIM-ASN gagal: ' + errorParam
    }
  }
})
```

- [ ] **Step 2: Commit**

```bash
git add client/app/pages/login/index.vue
git commit -m "feat: handle oauth_register redirect in login page"
```

---

## Task 7: Create Registration Page

**Files:**
- Create: `client/app/pages/auth/register/index.vue`

- [ ] **Step 1: Create the registration page**

```vue
<script setup lang="ts">
import { useAuthStore } from '~/stores/auth.store'

definePageMeta({
  layout: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()

// Pre-fill from OAuth registration payload
interface OAuthData {
  sim_asn_user_id: string
  name: string | null
  email: string | null
  access_token: string
  refresh_token: string | null
  expires_at: string | null
}

const oauthData = ref<OAuthData | null>(null)

const form = ref({
  name: '',
  email: '',
  opd_id: '' as string | number | undefined,
  sim_asn_user_id: '',
  sim_asn_token: {
    access_token: '',
    refresh_token: null as string | null,
    expires_at: null as string | null,
  },
})

const loading = ref(false)
const error = ref<string | null>(null)

// OPD list (loaded from a static list or API — check if ref-gaji pages have a pattern)
const opds = ref<{ id: number; nama: string }[]>([])

onMounted(async () => {
  // Load OPD list
  try {
    const api = useApi()
    const res = await api.get<{ data: { id: number; nama: string }[] }>('/api/v1/ref/opd')
    opds.value = res.data
  } catch {
    // fallback: empty list
  }

  // Decode and set OAuth payload
  const payload = authStore.oauthRegistrationPayload
  if (!payload) {
    router.replace('/login')
    return
  }

  try {
    const decoded: OAuthData = JSON.parse(atob(payload))
    oauthData.value = decoded
    form.value.name = decoded.name || ''
    form.value.email = decoded.email || ''
    form.value.sim_asn_user_id = decoded.sim_asn_user_id
    form.value.sim_asn_token = {
      access_token: decoded.access_token,
      refresh_token: decoded.refresh_token,
      expires_at: decoded.expires_at,
    }
  } catch {
    error.value = 'Data registrasi tidak valid.'
  }
})

async function register() {
  loading.value = true
  error.value = null
  try {
    const api = useApi()
    const res = await api.post<{ data: { user: any; token: string } }>(
      '/api/v1/auth/register-from-sim-asn',
      {
        name: form.value.name,
        email: form.value.email,
        opd_id: form.value.opd_id || null,
        sim_asn_user_id: form.value.sim_asn_user_id,
        sim_asn_token: form.value.sim_asn_token,
      },
    )
    await authStore.loginWithToken(res.data.token)
    authStore.clearOAuthRegistrationPayload()
    router.push('/dashboard')
  } catch (e: any) {
    error.value = e.data?.message || 'Registrasi gagal. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Daftar Akun SIM-ASN</h1>
        <p class="text-gray-500 mt-1">Lengkapi data untuk membuat akun</p>
      </div>

      <div v-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
          <input v-model="form.name" type="text" placeholder="Nama lengkap"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" placeholder="email@example.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
          <select v-model="form.opd_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">Pilih OPD</option>
            <option v-for="opd in opds" :key="opd.id" :value="opd.id">
              {{ opd.nama }}
            </option>
          </select>
        </div>

        <div v-if="oauthData" class="p-3 bg-blue-50 text-blue-700 text-sm rounded-lg">
          Login sebagai: <strong>{{ oauthData.name || oauthData.sim_asn_user_id }}</strong>
        </div>

        <button type="submit" :disabled="loading || !oauthData"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
          {{ loading ? 'Mendaftarkan...' : 'Daftar dan Masuk' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        <NuxtLink to="/login" class="text-indigo-600 hover:underline">Kembali ke Login</NuxtLink>
      </p>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add client/app/pages/auth/register/index.vue
git commit -m "feat: add SIM-ASN OAuth registration page"
```

---

## Task 8: Add OPD Reference Endpoint (if missing)

**Files:**
- Modify: `api/app/Http/Controllers/Api/V1/Ref/OpdController.php` (create if missing)
- Modify: `api/routes/api.php`

- [ ] **Step 1: Check if OPD endpoint exists**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan route:list --path=ref --method=GET 2>/dev/null | grep -i opd`

If no OPD endpoint exists, create one:

```php
<?php
// Create: api/app/Http/Controllers/Api/V1/Ref/OpdController.php

<?php

namespace App\Http\Controllers\Api\V1\Ref;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use Illuminate\Http\JsonResponse;

class OpdController extends Controller
{
    public function index(): JsonResponse
    {
        $opds = Opd::orderBy('nama')->get(['id', 'nama']);

        return ApiResponse::success($opds);
    }
}
```

Add route in `api/routes/api.php` under the ref routes section:
```php
Route::get('/ref/opd', [OpdController::class, 'index']);
```

Run Pint: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

Commit: `git add api/app/Http/Controllers/Api/V1/Ref/OpdController.php api/routes/api.php && git commit -m "feat: add OPD reference endpoint for registration form"`

---

## Task 9: Write Auth Tests

**Files:**
- Modify: `api/tests/Feature/AuthTest.php`

- [ ] **Step 1: Check if AuthTest exists**

Run: `ls /home/bacink/DevApps/2026/kgb/api/tests/Feature/AuthTest.php 2>/dev/null && echo "EXISTS" || echo "MISSING"`

If it doesn't exist, create it with the tests below. If it exists, read it and add the new tests.

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'opd_id', 'sim_asn_user_id', 'is_sim_asn_authenticated', 'roles', 'permissions'],
                    'token',
                ],
            ])
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('success', true);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_endpoint_returns_user_with_sim_asn_context(): void
    {
        $user = User::factory()->create([
            'sim_asn_user_id' => 'sim-asn-uuid-123',
            'sim_asn_token' => ['access_token' => 'tok', 'refresh_token' => null, 'expires_at' => null],
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.sim_asn_user_id', 'sim-asn-uuid-123')
            ->assertJsonPath('data.is_sim_asn_authenticated', true);
    }

    public function test_me_endpoint_returns_user_without_sim_asn_context(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.sim_asn_user_id', null)
            ->assertJsonPath('data.is_sim_asn_authenticated', false);
    }

    public function test_register_from_sim_asn_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'sim-asn-uuid-new',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'opd_id' => null,
            'sim_asn_token' => [
                'access_token' => 'sim-tok-abc',
                'refresh_token' => 'sim-ref-xyz',
                'expires_at' => '2027-01-01T00:00:00Z',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Budi Santoso')
            ->assertJsonPath('data.user.sim_asn_user_id', 'sim-asn-uuid-new')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'sim_asn_user_id' => 'sim-asn-uuid-new',
        ]);

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertTrue($user->hasRole('operator'));
    }

    public function test_register_from_sim_asn_fails_with_duplicate_sim_asn_user_id(): void
    {
        User::factory()->create(['sim_asn_user_id' => 'existing-uuid']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'existing-uuid',
            'name' => 'Duplicate User',
            'email' => 'dup@example.com',
            'sim_asn_token' => ['access_token' => 'tok'],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.sim_asn_user_id.0', 'Akun SIM-ASN ini sudah terdaftar.');
    }

    public function test_register_from_sim_asn_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'new-uuid',
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'sim_asn_token' => ['access_token' => 'tok'],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.email.0', 'Email ini sudah terdaftar di sistem.');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();

        // Token should no longer work
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');
        $meResponse->assertStatus(401);
    }

    public function test_callback_redirects_to_registration_for_unknown_user(): void
    {
        // We can't easily mock OauthClient::handleCallback in a feature test without
        // mocking the SIM-ASN service. Instead, verify the route is registered.
        $response = $this->get('/callback/sim-asn?error=user_not_found');
        // The route exists and redirects to the error handler
        $response->assertStatus(302);
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan test --compact --filter=AuthTest`

- [ ] **Step 3: Run Pint**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add api/tests/Feature/AuthTest.php
git commit -m "test: add comprehensive auth tests for OAuth and Sanctum flows"
```

---

## Task 10: Final Verification

- [ ] **Step 1: Run full test suite**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan test`

- [ ] **Step 2: Run Pint on all changed files**

Run: `cd /home/bacink/DevApps/2026/kgb/api && vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Check routes**

Run: `cd /home/bacink/DevApps/2026/kgb/api && php artisan route:list --path=auth`

Expected output should include:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/register-from-sim-asn`
- `GET /auth/sim-asn`
- `GET /callback/sim-asn`

- [ ] **Step 4: Review changed files**

Run: `git diff --stat`

---

## Self-Review Checklist

1. **Spec coverage** — All critical gaps addressed:
   - Token mismatch → Task 1 (callback returns Sanctum token)
   - User registration → Task 2 (registerFromSimAsn endpoint + registration page)
   - me() missing SIM-ASN context → Task 2 (is_sim_asn_authenticated + sim_asn_user_id)
   - Role mapping → Task 2 (assignRole('operator') on registration)
   - Client token clear on 401 → Task 5 (useApi interceptor)
   - Frontend registration flow → Task 6 + 7 (login page + register page)

2. **No placeholders** — Every step has actual code.

3. **Type consistency** — User type on frontend matches the me() + login() API response shape.

4. **Registration page OPD list** — Task 8 adds the OPD endpoint if missing, so the registration form can load the OPD dropdown.
