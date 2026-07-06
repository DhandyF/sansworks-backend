<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repair extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tailor_id',
        'brand_id',
        'article_id',
        'name',
        'total_repair',
        'sewing_price',
        'taken_date',
        'deadline_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'taken_date' => 'date',
            'deadline_date' => 'date',
            'sewing_price' => 'decimal:2',
        ];
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(RepairDeposit::class);
    }

    public function getTotalDepositedAttribute(): int
    {
        return $this->deposits()->sum('total_deposit');
    }

    public function getRemainingAttribute(): int
    {
        return $this->total_repair - $this->total_deposited;
    }
}