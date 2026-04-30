<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pmk\StorePmkRequest;
use App\Http\Resources\PmkResource;
use App\Services\Pmk\PmkService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PmkController extends Controller
{
    public function __construct(
        protected PmkService $pmkService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->pmkService->getList($request->all());

        return PmkResource::collection($items);
    }

    public function store(StorePmkRequest $request): PmkResource
    {
        $pmk = $this->pmkService->store($request->validated());

        return new PmkResource($pmk);
    }

    public function show(int $id): PmkResource
    {
        $pmk = $this->pmkService->findOrFail($id);

        return new PmkResource($pmk);
    }

    public function destroy(int $id): \Illuminate\Http\Response
    {
        $this->pmkService->delete($id);

        return response()->noContent();
    }
}
