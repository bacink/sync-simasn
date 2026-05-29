<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterFromSimAsnRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return ApiResponse::error(
                'AUTH_INVALID',
                'Email atau password salah',
                [],
                401
            );
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'opd_id' => $user->opd_id,
                'sim_asn_user_id' => $user->sim_asn_user_id,
                'is_sim_asn_authenticated' => ! empty($user->sim_asn_token),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, ['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'opd_id' => $user->opd_id,
            'sim_asn_user_id' => $user->sim_asn_user_id,
            'is_sim_asn_authenticated' => ! empty($user->sim_asn_token),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

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
}
