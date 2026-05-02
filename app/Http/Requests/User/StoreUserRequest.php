<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'in:admin,client,operator'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
