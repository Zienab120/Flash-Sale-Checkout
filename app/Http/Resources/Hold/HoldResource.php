<?php

namespace App\Http\Resources\Hold;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HoldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'hold_id' => $this->id,
            'expires_at' => $this->expires_at->format('Y-m-d H:i:s'),
        ];
    }
}
