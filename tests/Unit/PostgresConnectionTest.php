<?php

use App\Database\PostgresConnection;

it('preserves booleans so PostgreSQL binds them as native boolean parameters', function () {
    $connection = new PostgresConnection(new PDO('sqlite::memory:'), 'test');
    $statement = $connection->getPdo()->prepare('select ?, ?');
    $bindings = $connection->prepareBindings([true, false]);

    $connection->bindValues($statement, $bindings);
    $statement->execute();

    expect($bindings)->toBe([true, false])
        ->and($statement->fetch(PDO::FETCH_NUM))->toBe([1, 0]);
});
