<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        if (!empty($config['options'])) {
            $dsn .= ';options=' . $config['options'];
        }

        return $dsn;
    }
}