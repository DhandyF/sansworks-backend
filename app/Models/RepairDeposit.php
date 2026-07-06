<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairDeposit extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'repair_id',
        'tailor_id',
        'total_deposit',
        'deposit_date',
        'charge_amount',
        'charge_percent',
        'default_charge_per_pcs',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'charge_amount' => 'decimal:2',
            'default_charge_per_pcs' => 'decimal:2',
        ];
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }
}