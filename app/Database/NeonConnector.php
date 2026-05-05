<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $endpointId = env('DB_ENDPOINT_ID', '');

        if ($endpointId !== '') {
            $dsn .= ';options=' . urlencode('-c endpoint_id=' . $endpointId);
        }

        return $dsn;
    }
}