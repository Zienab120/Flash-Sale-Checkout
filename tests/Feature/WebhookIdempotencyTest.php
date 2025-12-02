<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookIdempotencyKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_idempotency_same_key_repeated()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100,
            'stock' => 100
        ]);

        $holdResponse = $this->postJson("/api/holds/{$product->id}/10");
        $holdId = $holdResponse->json('hold_id');

        $orderResponse = $this->postJson("/api/orders/{$holdId}");
        $orderId = $orderResponse->json('order_id');

        $idempotencyKey = 'test-key-123';

        $response1 = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => 'success',
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['status' => 'success']);


        $order = Order::find($orderId);
        $this->assertEquals('paid', $order->status);

        $response2 = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => 'success',
        ]);
        $response2->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $keyCount = WebhookIdempotencyKey::where('key', $idempotencyKey)->count();
        $this->assertEquals(1, $keyCount);

        Log::channel('testing')
            ->info('Idempotency Test Results', [
                'idempotency_key' => $idempotencyKey,
                'order_id' => $orderId,
                'final_status' => $order->status,
                'key_records' => $keyCount,
            ]);
    }
}
