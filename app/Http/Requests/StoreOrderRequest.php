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
        $branchId = $this->attributes->get('current_branch_id') ?? session('current_branch_id');
        $restaurantId = $this->user()?->restaurant_id;
        $inBranch = fn ($q) => $q->where('branch_id', $branchId)->where('restaurant_id', $restaurantId);

        // El precio NO viene del cliente: se toma de products al guardar.
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where($inBranch)->where('is_active', true)],
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.notes' => 'nullable|string|max:255',
            'type' => 'required|in:dine_in,takeaway,delivery',
            'payment_method' => 'required|in:cash,card,transfer',
            'table_id' => ['nullable', 'integer', Rule::exists('tables', 'id')->where($inBranch)],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where($inBranch)],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Agregá al menos un producto.',
            'items.*.product_id.exists' => 'Uno de los productos ya no está disponible.',
        ];
    }
}
