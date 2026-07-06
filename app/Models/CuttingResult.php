<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuttingResult extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'pre_order_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_cutting',
        'excess_cutting',
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

    protected static function booted(): void
    {
        static::deleting(function (CuttingResult $cr) {
            $cr->distributions()->each(fn ($d) => $d->delete());
        });

        static::restoring(function (CuttingResult $cr) {
            $cr->distributions()->onlyTrashed()->each(fn ($d) => $d->restore());
        });
    }
}