<?php

namespace App\Exceptions;

class CannotCancelOrderException extends OrderException
{
    public function __construct(?int $orderId = null)
    {
        parent::__construct('Можно отменить только активный заказ.', $orderId);
    }
}
