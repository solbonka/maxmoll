<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InsufficientStockException extends Exception
{
    public function __construct(
        protected int $productId,
        protected int $requested,
        protected ?int $available = null,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $msg = $message ?: "Недостаточно товара на складе для товара ID: {$productId}";
        parent::__construct($msg, $code, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage(),
            'product_id' => $this->productId,
            'requested' => $this->requested,
            'available' => $this->available,
        ], 400);
    }
}
