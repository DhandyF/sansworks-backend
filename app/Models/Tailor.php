<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tailor extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'code',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to only include active tailors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who created this tailor.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this tailor.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get cutting distributions for this tailor.
     */
    public function cuttingDistributions(): HasMany
    {
        return $this->hasMany(CuttingDistribution::class);
    }

    /**
     * Get deposit cutting results for this tailor.
     */
    public function depositCuttingResults(): HasMany
    {
        return $this->hasMany(DepositCuttingResult::class);
    }

    /**
     * Get QC results for this tailor.
     */
    public function qcResults(): HasMany
    {
        return $this->hasMany(QCResult::class);
    }

    /**
     * Get repair distributions for this tailor.
     */
    public function repairDistributions(): HasMany
    {
        return $this->hasMany(RepairDistribution::class);
    }

    /**
     * Get deposit repair results for this tailor.
     */
    public function depositRepairResults(): HasMany
    {
        return $this->hasMany(DepositRepairResult::class);
    }
}
