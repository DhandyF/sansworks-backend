<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fabric extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'unit',
        'total_quantity',
        'price_per_unit',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Get the total price (computed field).
     */
    protected function getTotalPriceAttribute(): float
    {
        return $this->total_quantity * $this->price_per_unit;
    }

    /**
     * Get the user who created this fabric.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this fabric.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get cutting results for this fabric.
     */
    public function cuttingResults(): HasMany
    {
        return $this->hasMany(CuttingResult::class);
    }
}
