<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            self::logActivity($model, 'created');
        });

        static::updated(function (Model $model) {
            self::logActivity($model, 'updated');
        });

        static::deleted(function (Model $model) {
            self::logActivity($model, 'deleted');
        });

        static::restored(function (Model $model) {
            self::logActivity($model, 'restored');
        });
    }

    /**
     * Log activity for the model.
     */
    protected static function logActivity(Model $model, string $action): void
    {
        $user = Auth::user();

        $oldValues = null;
        $newValues = null;

        if ($action === 'updated') {
            $oldValues = $model->getOriginal();
            $newValues = $model->getAttributes();
        } elseif ($action === 'created' || $action === 'deleted' || $action === 'restored') {
            $newValues = $model->getAttributes();
        }

        ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get the activity logs for this model.
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')
            ->orderBy('created_at', 'desc');
    }
}
