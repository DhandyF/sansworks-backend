<?php

namespace App\Traits;

use App\Services\ActivityLogService;

trait LogsActivity
{
    protected ?ActivityLogService $activityLogService = null;

    protected function getActivityLogService(): ActivityLogService
    {
        if (!$this->activityLogService) {
            $this->activityLogService = new ActivityLogService();
        }
        return $this->activityLogService;
    }

    protected function getSubjectType(): string
    {
        $model = $this->model ?? null;
        if (!$model) {
            return 'unknown';
        }
        return strtolower(class_basename($model));
    }

    protected function logCreate(array $data): void
    {
        $this->getActivityLogService()->logCreate($this->getSubjectType(), $data['id'] ?? uniqid(), $data);
    }

    protected function logUpdate(array $oldData, array $newData): void
    {
        $subjectId = $newData['id'] ?? $oldData['id'] ?? uniqid();
        $this->getActivityLogService()->logUpdate($this->getSubjectType(), $subjectId, $oldData, $newData);
    }

    protected function logDelete(array $data): void
    {
        $this->getActivityLogService()->logDelete($this->getSubjectType(), $data['id'] ?? uniqid(), $data);
    }
}