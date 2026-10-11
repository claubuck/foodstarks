<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'category_id' => ['required', Rule::exists('product_categories', 'id')->where('restaurant_id', $this->user()?->restaurant_id)],
            'is_active' => 'boolean',
            'stock_alert' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'category_id.exists' => 'La categoría no existe.',
            'image_url.max' => 'La imagen no debe exceder los 5 MB.',
            'image_url.image' => 'El archivo debe ser una imagen (jpg, png, webp o gif).',
            'image_url.mimes' => 'El archivo debe ser una imagen (jpg, png, webp o gif).',
            'category_id.required' => 'La categoría es obligatoria.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'stock_alert.integer' => 'La alerta de stock debe ser un número entero.',
        ];
    }

    
}
