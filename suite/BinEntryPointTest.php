<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\DatabaseTestSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\MigrationFixtureSupport;

final class BinEntryPointTest extends SuiteTestCase
{
    private string $projectRoot;

    private string $markerTable;

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $this->projectRoot = sys_get_temp_dir() . '/mw_bin_' . $suffix;
        $this->markerTable = 'mw_bin_' . $suffix;
        mkdir($this->projectRoot, 0777, true);

        $source = $this->projectRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Bin' . $suffix;
        MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_bin_' . $suffix, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeProjectConfig(
            $source,
            'oxmigrations_bin_pr_' . $suffix,
            'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\BinPr' . $suffix,
            $source . '/migration/project_data',
        );
        if (!is_dir($source . '/migration/project_data')) {
            mkdir($source . '/migration/project_data', 0777, true);
        }
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionBin' . $suffix . '.php',
            $ns . '\\VersionBin' . $suffix,
            $this->markerTable,
            'bin',
        );

        $url = sprintf(
            'mysql://%s:%s@%s:%s/%s',
            getenv('MW_DB_USER') ?: 'mw_test',
            getenv('MW_DB_PASSWORD') ?: 'mw_test',
            getenv('MW_DB_HOST') ?: '127.0.0.1',
            getenv('MW_DB_PORT') ?: '3306',
            getenv('MW_DB_NAME') ?: 'mw_test',
        );
        file_put_contents($this->projectRoot . '/.env', 'OXID_DB_URL=' . $url . PHP_EOL);
    }

    protected function tearDown(): void
    {
        DatabaseTestSupport::dropTableIfExists($this->markerTable);
        $this->removeTree($this->projectRoot);
        parent::tearDown();
    }

    public function testOeEshopDbMigrateRunsMigration(): void
    {
        $result = $this->runPhpBin([
            dirname(__DIR__) . '/bin/oe-eshop-db_migrate',
            'migrations:migrate',
        ]);
        $this->assertSame(0, $result['exitCode']);

        $count = (int) DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM `{$this->markerTable}` WHERE id = 'bin'",
        )->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testDryRunDoesNotMigrateViaBin(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $dryRoot = sys_get_temp_dir() . '/mw_bin_dry_' . $suffix;
        $markerDry = 'mw_bin_dry_' . $suffix;
        mkdir($dryRoot, 0777, true);
        $source = $dryRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\BinDry' . $suffix;
        MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_bindry_' . $suffix, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionBinDry' . $suffix . '.php',
            $ns . '\\VersionBinDry' . $suffix,
            $markerDry,
            'dry',
        );
        $url = sprintf(
            'mysql://%s:%s@%s:%s/%s',
            getenv('MW_DB_USER') ?: 'mw_test',
            getenv('MW_DB_PASSWORD') ?: 'mw_test',
            getenv('MW_DB_HOST') ?: '127.0.0.1',
            getenv('MW_DB_PORT') ?: '3306',
            getenv('MW_DB_NAME') ?: 'mw_test',
        );
        file_put_contents($dryRoot . '/.env', 'OXID_DB_URL=' . $url . PHP_EOL);

        $result = $this->runPhpBin([
            dirname(__DIR__) . '/bin/oe-eshop-db_migrate',
            'migrations:migrate',
            '--dry-run',
        ], extraEnv: ['MW_TEST_PROJECT_ROOT' => $dryRoot]);
        $this->assertSame(0, $result['exitCode']);

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerDry}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);

        DatabaseTestSupport::dropTableIfExists($markerDry);
        $this->removeTree($dryRoot);
    }

    public function testDoctrineMigrationBinAliasRunsStatus(): void
    {
        $result = $this->runPhpBin([
            dirname(__DIR__) . '/bin/oe-eshop-doctrine_migration',
            'migrations:status',
        ]);
        $this->assertSame(0, $result['exitCode']);
        $dbName = getenv('MW_DB_NAME') ?: 'mw_test';
        $this->assertStringContainsString($dbName, $result['output']);
    }

    public function testMissingAutoloadExitsWithCodeOne(): void
    {
        $temp = sys_get_temp_dir() . '/mw_migrate_only_' . MigrationFixtureSupport::uniqueSuffix();
        mkdir($temp, 0777, true);
        copy(dirname(__DIR__) . '/bin/migrate.php', $temp . '/migrate.php');

        $command = [PHP_BINARY, $temp . '/migrate.php'];
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $temp,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        unlink($temp . '/migrate.php');
        rmdir($temp);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Autoload file was not found!', $stderr . $stdout);
    }

    private function runPhpBin(array $args, array $extraEnv = []): array
    {
        $repoRoot = dirname(__DIR__);
        $env = array_merge([
            'MW_TEST_PROJECT_ROOT' => $this->projectRoot,
            'MW_TEST_EDITION' => 'CE',
            'MW_DB_HOST' => getenv('MW_DB_HOST') ?: '127.0.0.1',
            'MW_DB_PORT' => getenv('MW_DB_PORT') ?: '3306',
            'MW_DB_NAME' => getenv('MW_DB_NAME') ?: 'mw_test',
            'MW_DB_USER' => getenv('MW_DB_USER') ?: 'mw_test',
            'MW_DB_PASSWORD' => getenv('MW_DB_PASSWORD') ?: 'mw_test',
        ], $extraEnv);

        $command = array_merge([PHP_BINARY], $args);
        $descriptor = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open($command, $descriptor, $pipes, $repoRoot, $env);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'output' => $stdout . $stderr,
        ];
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeTree($full) : unlink($full);
        }
        rmdir($path);
    }
}
