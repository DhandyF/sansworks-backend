<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'article_id',
        'size_id',
        'name',
        'pre_order_date',
        'deadline_date',
        'total_pcs',
    ];

    protected function casts(): array
    {
        return [
            'pre_order_date' => 'date',
            'deadline_date' => 'date',
        ];
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

    public function cuttingResults(): HasMany
    {
        return $this->hasMany(CuttingResult::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (PreOrder $preOrder) {
            $preOrder->cuttingResults()->each(fn ($cr) => $cr->delete());
            $preOrder->shipments()->each(fn ($s) => $s->delete());
        });

        static::restoring(function (PreOrder $preOrder) {
            $preOrder->cuttingResults()->onlyTrashed()->each(fn ($cr) => $cr->restore());
            $preOrder->shipments()->onlyTrashed()->each(fn ($s) => $s->restore());
        });
    }
}