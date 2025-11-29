<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Wireless Bluetooth Headphones',
            'description' => 'High-quality over-ear wireless headphones with noise cancellation and 20-hour battery life.',
            'price' => 599.99,
            'stock' => 40,
        ];
    }
}
