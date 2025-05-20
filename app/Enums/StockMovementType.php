<?php

namespace App\Enums;

enum StockMovementType: string
{
    case ORDER_CREATED = 'order_created';
    case ORDER_UPDATED = 'order_updated';
    case ORDER_CANCELED = 'order_canceled';
    case ORDER_RESUMED = 'order_resumed';
}
