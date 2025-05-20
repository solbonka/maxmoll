<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $products = Product::all();

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                Stock::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'stock' => rand(10, 100),
                ]);
            }
        }
    }
}
