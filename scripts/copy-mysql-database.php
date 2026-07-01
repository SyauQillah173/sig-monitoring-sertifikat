<?php

declare(strict_types=1);

function env_value(string $key): string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        fwrite(STDERR, "Missing environment variable {$key}.\n");
        exit(1);
    }

    return $value;
}

function connect_mysql(string $prefix): PDO
{
    $host = env_value($prefix.'_HOST');
    $port = env_value($prefix.'_PORT');
    $database = env_value($prefix.'_DATABASE');
    $username = env_value($prefix.'_USERNAME');
    $password = env_value($prefix.'_PASSWORD');
    $sslCa = getenv($prefix.'_SSL_CA') ?: '';
    $sslRequired = filter_var(getenv($prefix.'_SSL_REQUIRED') ?: false, FILTER_VALIDATE_BOOL);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($sslCa !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    }

    if ($sslRequired && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        $options,
    );
}

function table_names(PDO $pdo, string $database): array
{
    $statement = $pdo->prepare(
        "select table_name from information_schema.tables where table_schema = ? and table_type = 'BASE TABLE' order by table_name",
    );
    $statement->execute([$database]);

    return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function column_names(PDO $pdo, string $database, string $table): array
{
    $statement = $pdo->prepare(
        'select column_name from information_schema.columns where table_schema = ? and table_name = ? order by ordinal_position',
    );
    $statement->execute([$database, $table]);

    return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function quote_identifier(string $identifier): string
{
    return '`'.str_replace('`', '``', $identifier).'`';
}

$sourceDatabase = env_value('SOURCE_DATABASE');
$targetDatabase = env_value('TARGET_DATABASE');

$source = connect_mysql('SOURCE');
$target = connect_mysql('TARGET');

$sourceTables = table_names($source, $sourceDatabase);
$targetTables = table_names($target, $targetDatabase);
$tables = array_values(array_intersect($sourceTables, $targetTables));

if ($tables === []) {
    fwrite(STDERR, "No common tables found.\n");
    exit(1);
}

$target->exec('set foreign_key_checks = 0');

try {
    foreach ($tables as $table) {
        $columns = array_values(array_intersect(
            column_names($source, $sourceDatabase, $table),
            column_names($target, $targetDatabase, $table),
        ));

        if ($columns === []) {
            echo "Skipping {$table}: no common columns.\n";

            continue;
        }

        $quotedTable = quote_identifier($table);
        $quotedColumns = array_map('quote_identifier', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $selectSql = 'select '.implode(', ', $quotedColumns)." from {$quotedTable}";
        $insertSql = "insert into {$quotedTable} (".implode(', ', $quotedColumns).") values ({$placeholders})";

        $target->exec("delete from {$quotedTable}");

        $read = $source->query($selectSql);
        $write = $target->prepare($insertSql);
        $count = 0;

        while ($row = $read->fetch(PDO::FETCH_ASSOC)) {
            $write->execute(array_map(fn (string $column) => $row[$column] ?? null, $columns));
            $count++;
        }

        echo "Copied {$table}: {$count} rows.\n";
    }
} finally {
    $target->exec('set foreign_key_checks = 1');
}

echo "Database copy completed.\n";
