<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::insert([
            ['name' => 'Товар A', 'price' => 100.50],
            ['name' => 'Товар B', 'price' => 250.00],
            ['name' => 'Товар C', 'price' => 75.99],
            ['name' => 'Товар D', 'price' => 150.00],
            ['name' => 'Товар E', 'price' => 199.99],
        ]);
    }
}
