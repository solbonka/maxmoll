<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementFilterRequest;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    public function index(StockMovementFilterRequest $request): JsonResponse
    {
        $query = StockMovement::with(['product', 'warehouse']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json(
            $query->orderByDesc('created_at')
                ->paginate($request->get('per_page', 15))
        );
    }
}
