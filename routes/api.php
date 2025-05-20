<?php

use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\ProductController;
use App\Http\Controllers\api\StockMovementController;
use App\Http\Controllers\api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::get('/products-with-stocks', [ProductController::class, 'withStocks']);
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::put('/orders/{order}', [OrderController::class, 'update']);
Route::post('/orders/{order}/complete', [OrderController::class, 'complete']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
Route::post('/orders/{order}/resume', [OrderController::class, 'resume']);
Route::get('/stock-movements', [StockMovementController::class, 'index']);
