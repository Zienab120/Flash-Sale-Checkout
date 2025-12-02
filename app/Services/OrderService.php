<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Order;
use App\Services\HoldService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected $holdService;
    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    public function createOrder($holdId)
    {
        $this->holdService->validateHold($holdId);

        return DB::transaction(function () use ($holdId) {

            $hold = Hold::with('product:id,price')
                ->where('id', $holdId)
                ->lockForUpdate()
                ->first();

            $order = Order::create([
                'hold_id' => $holdId,
                'product_id' => $hold->product_id,
                'total_price' => $hold->product->price * $hold->quantity,
            ]);

            $hold->update(['status' => 'expired']);

            Log::channel('order')->info(
                "Order created successfully.",
                [
                    'order_id'    => $order->id,
                    'hold_id'     => $hold->id,
                    'product_id'  => $hold->product_id,
                    'quantity'    => $hold->quantity,
                    'total_price' => $order->total_price,
                ]
            );
            return $order;
        });
    }
}
