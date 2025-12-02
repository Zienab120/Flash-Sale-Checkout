<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['hold_id', 'product_id', 'quantity', 'status', 'total_price'];

    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function payment()
    {
        return $this->hasOne(WebhookIdempotencyKey::class)
            ->whereNull('processed_at');
    }
}
