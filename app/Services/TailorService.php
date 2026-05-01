<?php

namespace App\Services;

use App\Models\Tailor;

class TailorService extends BaseService
{
    public function __construct(Tailor $model)
    {
        $this->model = $model;
    }
}
