<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterFromSimAsnRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->authService->login($request->only('email', 'password'));
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->authService->logout();
    }

    public function me(Request $request): JsonResponse
    {
        return $this->authService->me();
    }

    public function registerFromSimAsn(RegisterFromSimAsnRequest $request): JsonResponse
    {
        return $this->authService->registerFromSimAsn($request->validated());
    }
}
