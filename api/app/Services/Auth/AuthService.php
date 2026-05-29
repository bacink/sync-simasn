<?php

namespace App\Services\Auth;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AuthService
{
    /**
     * Authenticate with email + password, return user + Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    public function login(array $credentials): JsonResponse
    {
        if (! Auth::attempt($credentials)) {
            return ApiResponse::error(
                'AUTH_INVALID',
                'Email atau password salah',
                [],
                401
            );
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponse::success([
            'user' => $this->normalizeUser($user),
            'token' => $token,
        ]);
    }

    /**
     * Revoke the current Sanctum token (used for logout).
     */
    public function logout(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return ApiResponse::success(null, ['message' => 'Already logged out']);
        }

        try {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        } catch (\Throwable) {
            // TransientToken has no delete(); ignore
        }

        return ApiResponse::success(null, ['message' => 'Logged out successfully']);
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        return ApiResponse::success($this->normalizeUser($user));
    }

    /**
     * Register a new user from SIM-ASN OAuth data.
     *
     * @param  array{sim_asn_user_id: string, sim_asn_token: array, name: string, email: string, opd_id?: int|null}  $data
     */
    public function registerFromSimAsn(array $data): JsonResponse
    {
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

            // Ensure 'operator' role exists for the sanctum guard before assigning
            if (! Role::where('name', 'operator')->where('guard_name', 'sanctum')->exists()) {
                Role::create(['name' => 'operator', 'guard_name' => 'sanctum']);
            }
            $user->assignRole('operator');

            return $user;
        });

        $token = $user->createToken('sim-asn-token')->plainTextToken;

        return ApiResponse::created([
            'user' => $this->normalizeUser($user),
            'token' => $token,
        ]);
    }

    /**
     * Return a normalized user array (matching login + me response shape).
     *
     * @return array<string, mixed>
     */
    private function normalizeUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'opd_id' => $user->opd_id,
            'sim_asn_user_id' => $user->sim_asn_user_id,
            'is_sim_asn_authenticated' => ! empty($user->sim_asn_token),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
