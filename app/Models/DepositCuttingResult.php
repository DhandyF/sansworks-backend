<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositCuttingResult extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'cutting_distribution_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_sewing_result',
        'deposit_date',
        'status',
        'quality_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_sewing_result' => 'integer',
            'cutting_left' => 'integer',
            'sewing_price' => 'decimal:2',
            'deposit_date' => 'date',
        ];
    }

    /**
     * Get the cutting left (computed field).
     */
    protected function getCuttingLeftAttribute(): int
    {
        $distributed = $this->cuttingDistribution ? $this->cuttingDistribution->total_cutting : 0;
        return max(0, $distributed - $this->total_sewing_result);
    }

    /**
     * Get the sewing price (computed field).
     */
    protected function getSewingPriceAttribute(): float
    {
        $sewingPricePerUnit = $this->article ? $this->article->sewing_price : 0;
        return $this->total_sewing_result * $sewingPricePerUnit;
    }

    /**
     * Scope to only include completed deposits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'done');
    }

    /**
     * Scope to only include in-progress deposits.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to only include overdue deposits.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Get the cutting distribution for this deposit.
     */
    public function cuttingDistribution(): BelongsTo
    {
        return $this->belongsTo(CuttingDistribution::class);
    }

    /**
     * Get the tailor for this deposit.
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the brand for this deposit.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the article for this deposit.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the size for this deposit.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the user who created this deposit.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this deposit.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get QC results for this deposit.
     */
    public function qcResults(): HasMany
    {
        return $this->hasMany(QCResult::class);
    }
}
