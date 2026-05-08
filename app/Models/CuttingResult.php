<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingResult extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'pre_order_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_cutting',
        'remaining',
        'cutting_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cutting_date' => 'date',
        ];
    }

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
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

    public function distributions(): HasMany
    {
        return $this->hasMany(CuttingDistribution::class);
    }
}