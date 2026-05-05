<?php

namespace App\Providers;

use App\Database\NeonConnector;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            $connector = new NeonConnector;
            $connection = $connector->connect($config);

            return new Connection($connection, $database, $prefix, $config);
        });
    }

    public function boot(): void
    {
        //
    }
}
