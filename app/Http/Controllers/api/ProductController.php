<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function withStocks(): JsonResponse
    {
        $products = Product::with(['stocks.warehouse'])->get();

        return response()->json($products);
    }
}
