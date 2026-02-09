<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = session('current_branch_id');
        $restaurantId = $this->user()?->restaurant_id;

        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($q) use ($branchId, $restaurantId) {
                    $q->where('branch_id', $branchId)->where('restaurant_id', $restaurantId);
                }),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'type' => 'required|in:dine_in,takeaway,delivery',
            'payment_method' => 'required|in:cash,card,transfer',
            'table_id' => 'nullable|integer|exists:tables,id',
            'order_id' => 'nullable|integer|exists:orders,id',
        ];
    }
}
