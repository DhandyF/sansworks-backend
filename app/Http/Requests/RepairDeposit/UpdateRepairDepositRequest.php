<?php

namespace App\Http\Requests\RepairDeposit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepairDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'repair_id' => ['sometimes', 'exists:repairs,id'],
            'tailor_id' => ['sometimes', 'exists:tailors,id'],
            'total_deposit' => ['sometimes', 'integer', 'min:1'],
            'deposit_date' => ['sometimes', 'date'],
        ];
    }
}