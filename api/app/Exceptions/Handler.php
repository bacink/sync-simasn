<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->renderable(function (ApiError $e) {
            return $e->render();
        });

        $this->renderable(function (AuthenticationException $e) {
            return ApiResponse::error(
                code: 'AUTH_TOKEN_INVALID',
                message: 'Unauthenticated',
                httpStatus: 401
            );
        });

        $this->renderable(function (ValidationException $e) {
            return ApiResponse::error(
                code: 'VALIDATION_ERROR',
                message: 'Validasi request gagal',
                details: ['errors' => $e->errors()],
                httpStatus: 422
            );
        });

        $this->renderable(function (NotFoundHttpException $e) {
            return ApiResponse::error(
                code: ApiErrorCode::KGB_NOT_FOUND->value,
                message: 'Resource tidak ditemukan',
                httpStatus: 404
            );
        });

        $this->renderable(function (Throwable $e) {
            if (config('app.debug')) {
                return;
            }

            return ApiResponse::error(
                code: 'SYSTEM_ERROR',
                message: 'Terjadi kesalahan sistem',
                httpStatus: 500
            );
        });
    }
}
