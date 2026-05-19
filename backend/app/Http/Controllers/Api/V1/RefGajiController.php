<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\RefGaji\StoreRefGajiRequest;
use App\Http\Requests\RefGaji\UpdateRefGajiRequest;
use App\Models\RefGajiAsn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefGajiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RefGajiAsn::query();

        if ($request->boolean('is_active', true)) {
            $query->active();
        }

        if ($request->has('golongan')) {
            $query->where('golongan', $request->string('golongan'));
        }

        $sortField = $request->string('sort', 'golongan');
        $sortDir = $request->string('dir', 'asc');
        $query->orderBy('golongan', $sortDir->value())->orderBy('masa_kerja_tahun', $sortDir->value());

        $perPage = $request->integer('per_page', 15);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn ($r) => [
            'id' => $r->id,
            'golongan' => $r->golongan,
            'masa_kerja_tahun' => $r->masa_kerja_tahun,
            'gaji' => (int) $r->gaji,
            'peraturan' => $r->peraturan,
            'is_active' => $r->is_active,
            'created_at' => $r->created_at->toIso8601String(),
        ])->toArray();

        return ApiResponse::paginated($paginator, $items);
    }

    public function store(StoreRefGajiRequest $request): JsonResponse
    {
        $refGaji = RefGajiAsn::create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return ApiResponse::created([
            'id' => $refGaji->id,
            'golongan' => $refGaji->golongan,
            'masa_kerja_tahun' => $refGaji->masa_kerja_tahun,
            'gaji' => (int) $refGaji->gaji,
            'peraturan' => $refGaji->peraturan,
            'is_active' => $refGaji->is_active,
            'created_at' => $refGaji->created_at->toIso8601String(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $refGaji = RefGajiAsn::findOrFail($id);

        return ApiResponse::success([
            'id' => $refGaji->id,
            'golongan' => $refGaji->golongan,
            'masa_kerja_tahun' => $refGaji->masa_kerja_tahun,
            'gaji' => (int) $refGaji->gaji,
            'peraturan' => $refGaji->peraturan,
            'is_active' => $refGaji->is_active,
            'created_at' => $refGaji->created_at->toIso8601String(),
            'updated_at' => $refGaji->updated_at->toIso8601String(),
        ]);
    }

    public function update(UpdateRefGajiRequest $request, int $id): JsonResponse
    {
        $refGaji = RefGajiAsn::findOrFail($id);
        $refGaji->update($request->validated());

        return ApiResponse::success([
            'id' => $refGaji->id,
            'golongan' => $refGaji->golongan,
            'masa_kerja_tahun' => $refGaji->masa_kerja_tahun,
            'gaji' => (int) $refGaji->gaji,
            'peraturan' => $refGaji->peraturan,
            'is_active' => $refGaji->is_active,
            'updated_at' => $refGaji->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $refGaji = RefGajiAsn::findOrFail($id);
        $refGaji->update(['is_active' => false]);

        return ApiResponse::success(null, ['message' => 'Data referensi gaji berhasil dinonaktifkan']);
    }
}