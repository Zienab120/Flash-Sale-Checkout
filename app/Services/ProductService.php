<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductService
{
    protected $cache_prefix = 'product:';
    protected $cache_timeout = 60;

    public function getProduct($id)
    {
        return Cache::remember($this->cache_prefix . $id, $this->cache_timeout, function () use ($id) {
            return Product::find($id);
        });
    }

    public function forgetProductCache($id)
    {
        Cache::forget($this->cache_prefix . $id);
        Log::channel('product')->info('Cleared cache for product ' . $id);
    }
}
