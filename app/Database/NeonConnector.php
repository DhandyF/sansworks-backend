<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $options = $config['options'] ?? [];
        $optionsValue = $options['options'] ?? $options ?? '';

        if (is_string($optionsValue) && $optionsValue !== '') {
            $dsn .= ';options=' . urlencode($optionsValue);
        }

        return $dsn;
    }
}