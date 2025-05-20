<?php

namespace App\DTOs;

readonly class OrderItemDTO
{
    public function __construct(
        public int $product_id,
        public int $count,
    ) {}
}
