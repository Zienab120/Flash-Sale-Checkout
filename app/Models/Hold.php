<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'status',
        'expires_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
