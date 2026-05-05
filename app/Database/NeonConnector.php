<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        if (!empty($config['neon_options'])) {
            $dsn .= ';options=' . urlencode($config['neon_options']);
        }

        return $dsn;
    }
}