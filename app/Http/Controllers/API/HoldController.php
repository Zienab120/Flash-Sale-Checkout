<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hold\HoldResource;
use App\Services\HoldService;

class HoldController extends Controller
{
    public function __construct(private HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    public function store($productId,  $quantity)
    {
        try {
            $data = $this->holdService->createHold($productId, $quantity);
            $hold = new HoldResource($data);
            return response()->json($hold, 201);
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
