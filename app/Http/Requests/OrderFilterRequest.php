<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:active,completed,canceled',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'Поле :attribute должно быть одним из следующих значений: active, completed, canceled.',
            'exists' => 'Указанный :attribute не найден.',
            'integer' => 'Поле :attribute должно быть целым числом.',
            'min' => 'Минимальное значение для поля :attribute — 1.',
            'max' => 'Максимальное значение для поля :attribute — 100.',
        ];
    }

    public function validatedWithDefaults(): array
    {
        return array_merge(
            ['per_page' => 15],
            $this->validated()
        );
    }

    public function attributes(): array
    {
        return [
            'status' => 'статус',
            'warehouse_id' => 'склад',
            'per_page' => 'кол-во на странице',
        ];
    }
}
