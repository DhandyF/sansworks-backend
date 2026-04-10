<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'sewing_price',
        'code',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sewing_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to only include active articles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who created this article.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this article.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get cutting results for this article.
     */
    public function cuttingResults(): HasMany
    {
        return $this->hasMany(CuttingResult::class);
    }

    /**
     * Get cutting distributions for this article.
     */
    public function cuttingDistributions(): HasMany
    {
        return $this->hasMany(CuttingDistribution::class);
    }

    /**
     * Get deposit cutting results for this article.
     */
    public function depositCuttingResults(): HasMany
    {
        return $this->hasMany(DepositCuttingResult::class);
    }

    /**
     * Get QC results for this article.
     */
    public function qcResults(): HasMany
    {
        return $this->hasMany(QCResult::class);
    }

    /**
     * Get repair distributions for this article.
     */
    public function repairDistributions(): HasMany
    {
        return $this->hasMany(RepairDistribution::class);
    }

    /**
     * Get deposit repair results for this article.
     */
    public function depositRepairResults(): HasMany
    {
        return $this->hasMany(DepositRepairResult::class);
    }
}
