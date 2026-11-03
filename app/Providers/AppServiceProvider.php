<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use App\Database\PostgresConnection;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', PostgresConnector::class);

        Connection::resolverFor(
            'pgsql',
            fn (PDO|Closure $pdo, string $database, string $prefix, array $config): PostgresConnection => new PostgresConnection(
                $pdo,
                $database,
                $prefix,
                $config,
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
