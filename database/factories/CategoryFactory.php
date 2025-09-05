<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         $name = $this->faker->unique()->words(2, true); // e.g., "Home Decor"

        return [
            'name' => ucfirst($name),
            'description' => $this->faker->sentence(),
            'image' => $this->faker->imageUrl(400, 300, 'business', true), // or null sometimes
            'slug' => Str::slug($name),
            'category_id' => null, // we can assign a parent later if needed
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
