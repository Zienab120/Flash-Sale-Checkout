<?php

namespace App\Jobs;

use App\Models\Hold;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireHolds implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(ProductService $productService): void
    {
        DB::transaction(function () use ($productService) {
            $holds = Hold::where('status', 'active')
                ->where('expires_at', '<=', now())
                ->orderBy('id')
                ->limit(100)
                ->lockForUpdate()
                ->get();

            if ($holds->isEmpty()) {
                return;
            }

            $productUpdates = [];
            foreach ($holds as $hold) {

                if (!isset($productUpdates[$hold->product_id])) {
                    $productUpdates[$hold->product_id] = 0;
                }
                $productUpdates[$hold->product_id] += $hold->quantity;
            }

            foreach ($productUpdates as $productId => $quantity) {
                Product::where('id', $productId)
                    ->increment('stock', $quantity);

                $productService->forgetProductCache($productId);
            }

            Hold::whereIn('id', $holds->pluck('id'))
                ->update(['status' => 'expired']);

            Log::channel('holding')
                ->info('Holds expired', [
                    'count' => $holds->count(),
                    'products_affected' => count($productUpdates),
                ]);
        });
    }
}
