<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{
    User,
    Customer,
    Category,
    Product,
    Order,
    OrderItem,
};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Create your own account
        $user = User::factory()->myAccount()->create()->first();

        // // Create 10 fake users
        // User::factory(10)->create();

        // Create 10 customers linked to your account
        Customer::factory(10)->create([
            'created_by_id' => $user->id,
        ]);

        $categories = Category::factory(10)->create();

        Product::factory(10) ->create([
            'category_id' => $categories->random()->id,
        ]);

        Order::factory(5)->create();

        OrderItem::factory(50)->create();

        $this->call(RolePermissionSeeder::class);
    }
}
