<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FabricRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'unit' => 'required|in:pcs,meter,yard,roll',
            'total_quantity' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Fabric name is required.',
            'unit.required' => 'Unit is required.',
            'unit.in' => 'Unit must be one of: pcs, meter, yard, roll.',
            'total_quantity.required' => 'Total quantity is required.',
            'total_quantity.numeric' => 'Total quantity must be a number.',
            'total_quantity.min' => 'Total quantity cannot be negative.',
            'price_per_unit.required' => 'Price per unit is required.',
            'price_per_unit.numeric' => 'Price per unit must be a number.',
            'price_per_unit.min' => 'Price per unit cannot be negative.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
