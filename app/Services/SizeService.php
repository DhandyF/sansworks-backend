<?php

namespace App\Services;

use App\Models\Size;

class SizeService extends BaseService
{
    public function __construct(Size $model)
    {
        $this->model = $model;
    }
}
