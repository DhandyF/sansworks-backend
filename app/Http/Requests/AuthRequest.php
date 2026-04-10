<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthRequest extends FormRequest
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
        $rules = [];

        // Get the route action to determine which validation to apply
        $action = $this->route()->getActionMethod();

        return match ($action) {
            'register' => $this->registerRules(),
            'login' => $this->loginRules(),
            'updateProfile' => $this->updateProfileRules(),
            'changePassword' => $this->changePasswordRules(),
            default => [],
        };
    }

    /**
     * Validation rules for registration.
     */
    protected function registerRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:admin,manager,staff',
        ];
    }

    /**
     * Validation rules for login.
     */
    protected function loginRules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Validation rules for profile update.
     */
    protected function updateProfileRules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $userId,
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20',
        ];
    }

    /**
     * Validation rules for password change.
     */
    protected function changePasswordRules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Please provide your username or email.',
            'password.required' => 'Please provide your password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
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
