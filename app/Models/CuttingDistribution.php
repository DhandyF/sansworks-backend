<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuttingDistribution extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'cutting_result_id',
        'tailor_id',
        'brand_id',
        'article_id',
        'size_id',
        'total_cutting',
        'taken_date',
        'deadline_date',
        'distribution_number',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'total_cutting' => 'integer',
            'taken_date' => 'date',
            'deadline_date' => 'date',
        ];
    }

    /**
     * Scope to check if deadline is passed.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now());
    }

    /**
     * Get the cutting result for this distribution.
     */
    public function cuttingResult(): BelongsTo
    {
        return $this->belongsTo(CuttingResult::class);
    }

    /**
     * Get the tailor for this distribution.
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the brand for this distribution.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the article for this distribution.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the size for this distribution.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the user who created this distribution.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this distribution.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get deposit cutting results for this distribution.
     */
    public function depositCuttingResults(): HasMany
    {
        return $this->hasMany(DepositCuttingResult::class);
    }
}
