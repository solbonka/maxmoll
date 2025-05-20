<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => 'sometimes|string',
            'items' => 'sometimes|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.count' => 'required_with:items|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'required_with' => 'Поле :attribute обязательно, если указаны позиции.',
            'exists' => 'Указанный :attribute не найден.',
            'integer' => 'Поле :attribute должно быть целым числом.',
            'count.min' => 'Минимальное количество в поле :attribute — 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer' => 'клиент',
            'items' => 'позиции',
            'items.*.product_id' => 'товар',
            'items.*.count' => 'количество',
        ];
    }
}
