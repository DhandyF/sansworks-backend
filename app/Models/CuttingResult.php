<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuttingResult extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'cutting_results';

    protected $fillable = [
        'fabric_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_cutting',
        'cutting_date',
        'batch_number',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_cutting' => 'integer',
            'cutting_date' => 'date',
        ];
    }

    /**
     * Get the fabric for this cutting result.
     */
    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class);
    }

    /**
     * Get the brand for this cutting result.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the article for this cutting result.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the size for this cutting result.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the user who created this cutting result.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this cutting result.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get cutting distributions for this cutting result.
     */
    public function cuttingDistributions(): HasMany
    {
        return $this->hasMany(CuttingDistribution::class, 'cutting_result_id');
    }

    /**
     * Get total distributed (sum of all cutting distributions).
     */
    public function getTotalDistributedAttribute(): int
    {
        return $this->cuttingDistributions()->sum('total_cutting');
    }

    /**
     * Get remaining cutting that hasn't been distributed yet.
     */
    public function getRemainingAttribute(): int
    {
        return $this->total_cutting - $this->total_distributed;
    }

    /**
     * Get total deposited (completed deposits).
     */
    public function getTotalDepositedAttribute(): int
    {
        return $this->cuttingDistributions()
            ->whereHas('depositCuttingResults', function ($query) {
                $query->where('status', 'completed');
            })
            ->with('depositCuttingResults')
            ->get()
            ->sum(function ($distribution) {
                return $distribution->depositCuttingResults->sum('total_deposited');
            });
    }
}
