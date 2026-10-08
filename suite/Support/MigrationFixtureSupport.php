<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Support;

use Symfony\Component\Yaml\Yaml;

final class MigrationFixtureSupport
{
    public static function writeCeConfig(string $root, string $metadataTable, string $namespace, string $dataDir): string
    {
        $migrationDir = $root . '/migration';
        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0777, true);
        }
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $configPath = $migrationDir . '/migrations.yml';
        file_put_contents($configPath, Yaml::dump([
            'table_storage' => ['table_name' => $metadataTable],
            'migrations_paths' => [$namespace => 'data'],
        ], 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return $configPath;
    }

    public static function writeProjectConfig(
        string $root,
        string $metadataTable,
        string $namespace,
        string $projectDataDir,
    ): string {
        $migrationDir = $root . '/migration';
        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0777, true);
        }
        if (!is_dir($projectDataDir)) {
            mkdir($projectDataDir, 0777, true);
        }

        $configPath = $migrationDir . '/project_migrations.yml';
        file_put_contents($configPath, Yaml::dump([
            'table_storage' => ['table_name' => $metadataTable],
            'migrations_paths' => [$namespace => 'project_data'],
        ], 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return $configPath;
    }

    public static function writeMigrationClass(
        string $filePath,
        string $fqcn,
        string $markerTable,
        string $markerId,
        bool $createTable = true,
        bool $dropOnDown = false,
    ): void {
        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        $createSql = $createTable
            ? "CREATE TABLE `{$markerTable}` (`id` VARCHAR(64) NOT NULL PRIMARY KEY);"
            : '';
        $insertSql = "INSERT INTO `{$markerTable}` (`id`) VALUES ('{$markerId}');";
        $downSql = $dropOnDown ? "DROP TABLE `{$markerTable}`;" : '';

        $upBody = '';
        if ($createSql !== '') {
            $upBody .= "\$this->addSql('{$createSql}');\n        ";
        }
        $upBody .= "\$this->addSql(\"{$insertSql}\");";

        $downBody = $downSql !== '' ? "\$this->addSql('{$downSql}');" : '';

        $php = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class {$className} extends AbstractMigration
{
    public function up(Schema \$schema): void
    {
        {$upBody}
    }

    public function down(Schema \$schema): void
    {
        {$downBody}
    }
}

PHP;

        file_put_contents($filePath, $php);
    }

    public static function uniqueSuffix(): string
    {
        return bin2hex(random_bytes(4));
    }
}
