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
