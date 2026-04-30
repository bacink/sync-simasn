<?php

namespace App\Http\Controllers;

use App\DTOs\KgbApprovalDTO;
use App\DTOs\KgbGenerateDTO;
use App\DTOs\KgbSubmitDTO;
use App\Http\Requests\Kgb\GenerateKgbRequest;
use App\Http\Requests\Kgb\KgbRejectRequest;
use App\Http\Requests\Kgb\KgbSubmitRequest;
use App\Http\Requests\Kgb\KgbVerifyRequest;
use App\Http\Resources\KgbResource;
use App\Models\RiwayatKgb;
use App\Services\Kgb\KgbService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KgbController extends ApiController
{
    public function __construct(
        protected KgbService $kgbService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->kgbService->getList($request->all());

        return KgbResource::collection($items);
    }

    public function generate(GenerateKgbRequest $request): KgbResource
    {
        $dto = KgbGenerateDTO::fromRequest($request->validated());
        $kgb = $this->kgbService->generateDraft($dto);

        return new KgbResource($kgb);
    }

    public function show(RiwayatKgb $kgb): KgbResource
    {
        return new KgbResource($kgb->load(['snapshot', 'calculation', 'pmk', 'approvals.user']));
    }

    public function submit(KgbSubmitRequest $request, RiwayatKgb $kgb): KgbResource
    {
        $dto = KgbSubmitDTO::fromRequest($request->validated());
        return new KgbResource($this->kgbService->submit($kgb, $dto));
    }

    public function verify(KgbVerifyRequest $request, RiwayatKgb $kgb): KgbResource
    {
        $dto = KgbApprovalDTO::fromRequest($request->validated());
        return new KgbResource($this->kgbService->verify($kgb, $dto));
    }

    public function approve(RiwayatKgb $kgb): KgbResource
    {
        return new KgbResource($this->kgbService->approve($kgb));
    }

    public function reject(KgbRejectRequest $request, RiwayatKgb $kgb): KgbResource
    {
        $dto = KgbApprovalDTO::fromRequest($request->validated());
        return new KgbResource($this->kgbService->reject($kgb, $dto));
    }
}
