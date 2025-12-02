<?php

use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\HoldController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/holds/{product_id}/{quantity}', [HoldController::class, 'store']);
Route::post('/orders/{hold_id}', [OrderController::class, 'store']);
Route::post('/payments/webhook', [WebhookController::class, 'handlePaymentWebhook']);
