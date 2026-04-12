<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositRepairResult extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'deposit_repair_results';

    protected $fillable = [
        'repair_distribution_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'deposit_date',
        'total_repaired',
        'repair_quality_rating',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_repaired' => 'integer',
            'product_to_repair_left' => 'integer',
            'deposit_date' => 'date',
        ];
    }

    /**
     * Get the product to repair left (computed field).
     */
    protected function getProductToRepairLeftAttribute(): int
    {
        $toRepair = $this->repairDistribution ? $this->repairDistribution->total_to_repair : 0;
        return max(0, $toRepair - $this->total_repaired);
    }

    /**
     * Scope to filter by quality rating.
     */
    public function scopeWithQualityRating($query, string $rating)
    {
        return $query->where('repair_quality_rating', $rating);
    }

    /**
     * Get the repair distribution for this deposit.
     */
    public function repairDistribution(): BelongsTo
    {
        return $this->belongsTo(RepairDistribution::class, 'repair_distribution_id');
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
}
