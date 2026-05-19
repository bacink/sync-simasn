<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ], $meta),
        ];

        return response()->json($response, 200);
    }

    public static function created(mixed $data = null, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ], $meta),
        ];

        return response()->json($response, 201);
    }

    public static function paginated(LengthAwarePaginator $paginator, array $items = [], array $additional = []): JsonResponse
    {
        $data = $items;
        if (empty($items)) {
            $data = $paginator->items();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ], $additional),
        ], 200);
    }

    public static function error(
        string $code,
        string $message,
        array $details = [],
        int $httpStatus = 400,
        ?string $requestId = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
                'request_id' => $requestId ?? uniqid('req_'),
            ],
        ];

        return response()->json($response, $httpStatus);
    }
}