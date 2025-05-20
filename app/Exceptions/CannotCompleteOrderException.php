<?php

namespace App\Exceptions;

class CannotCompleteOrderException extends OrderException
{
    public function __construct(?int $orderId = null)
    {
        parent::__construct('Можно завершить только активный заказ.', $orderId);
    }
}
