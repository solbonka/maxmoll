<?php

namespace App\Services;

use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Enums\StockMovementType;
use App\Exceptions\CannotCancelOrderException;
use App\Exceptions\CannotCompleteOrderException;
use App\Exceptions\CannotResumeOrderException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
    /**
     * Создаёт новый заказ и резервирует товары на складе.
     *
     * @param CreateOrderDTO $dto Данные для создания заказа
     * @return Order Созданный заказ с загруженными позициями
     *
     * @throws InsufficientStockException Если на складе недостаточно товара
     */
    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $this->validateStockAvailability($dto->items, $dto->warehouse_id);

            // Создаём заказ с переданным покупателем, складом и статусом 'active'
            $order = Order::create([
                'customer' => $dto->customer,
                'warehouse_id' => $dto->warehouse_id,
                'status' => 'active',
                'created_at' => now(),
            ]);

            // Обрабатываем позиции заказа: резервируем товары и создаём движения склада
            $this->processOrderItems($order, $dto->items, StockMovementType::ORDER_CREATED->value);

            // Загружаем связанные позиции и возвращаем заказ
            return $order->load('items');
        });
    }

    /**
     * Обновляет заказ: может изменить покупателя и/или позиции.
     * При изменении позиций возвращает старые на склад и резервирует новые.
     *
     * @param Order $order Заказ для обновления
     * @param UpdateOrderDTO $dto Данные для обновления
     * @return Order Обновлённый заказ с позициями
     *
     * @throws InsufficientStockException Если на складе недостаточно товара для новых позиций
     */
    public function updateOrder(Order $order, UpdateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($order, $dto) {
            // Обновляем покупателя, если передано новое значение
            if ($dto->customer !== null) {
                $order->customer = $dto->customer;
                $order->save();
            }

            // Обновляем позиции, если передан новый список
            if ($dto->items !== null) {
                // Возвращаем текущие позиции обратно на склад с фиксацией движения
                $this->returnItemsToStock($order, StockMovementType::ORDER_UPDATED->value);

                // Удаляем старые позиции из заказа
                $order->items()->delete();

                // Резервируем новые позиции и создаём записи движения склада
                $this->processOrderItems($order, $dto->items, StockMovementType::ORDER_UPDATED->value);
            }

            // Загружаем и возвращаем обновлённый заказ с позициями
            return $order->load('items');
        });
    }

    /**
     * Отменяет заказ, возвращая все позиции обратно на склад.
     *
     * @param Order $order Заказ для отмены
     * @return void
     *
     * @throws CannotCancelOrderException Если заказ нельзя отменить (статус не 'active' или 'completed')
     */
    public function cancelOrder(Order $order): void
    {
        // Проверяем, что заказ можно отменить по текущему статусу
        if (!in_array($order->status, ['active', 'completed'])) {
            throw new CannotCancelOrderException($order->id);
        }

        DB::transaction(function () use ($order) {
            // Возвращаем все позиции на склад и создаём соответствующие движения
            $this->returnItemsToStock($order, StockMovementType::ORDER_CANCELED->value);

            // Обновляем статус заказа на 'canceled'
            $order->update([
                'status' => 'canceled',
            ]);
        });
    }

    /**
     * Возобновляет отменённый заказ, резервируя товары на складе снова.
     *
     * @param Order $order Заказ для возобновления
     * @return void
     *
     * @throws CannotResumeOrderException Если статус заказа не 'canceled'
     * @throws InsufficientStockException Если на складе недостаточно товара для резервирования
     */
    public function resumeOrder(Order $order): void
    {
        // Разрешаем возобновить только отменённые заказы
        if ($order->status !== 'canceled') {
            throw new CannotResumeOrderException($order->id);
        }

        DB::transaction(function () use ($order) {
            // Для каждой позиции заказа уменьшаем склад и создаём движение с типом ORDER_RESUMED
            foreach ($order->items as $item) {
                $this->decrementStockAndCreateMovement(
                    $item->product_id,
                    $order->warehouse_id,
                    $item->count,
                    StockMovementType::ORDER_RESUMED->value
                );
            }

            // Обновляем статус заказа на 'active'
            $order->update(['status' => 'active']);
        });
    }

    /**
     * Завершает заказ, устанавливая статус 'completed' и дату завершения.
     *
     * @param Order $order Заказ для завершения
     * @return void
     *
     * @throws CannotCompleteOrderException Если заказ нельзя завершить (статус не 'active')
     */
    public function completeOrder(Order $order): void
    {
        // Разрешаем завершить только активные заказы
        if ($order->status !== 'active') {
            throw new CannotCompleteOrderException($order->id);
        }

        // Обновляем статус заказа и дату завершения
        $order->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);
    }

    /**
     * Проверяет наличие нужного количества товара на складе, блокирует запись для обновления.
     *
     * @param int $productId ID продукта
     * @param int $warehouseId ID склада
     * @param int $requiredQuantity Требуемое количество
     * @return Stock Заблокированная запись склада
     *
     * @throws InsufficientStockException Если на складе недостаточно товара
     */
    private function checkAndLockStock(int $productId, int $warehouseId, int $requiredQuantity): Stock
    {
        // Получаем запись склада с блокировкой для исключения гонок
        $stock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        // Проверяем наличие достаточного количества товара
        if (!$stock || $stock->stock < $requiredQuantity) {
            throw new InsufficientStockException(
                productId: $productId,
                requested: $requiredQuantity,
                available: $stock?->stock
            );
        }

        return $stock;
    }

    /**
     * Обрабатывает позиции заказа: резервирует товары, создаёт движения склада и записи OrderItem.
     *
     * @param Order $order Заказ
     * @param array $items Список позиций с ключами ['product_id', 'count']
     * @param string $movementType Тип движения склада (например, ORDER_CREATED)
     * @return void
     *
     * @throws InsufficientStockException При нехватке товара
     */
    private function processOrderItems(Order $order, array $items, string $movementType): void
    {
        foreach ($items as $item) {
            // Списываем товар со склада и создаём движение
            $this->decrementStockAndCreateMovement(
                $item->product_id,
                $order->warehouse_id,
                $item->count,
                $movementType
            );

            // Создаём запись позиции заказа
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'count' => $item->count,
            ]);
        }
    }

    /**
     * Уменьшает количество товара на складе и создаёт запись движения склада.
     *
     * @param int $productId ID продукта
     * @param int $warehouseId ID склада
     * @param int $quantity Количество для списания
     * @param string $movementType Тип движения склада
     * @return void
     *
     * @throws InsufficientStockException Если товара недостаточно
     */
    private function decrementStockAndCreateMovement(int $productId, int $warehouseId, int $quantity, string $movementType): void
    {
        // Проверяем наличие и блокируем запись склада
        $this->checkAndLockStock($productId, $warehouseId, $quantity);

        // Списываем товар
        Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->decrement('stock', $quantity);

        // Создаём запись движения склада с отрицательным изменением
        StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity_change' => -$quantity,
            'type' => $movementType,
        ]);
    }

    /**
     * Возвращает позиции заказа обратно на склад и создаёт положительные движения склада.
     *
     * @param Order $order Заказ
     * @param string $movementType Тип движения склада (например, ORDER_CANCELED)
     * @return void
     */
    private function returnItemsToStock(Order $order, string $movementType): void
    {
        foreach ($order->items as $item) {
            // Увеличиваем количество товара на складе
            Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->increment('stock', $item->count);

            // Создаём движение склада с положительным изменением
            StockMovement::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $order->warehouse_id,
                'quantity_change' => $item->count,
                'type' => $movementType,
            ]);
        }
    }

    /**
     * Проверяет наличие достаточного количества товара на складе для всех позиций заказа.
     *
     * @param array $items
     * @param int $warehouseId
     * @throws InsufficientStockException
     */
    private function validateStockAvailability(array $items, int $warehouseId): void
    {
        foreach ($items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$stock || $stock->stock < $item->count) {
                throw new InsufficientStockException(
                    productId: $item->product_id,
                    requested: $item->count,
                    available: $stock?->stock
                );
            }
        }
    }
}
