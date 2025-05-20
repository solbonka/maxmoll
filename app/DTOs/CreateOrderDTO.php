<?php

namespace App\DTOs;

readonly class CreateOrderDTO
{
    /**
     * @param OrderItemDTO[] $items
     */
    public function __construct(
        public string $customer,
        public int $warehouse_id,
        public array $items,
    ) {}
}
