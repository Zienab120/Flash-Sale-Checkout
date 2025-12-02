<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoldService
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function createHold($productId, $quantity)
    {
        $maxAttempts = 3;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return $this->createHoldTransaction($productId, $quantity);
            } catch (\Throwable $th) {

                if ($this->isDeadlock($th)) {
                    $waitMs = min(100 * (2 ** $attempt), 1000);

                    Log::channel('holding')->warning("Deadlock detected, retrying", [
                        'attempt' => $attempt + 1,
                        'product_id' => $productId,
                        'wait_ms' => $waitMs,
                    ]);

                    usleep($waitMs * 1000);
                    continue;
                }

                throw $th;
            }
        }
        throw new \RuntimeException('Failed to create hold after max retries');
    }

    public function createHoldTransaction($productId, $quantity)
    {
        return DB::transaction(function () use ($productId, $quantity) {
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception("Product not found");
            }

            if ($product->stock < $quantity) {
                throw new \Exception("Insufficient stock. Only {$product->stock} items available.", 422);
            }

            $product->decrement('stock', $quantity);

            $hold = Hold::create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes(2),
            ]);

            $this->productService->forgetProductCache($productId);

            Log::channel('holding')->info(
                "Hold created successfully.",
                [
                    'hold_id'    => $hold->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'expires_at' => $hold->expires_at,
                    'message'    => "A hold has been created for product {$productId} with quantity {$quantity}."
                ]
            );

            return $hold;
        });
    }

    public function validateHold($holdId)
    {
        $hold = Hold::find($holdId);

        if (!$hold) {
            throw new \Exception('Hold not found');
        }

        if ($hold->status !== 'active') {
            throw new \Exception("Hold is {$hold->status}");
        }

        if ($hold->isExpired()) {
            throw new \Exception('Hold has expired');
        }

        if ($hold->order()->exists()) {
            throw new \Exception('Hold already converted to order');
        }
    }

    public function isDeadlock(\Throwable $th)
    {
        return $th instanceof \Illuminate\Database\QueryException &&
            ($th->getCode() === '40001' || $th->getCode() === '1213');
    }
}
