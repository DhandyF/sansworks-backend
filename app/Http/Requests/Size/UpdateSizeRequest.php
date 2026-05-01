<?php

namespace App\Http\Requests\Size;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'abbreviation' => ['sometimes', 'required', 'string', 'max:10'],
        ];
    }
}
