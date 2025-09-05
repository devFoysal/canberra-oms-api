<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake()->words(3, true); // e.g. "Smart LED TV"

        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'name' => ucfirst($name),
            'thumbnail' => fake()->optional()->imageUrl(300, 300, 'products', true),
            'cover_image' => fake()->optional()->imageUrl(800, 400, 'products', true),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'purchase_price' => fake()->randomFloat(2, 50, 500), // 50–500
            'sale_price' => fake()->randomFloat(2, 100, 1000),  // 100–1000
            'stock' => fake()->numberBetween(1, 100),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
