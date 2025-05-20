<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|exists:products,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'exists' => 'Указанный :attribute не найден.',
            'date' => 'Поле :attribute должно быть корректной датой.',
            'after_or_equal' => 'Поле :attribute должно быть не раньше чем поле :other.',
            'integer' => 'Поле :attribute должно быть целым числом.',
            'min' => 'Минимальное значение для поля :attribute — 1.',
            'max' => 'Максимальное значение для поля :attribute — 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'товар',
            'warehouse_id' => 'склад',
            'from' => 'дата начала',
            'to' => 'дата окончания',
            'per_page' => 'кол-во на странице',
        ];
    }

    public function wantsJson(): bool
    {
        return true;
    }
}
