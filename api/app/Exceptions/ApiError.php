<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiError extends Exception
{
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        public readonly array $details = [],
        ?string $message = null,
        int $httpStatus = null,
        ?Throwable $previous = null
    ) {
        $httpStatus ??= $errorCode->httpStatus();
        $message ??= $errorCode->defaultMessage();
        parent::__construct($message, $httpStatus, $previous);
    }

    public static function notFound(string $code, string $message, array $details = []): static
    {
        $errorCode = ApiErrorCode::tryFrom($code);
        if (!$errorCode) {
            throw new \InvalidArgumentException("Unknown error code: {$code}");
        }
        return new static($errorCode, $details, $message, 404);
    }

    public static function invalidTransition(string $message = 'Transisi status tidak valid'): static
    {
        return new static(ApiErrorCode::KGB_INVALID_TRANSITION, [], $message, 409);
    }

    public static function refGajiNotFound(string $golongan, int $masaKerja): static
    {
        $code = ApiErrorCode::REF_GAJI_NOT_FOUND;
        $message = "Referensi gaji tidak ditemukan untuk golongan {$golongan} masa kerja {$masaKerja} tahun";
        return new static($code, ['golongan' => $golongan, 'masa_kerja_tahun' => $masaKerja], $message, 422);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::error(
            code: $this->errorCode->value,
            message: $this->getMessage(),
            details: $this->details,
            httpStatus: $this->getCode()
        );
    }
}