<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        if (!empty($config['endpoint_id'])) {
            $dsn .= ';options=' . urlencode('-c endpoint_id=' . $config['endpoint_id']);
        }

        return $dsn;
    }
}