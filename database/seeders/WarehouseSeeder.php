<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::insert([
            ['name' => 'Склад №1'],
            ['name' => 'Склад №2'],
            ['name' => 'Склад №3'],
        ]);
    }
}
