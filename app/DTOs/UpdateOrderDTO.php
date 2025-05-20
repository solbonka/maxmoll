<?php

namespace App\DTOs;

readonly class UpdateOrderDTO
{
    /**
     * @param OrderItemDTO[]|null $items
     */
    public function __construct(
        public ?string $customer = null,
        public ?array $items = null,
    ) {}
}
