<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QCResult extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'deposit_cutting_result_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_products',
        'total_to_repair',
        'qc_date',
        'qc_by',
        'defect_details',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_products' => 'integer',
            'total_to_repair' => 'integer',
            'qc_date' => 'date',
            'defect_details' => 'array',
        ];
    }

    /**
     * Calculate defect rate.
     */
    public function getDefectRateAttribute(): float
    {
        if ($this->total_products === 0) {
            return 0;
        }

        return round(($this->total_to_repair / $this->total_products) * 100, 2);
    }

    /**
     * Get the deposit cutting result for this QC.
     */
    public function depositCuttingResult(): BelongsTo
    {
        return $this->belongsTo(DepositCuttingResult::class);
    }

    /**
     * Get the tailor for this QC.
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the brand for this QC.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the article for this QC.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the size for this QC.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the user who performed this QC.
     */
    public function qcBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    /**
     * Get the user who created this QC.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this QC.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get repair distributions for this QC result.
     */
    public function repairDistributions(): HasMany
    {
        return $this->hasMany(RepairDistribution::class);
    }
}
