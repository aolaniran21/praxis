<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as FrameworkPostgresConnector;
use InvalidArgumentException;

class PostgresConnector extends FrameworkPostgresConnector
{
    protected function addSslOptions($dsn, array $config)
    {
        $dsn = parent::addSslOptions($dsn, $config);

        if (! isset($config['channel_binding'])) {
            return $dsn;
        }

        if (! in_array($config['channel_binding'], ['disable', 'prefer', 'require'], true)) {
            throw new InvalidArgumentException('PostgreSQL channel_binding must be disable, prefer, or require.');
        }

        return $dsn.';channel_binding='.$config['channel_binding'];
    }
}
