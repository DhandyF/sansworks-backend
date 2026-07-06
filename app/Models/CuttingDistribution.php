<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuttingDistribution extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'cutting_result_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_cutting',
        'taken_date',
        'deadline_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'taken_date' => 'date',
            'deadline_date' => 'date',
        ];
    }

    public function cuttingResult(): BelongsTo
    {
        return $this->belongsTo(CuttingResult::class);
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

    public function deposits(): HasMany
    {
        return $this->hasMany(DepositCuttingResult::class);
    }
}