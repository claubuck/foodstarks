<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => 'sometimes|integer|exists:tables,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_phone' => 'sometimes|string|max:50',
            'reservation_time' => 'sometimes|date',
            'party_size' => 'sometimes|integer|min:1|max:50',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled,no_show',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
