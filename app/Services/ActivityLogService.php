<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(string $action, string $subjectType, string $subjectId, ?array $properties = null, ?string $userId = null): ActivityLog
    {
        $userId = $userId ?? Auth::id();
        
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
        ]);
    }

    public function logCreate(string $subjectType, string $subjectId, array $data): ActivityLog
    {
        return $this->log("{$subjectType}.created", $subjectType, $subjectId, $data);
    }

    public function logUpdate(string $subjectType, string $subjectId, array $oldData, array $newData): ActivityLog
    {
        return $this->log("{$subjectType}.updated", $subjectType, $subjectId, [
            'old' => $oldData,
            'new' => $newData,
        ]);
    }

    public function logDelete(string $subjectType, string $subjectId, array $data): ActivityLog
    {
        return $this->log("{$subjectType}.deleted", $subjectType, $subjectId, $data);
    }
}