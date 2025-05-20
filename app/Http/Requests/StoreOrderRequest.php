<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => 'required|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.count' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения.',
            'string' => 'Поле :attribute должно быть строкой.',
            'exists' => 'Указанный :attribute не найден.',
            'items.required' => 'Необходимо указать хотя бы одну позицию.',
            'array' => 'Поле :attribute должно быть массивом.',
            'min' => 'Минимальное значение для поля :attribute — 1.',
            'integer' => 'Поле :attribute должно быть целым числом.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer' => 'клиент',
            'warehouse_id' => 'склад',
            'items' => 'позиции',
            'items.*.product_id' => 'товар',
            'items.*.count' => 'количество',
        ];
    }
}
