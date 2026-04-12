<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairDistribution extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'repair_distributions';

    protected $fillable = [
        'qc_result_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_to_repair',
        'taken_date',
        'deadline_repair_date',
        'repair_number',
        'repair_type',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_to_repair' => 'integer',
            'taken_date' => 'date',
            'deadline_repair_date' => 'date',
        ];
    }

    /**
     * Scope to check if deadline is passed.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_repair_date', '<', now());
    }

    /**
     * Get the QC result for this repair distribution.
     */
    public function qcResult(): BelongsTo
    {
        return $this->belongsTo(QCResult::class, 'qc_result_id');
    }

    /**
     * Get the tailor for this repair.
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the brand for this repair.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the article for this repair.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the size for this repair.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the user who created this repair distribution.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this repair distribution.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get deposit repair results for this distribution.
     */
    public function depositRepairResults(): HasMany
    {
        return $this->hasMany(DepositRepairResult::class, 'repair_distribution_id');
    }
}
