<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Support;

use PDO;

final class DatabaseTestSupport
{
    public static function pdo(): PDO
    {
        $host = getenv('MW_DB_HOST') ?: '127.0.0.1';
        $port = getenv('MW_DB_PORT') ?: '3306';
        $name = getenv('MW_DB_NAME') ?: 'mw_test';
        $user = getenv('MW_DB_USER') ?: 'mw_test';
        $pass = getenv('MW_DB_PASSWORD') ?: 'mw_test';

        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    public static function connectionParameters(): array
    {
        $host = getenv('MW_DB_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('MW_DB_PORT') ?: '3306');
        $name = getenv('MW_DB_NAME') ?: 'mw_test';
        $user = getenv('MW_DB_USER') ?: 'mw_test';
        $pass = getenv('MW_DB_PASSWORD') ?: 'mw_test';

        return [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'port' => $port,
            'dbname' => $name,
            'user' => $user,
            'password' => $pass,
        ];
    }

    public static function writeDbConfigPhp(string $path): void
    {
        $params = self::connectionParameters();
        $export = var_export($params, true);
        file_put_contents($path, "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n");
    }

    public static function dropTableIfExists(string $table): void
    {
        self::pdo()->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
    }

    public static function dropMetadataTableIfExists(string $table): void
    {
        self::dropTableIfExists($table);
    }
}
