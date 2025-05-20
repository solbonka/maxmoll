<?php

namespace App\Exceptions;

class CannotResumeOrderException extends OrderException
{
    public function __construct(?int $orderId = null)
    {
        parent::__construct('Можно возобновить только отменённый заказ.', $orderId);
    }
}
