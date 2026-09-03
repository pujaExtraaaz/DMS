<?php

namespace App\Http\Requests\Order;

use App\Domains\Order\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order
            ? $this->user()?->can('update', $order) === true
            : $this->user()?->can('create', Order::class) === true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],

            'order_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],

            'items.*.uom_id' => [
                'required',
                'integer',
                Rule::exists('uoms', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.9999',
            ],

            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the order.',
            'items.min' => 'An order must contain at least one product.',
            'items.max' => 'An order cannot contain more than 100 lines.',
            'items.*.quantity.gt' => 'Quantity must be greater than zero.',
        ];
    }
}