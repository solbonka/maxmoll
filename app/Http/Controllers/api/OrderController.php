<?php

namespace App\Http\Controllers\api;

use App\DTOs\CreateOrderDTO;
use App\DTOs\OrderItemDTO;
use App\DTOs\UpdateOrderDTO;
use App\Exceptions\CannotCancelOrderException;
use App\Exceptions\CannotCompleteOrderException;
use App\Exceptions\CannotResumeOrderException;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Контроллер для работы с заказами (создание, обновление, фильтрация, управление статусами).
 */
class OrderController extends Controller
{
    /**
     * Внедрение OrderService через DI (для бизнес-логики).
     *
     * @param OrderService $service
     */
    public function __construct(private readonly OrderService $service) {}

    /**
     * Получение списка заказов с возможностью фильтрации.
     *
     * @param OrderFilterRequest $request
     * @return JsonResponse
     */
    public function index(OrderFilterRequest $request): JsonResponse
    {
        // Получаем отфильтрованные данные, включая значения по умолчанию
        $filters = $request->validatedWithDefaults();

        // Строим запрос с жадной загрузкой связанных моделей
        $query = Order::with('items.product', 'warehouse');

        // Применяем фильтр по статусу, если передан
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Применяем фильтр по складу, если передан
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        // Пагинация результата и возврат JSON-ответа
        return response()->json($query->paginate($filters['per_page']));
    }

    /**
     * Создание нового заказа.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     *
     * @throws InsufficientStockException Если недостаточно товара на складе для заказа
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = array_map(fn (array $item) => new OrderItemDTO(
            product_id: (int) $item['product_id'],
            count: (int) $item['count']
        ), $data['items']);

        $dto = new CreateOrderDTO(
            customer: $data['customer'],
            warehouse_id: (int) $data['warehouse_id'],
            items: $items
        );
        $order = $this->service->createOrder($dto);

        // Возврат созданного заказа с HTTP-кодом 201
        return response()->json($order, 201);
    }

    /**
     * Обновление существующего заказа.
     *
     * @param UpdateOrderRequest $request
     * @param Order $order
     * @return JsonResponse
     *
     * @throws InsufficientStockException Если недостаточно товара на складе для новых позиций
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        $items = [];
        if (isset($data['items'])) {
            $items = array_map(fn (array $item) => new OrderItemDTO(
                product_id: (int) $item['product_id'],
                count: (int) $item['count']
            ), $data['items']);
        }

        $dto = new UpdateOrderDTO(
            customer: $data['customer'] ?? $order->customer,
            items: $items
        );

        $order = $this->service->updateOrder($order, $dto);

        return response()->json(['data' => $order]);
    }
    /**
     * Отмена заказа.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function cancel(Order $order): JsonResponse
    {
        try {
            // Пытаемся отменить заказ
            $this->service->cancelOrder($order);

            return response()->json(['message' => 'Заказ с ID ' . $order->id . ' отменён.']);
        } catch (CannotCancelOrderException $e) {
            // Ошибка бизнес-логики: заказ нельзя отменить
            return response()->json([
                'error' => $e->getMessage(),
                'order_id' => $e->orderId ?? $order->id,
            ], 400);
        } catch (Throwable $e) {
            // Любая другая (непредвиденная) ошибка
            return response()->json([
                'error' => 'Произошла непредвиденная ошибка.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Возобновление отменённого заказа.
     *
     * @param Order $order
     * @return JsonResponse
     *
     * @throws InsufficientStockException Если на складе недостаточно товара для резервирования
     */
    public function resume(Order $order): JsonResponse
    {
        try {
            // Пытаемся возобновить заказ
            $this->service->resumeOrder($order);

            return response()->json(['message' => 'Заказ с ID ' . $order->id . ' возобновлён.']);
        } catch (CannotResumeOrderException $e) {
            // Ошибка бизнес-логики: заказ нельзя возобновить
            return response()->json([
                'error' => $e->getMessage(),
                'order_id' => $e->orderId ?? $order->id,
            ], 400);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'error' => 'Недостаточно товара на складе для возобновления заказа.',
                'details' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            // Непредвиденная ошибка
            return response()->json([
                'error' => 'Произошла непредвиденная ошибка.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завершение заказа.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function complete(Order $order): JsonResponse
    {
        try {
            // Пытаемся завершить заказ
            $this->service->completeOrder($order);

            return response()->json(['message' => 'Заказ с ID ' . $order->id . ' завершён.']);
        } catch (CannotCompleteOrderException $e) {
            // Ошибка бизнес-логики: нельзя завершить
            return response()->json([
                'error' => $e->getMessage(),
                'order_id' => $e->orderId ?? $order->id,
            ], 400);
        } catch (Throwable $e) {
            // Любая непредвиденная ошибка
            return response()->json([
                'error' => 'Произошла непредвиденная ошибка.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
