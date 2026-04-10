<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStatistic extends Model
{
    protected $fillable = [
        'statistic_date',
        'total_fabric_input',
        'total_fabric_cost',
        'total_cutting_result',
        'total_cutting_distribution',
        'total_deposit_cutting',
        'total_sewing_price',
        'total_qc_result',
        'total_qc_to_repair',
        'total_repair_distribution',
        'total_deposit_repair',
        'active_tailors',
        'active_brands',
        'completed_orders',
        'overdue_orders',
        'completion_rate',
        'defect_rate',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'statistic_date' => 'date',
            'total_fabric_input' => 'decimal:2',
            'total_fabric_cost' => 'decimal:2',
            'total_cutting_result' => 'integer',
            'total_cutting_distribution' => 'integer',
            'total_deposit_cutting' => 'integer',
            'total_sewing_price' => 'decimal:2',
            'total_qc_result' => 'integer',
            'total_qc_to_repair' => 'integer',
            'total_repair_distribution' => 'integer',
            'total_deposit_repair' => 'integer',
            'active_tailors' => 'integer',
            'active_brands' => 'integer',
            'completed_orders' => 'integer',
            'overdue_orders' => 'integer',
            'completion_rate' => 'decimal:2',
            'defect_rate' => 'decimal:2',
        ];
    }

    /**
     * Scope to get statistics for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('statistic_date', [$startDate, $endDate]);
    }

    /**
     * Get the user who created this statistic.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this statistic.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
