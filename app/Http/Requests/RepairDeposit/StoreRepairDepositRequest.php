<?php

namespace App\Http\Requests\RepairDeposit;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'repair_id' => ['required', 'exists:repairs,id'],
            'tailor_id' => ['required', 'exists:tailors,id'],
            'total_deposit' => ['required', 'integer', 'min:1'],
            'deposit_date' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $repairId = $this->input('repair_id');
            $totalDeposit = (int) $this->input('total_deposit');

            if ($repairId && $totalDeposit > 0) {
                $repair = \App\Models\Repair::find($repairId);
                if ($repair) {
                    $totalDeposited = $repair->deposits()->sum('total_deposit');
                    $available = $repair->total_repair - $totalDeposited;

                    if ($totalDeposit > $available) {
                        $validator->errors()->add('total_deposit', "Total deposit ({$totalDeposit}) exceeds remaining repair quantity ({$available}).");
                    }
                }
            }
        });
    }
}