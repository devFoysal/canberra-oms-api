<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{
    User,
    Customer
};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500, 5000); // between 500 and 5000
        $tax = $this->faker->randomFloat(2, 0, 500);
        $total = $subtotal + $tax;

        $statusOptions = ['pending'];
        $invoiceStatusOptions = ['pending', 'generated'];
        $paymentStatusOptions = ['pending'];

        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
            'sales_rep_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'status' => $this->faker->randomElement($statusOptions),
            'invoice_status' => $this->faker->randomElement($invoiceStatusOptions),
            'payment_status' => $this->faker->randomElement($paymentStatusOptions),
        ];
    }
}
