# 🧾 Микро-CRM: управление заказами, товарами и складами

Проект представляет собой **REST API-сервис** для управления товарами, заказами, складами и учётом движения остатков. Построен на **Laravel 12**.

---

## ⚙️ Технологии

- **PHP 8.3**
- **Laravel 12**
- **MySQL**
- **Docker**

---

## 📦 Функциональность

### 🧍 Заказы

- Создание заказа с товарами
- Обновление заказа
- Завершение заказа
- Возобновление заказа
- Получение списка заказов (с фильтрами и настраиваемой пагинацией)
- Валидация наличия остатков с исключением `InsufficientStockException`

### 📦 Складской учёт

- Таблица `stocks` для учёта остатков по товарам и складам
- Таблица `stock_movements` — история движения товаров
- Списание и возврат товаров автоматически при изменении заказа

### 📑 API по складам и товарам

- Список товаров с их остатками по складам
- Список складов
- Фильтрация истории движения по товару, складу и датам

---

## 📁 Структура DTO

### OrderItemDTO

```php
readonly class OrderItemDTO
{
    public function __construct(
        public int $product_id,
        public int $count,
    ) {}
}
```

### CreateOrderDTO

```php
readonly class CreateOrderDTO
{
    public function __construct(
        public string $customer,
        public int $warehouse_id,
        /** @var OrderItemDTO[] */
        public array $items,
    ) {}
}
```

### UpdateOrderDTO

```php
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
```

## 📊 Структура БД
- products — список товаров

- orders — заказы

- order_items — позиции заказов

- warehouses — склады

- stocks — остатки товаров по складам

- stock_movements — история движений
