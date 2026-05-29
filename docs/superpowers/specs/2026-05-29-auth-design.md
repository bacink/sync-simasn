# Design: Dual-Mode Authentication (Credentials + SIM-ASN OAuth)

**Date:** 2026-05-29
**Author:** Claude Code
**Status:** Approved

---

## Overview

Add SIM-ASN OAuth as an alternative login method alongside the existing email/password credentials login. Users choose their preferred method. Both result in a local Sanctum token.

## Architecture

```
Browser                    KGB Backend                     SIM-ASN
    |                           |                             |
    | click "Masuk SIM-ASN"     |                             |
    |----------------------->   |                             |
    |   GET /auth/sim-asn       |                             |
    |<--------------------------                             |
    |   302 → SIM-ASN authorize |                             |
    |------------------------------------------------------------->|
    |                           |                             |
    |<-------------------- authorize + login -----------------|
    |<-------------------------------------------------------------|
    |                           |                             |
    |   GET /callback/sim-asn?code=xxx&state=login              |
    |------------------------------------------------------------->|
    |                           |                             |
    |                    exchange code                         |
    |                    fetch SIM-ASN user                    |
    |                    find User by sim_asn_user_id         |
    |                    save sim_asn_token                   |
    |                    create Sanctum token                 |
    |                           |                             |
    |<-------------------- 302 → /login?token=xxx ------------|
    |                           |                             |
    | store token, navigate to /dashboard                      |
```

---

## Decisions

| Decision | Choice |
|---|---|
| Login methods | Both credential and SIM-ASN available to all roles |
| User matching | Link by `sim_asn_user_id` (pre-provisioned only) |
| User provisioning | Pre-provisioned by admin — no auto-create on first login |
| Token strategy | Exchange SIM-ASN code for Sanctum token; store both locally |
| OAuth flow | Backend initiates, backend callback, redirect with token (Approach 1) |
| Callback page | Transparent redirect (backend → `/login?token=xxx`, login page handles silently) |

---

## 1. Database Schema

### Migration: Add SIM-ASN fields to `users` table

```php
$table->uuid('sim_asn_user_id')->nullable()->unique()->after('password');
$table->json('sim_asn_token')->nullable()->after('sim_asn_user_id');
```

- `sim_asn_user_id`: UUID from SIM-ASN. Used to match SIM-ASN user → local User on callback. Nullable — existing local users don't need it.
- `sim_asn_token`: JSON blob storing the SIM-ASN `AccessToken` object (access_token, refresh_token, expiry). Updated on every token refresh.
- Admin provisions users by setting their `sim_asn_user_id` before SIM-ASN login works for them.

### Updated User Model

Add to `$fillable`:
```php
'sim_asn_user_id',
'sim_asn_token',
```

Add to `$casts`:
```php
'sim_asn_token' => 'array',
```

---

## 2. Backend: Route Additions

### `routes/web.php` — OAuth web routes

```php
// Initiate SIM-ASN OAuth flow (redirects browser to SIM-ASN)
Route::get('/auth/sim-asn', [SimAsnCallbackController::class, 'initiate'])
    ->name('auth.sim-asn');

// SIM-ASN redirects here after user authorizes
Route::get('/callback/sim-asn', [SimAsnCallbackController::class, 'callback'])
    ->name('callback.sim-asn');
```

### `routes/api.php` — No new API routes needed

Existing `/api/v1/auth/*` routes serve both credential and SIM-ASN logins.

---

## 3. Backend: Callback Controller

`app/Http/Controllers/SimAsnCallbackController.php`

**`initiate()`** — simply redirects to SIM-ASN OAuth authorize URL:
```php
return OauthClient::requestCode('login');
```

**`callback(Request $request)`** — handles the redirect from SIM-ASN:

```php
return OauthClient::handleCallback($request, function (UserSimASN $simAsnUser, AccessToken $token) {
    $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

    if (!$user) {
        return null; // triggers error redirect below
    }

    $user->sim_asn_token = $token;
    $user->save();

    $sanctumToken = $user->createToken('sim-asn-token')->plainTextToken;

    return redirect()->away(config('app.frontend_url') . "/login?token={$sanctumToken}");
});
```

If user not found, `handleCallback` redirects to `/login?error=user_not_found`.

---

## 4. Backend: Token Refresh Handler

In `app/Providers/AppServiceProvider.php` → `boot()`:

```php
use SIM_ASN\Laravel\Facades\UserClient;
use SIM_ASN\Models\AccessToken;

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
```

This ensures SIM-ASN tokens stay fresh automatically — every authenticated request triggers a refresh check if needed.

---

## 5. Frontend: Login Page

**`client/app/pages/login/index.vue`**

Layout: Two equal-width buttons stacked above the form, or side-by-side depending on screen width.

```
┌──────────────────────────────────────────────┐
│           Sistem KGB & PMK                   │
│         Silakan masuk untuk melanjutkan       │
│                                              │
│  ┌─────────────────┐  ┌────────────────────┐  │
│  │ Email & Password │  │   Masuk SIM-ASN    │  │
│  └─────────────────┘  └────────────────────┘  │
│                                              │
│  ─────────── atau ───────────                │
│                                              │
│  [email    ]                                 │
│  [password ]                                 │
│  [    Masuk    ]                             │
└──────────────────────────────────────────────┘
```

**"Masuk SIM-ASN" button behavior:**
- `<a href="/auth/sim-asn">` — standard link that triggers the backend redirect
- Styled consistently with the existing credential form button

**Token-from-URL handling** (in `onMounted`):
1. Check `?token=xxx` in URL → call `authStore.loginWithToken(token)`
2. Check `?error=xxx` in URL → display error message
3. Clear query params from URL after processing

---

## 6. Frontend: AuthStore

**`client/app/stores/auth.store.ts`**

New action `loginWithToken(token: string)`:
```ts
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

Existing `login(credentials)` and `logout()` remain unchanged.

---

## 7. File Change Summary

| File | Change |
|---|---|
| `api/database/migrations/` | New migration for `sim_asn_user_id` + `sim_asn_token` columns |
| `api/app/Models/User.php` | Add fields to `$fillable` and `$casts` |
| `api/app/Providers/AppServiceProvider.php` | Add `UserClient::setAccessToken()` + `onRefreshToken()` in `boot()` |
| `api/app/Http/Controllers/SimAsnCallbackController.php` | **New** — `initiate()` + `callback()` |
| `api/routes/web.php` | Add `/auth/sim-asn` and `/callback/sim-asn` routes |
| `client/app/pages/login/index.vue` | Add SIM-ASN button + token-from-URL handler |
| `client/app/stores/auth.store.ts` | Add `loginWithToken()` action |

---

## 8. Error Handling

| Scenario | Behavior |
|---|---|
| User clicks SIM-ASN but account not pre-provisioned | Redirect to `/login?error=user_not_found` → show "Akun SIM-ASN belum terdaftar di sistem ini" |
| SIM-ASN returns OAuth error (denied, invalid) | Redirect to `/login?error=oauth_denied` → show appropriate message |
| Sanctum token exchange fails | Handled by `handleCallback`, redirects with error |
| Token refresh fails | `UserClient::onRefreshToken` handles; if token expires, user re-authenticates |

---

## 9. Security Considerations

- Sanctum token in URL query param is short-lived — frontend immediately stores it in localStorage/cookie and clears the URL param
- `sim_asn_token` JSON is stored server-side only; never sent to frontend
- SIM-ASN `client_secret` never leaves the backend
- `state` parameter in OAuth request prevents CSRF (handled by `OauthClient::requestCode`)
- Users pre-provisioned by admin prevents unauthorized account creation