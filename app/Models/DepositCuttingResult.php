<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositCuttingResult extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'cutting_distribution_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_sewing_result',
        'cutting_price_per_pcs',
        'total_price',
        'deposit_date',
        'status',
        'quality_notes',
        'notes',
        'charge_amount',
        'charge_percent',
        'default_charge_per_pcs',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
        ];
    }

    public function cuttingDistribution(): BelongsTo
    {
        return $this->belongsTo(CuttingDistribution::class);
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

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}