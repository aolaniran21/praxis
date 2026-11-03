<?php

use App\Database\Connectors\PostgresConnector;

it('passes supported PostgreSQL channel binding settings to PDO', function () {
    $connector = new class extends PostgresConnector
    {
        public function dsn(array $config): string
        {
            return $this->getDsn($config);
        }
    };

    $dsn = $connector->dsn([
        'host' => 'localhost',
        'port' => 5432,
        'database' => 'university_management',
        'sslmode' => 'require',
        'channel_binding' => 'require',
    ]);

    expect($dsn)->toContain('sslmode=require')
        ->toContain('channel_binding=require');
});

it('rejects unsupported PostgreSQL channel binding settings', function () {
    $connector = new class extends PostgresConnector
    {
        public function dsn(array $config): string
        {
            return $this->getDsn($config);
        }
    };

    expect(fn () => $connector->dsn([
        'host' => 'localhost',
        'database' => 'university_management',
        'channel_binding' => 'unexpected',
    ]))->toThrow(InvalidArgumentException::class);
});
