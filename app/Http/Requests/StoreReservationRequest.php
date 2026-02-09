<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = session('current_branch_id');
        return [
            'table_id' => 'required|integer|exists:tables,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'reservation_time' => 'required|date',
            'party_size' => 'required|integer|min:1|max:50',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
