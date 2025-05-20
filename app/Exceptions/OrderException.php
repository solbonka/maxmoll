<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

abstract class OrderException extends Exception
{
    public ?int $orderId;

    public function __construct(string $message, ?int $orderId = null, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->orderId = $orderId;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage(),
            'order_id' => $this->orderId,
        ], 400);
    }
}
