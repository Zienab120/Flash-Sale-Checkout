<?php

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_parallel_holds_prevent_overselling()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100,
            'stock' => 10,
        ]);

        $successCount = 0;
        $failureCount = 0;

        $promises = [];
        for ($i = 0; $i < 20; $i++) {
            $promises[] = function () use ($product, &$successCount, &$failureCount) {
                try {
                    $response = $this->postJson("/api/holds/{$product->id}/1");

                    if ($response->status() === 201) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                }
            };
        }

        foreach ($promises as $promise) {
            $promise();
        }

        $product->refresh();

        $this->assertEquals(10, $successCount, 'Exactly 10 holds should succeed');
        $this->assertEquals(10, $failureCount, 'Exactly 10 holds should fail');
        $this->assertEquals(0, $product->stock, 'Available stock should be 0');
        $this->assertEquals(10, Hold::where('status', 'active')->count(), 'Should have 10 active holds');


        Log::channel('testing')
            ->info('Concurrency Test Results', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'final_stock' => $product->stock,
                'active_holds' => Hold::where('status', 'active')->count(),
            ]);
    }

    public function test_boundary_exact_stock_allocation()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100,
            'stock' => 5,
        ]);

        $response1 = $this->postJson("/api/holds/{$product->id}/3");
        $response1->assertStatus(201);

        $response2 = $this->postJson("/api/holds/{$product->id}/2");
        $response2->assertStatus(201);

        $response3 = $this->postJson("/api/holds/{$product->id}/1");
        $response3->assertStatus(422);

        $product->refresh();
        $this->assertEquals(0, $product->stock);
    }

    public function test_multiple_products_no_deadlock()
    {
        $product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'description' => 'Test Product Description',
            'price' => 100,
            'stock' => 50
        ]);
        $product2 = Product::factory()->create([
            'name' => 'Test Product 2',
            'description' => 'Test Product Description',
            'price' => 100,
            'stock' => 50
        ]);

        $iterations = 30;
        $errors = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $productId = ($i % 2 === 0) ? $product1->id : $product2->id;

                $response = $this->postJson("/api/holds/{$productId}/1");

                if ($response->status() !== 201 && $response->status() !== 422) {
                    $errors[] = "Unexpected status: {$response->status()}";
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->assertEmpty($errors, 'No deadlocks or errors should occur: ' . implode(', ', $errors));

        $product1->refresh();
        $product2->refresh();

        $totalHolds = Hold::whereIn('product_id', [$product1->id, $product2->id])
            ->where('status', 'active')
            ->sum('quantity');

        $totalAvailable = $product1->stock + $product2->stock;

        $this->assertEquals(100, $totalHolds + $totalAvailable, 'Total inventory should be conserved');
    }
}
