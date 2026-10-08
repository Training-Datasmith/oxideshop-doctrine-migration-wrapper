<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use Doctrine\Migrations\Exception\MigrationClassNotFound;
use OxidEsales\DoctrineMigrationWrapper\DoctrineApplicationBuilder;
use OxidEsales\DoctrineMigrationWrapper\MigrationAvailabilityChecker;
use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\DatabaseTestSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\MigrationFixtureSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\MigrationsPathProviderStub;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Output\BufferedOutput;

final class MigrationsExecutionTest extends SuiteTestCase
{
    private string $workRoot;

    private string $dbConfigPath;

    /** @var list<string> */
    private array $tablesToDrop = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->workRoot = sys_get_temp_dir() . '/mw_exec_' . MigrationFixtureSupport::uniqueSuffix();
        mkdir($this->workRoot, 0777, true);
        $this->dbConfigPath = $this->workRoot . '/db.php';
        DatabaseTestSupport::writeDbConfigPhp($this->dbConfigPath);
    }

    protected function tearDown(): void
    {
        foreach (array_unique($this->tablesToDrop) as $table) {
            DatabaseTestSupport::dropTableIfExists($table);
        }
        $this->removeTree($this->workRoot);
        parent::tearDown();
    }

    public function testMigrateRunsCeAndProjectSuites(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $this->tablesToDrop[] = $markerTable;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $metaPr = 'oxmigrations_project_' . $suffix;
        $this->tablesToDrop[] = $metaCe;
        $this->tablesToDrop[] = $metaPr;

        $source = $this->workRoot . '/source';
        $nsCe = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Ce' . $suffix;
        $nsPr = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Pr' . $suffix;

        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $nsCe, $source . '/migration/data');
        $prConfig = MigrationFixtureSupport::writeProjectConfig($source, $metaPr, $nsPr, $source . '/migration/project_data');

        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionCe' . $suffix . '.php',
            $nsCe . '\\VersionCe' . $suffix,
            $markerTable,
            'ce',
        );
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/project_data/VersionPr' . $suffix . '.php',
            $nsPr . '\\VersionPr' . $suffix,
            $markerTable,
            'project',
            false,
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig, 'pr' => $prConfig]);
        $this->assertSame(0, $migrations->execute('migrations:migrate'));

        $pdo = DatabaseTestSupport::pdo();
        $rows = $pdo->query("SELECT id FROM `{$markerTable}` ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['ce', 'project'], $rows);
    }

    public function testSkipsEmptySuiteAndRunsSecond(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $this->tablesToDrop[] = $markerTable;
        $metaEmpty = 'oxmigrations_empty_' . $suffix;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $this->tablesToDrop[] = $metaEmpty;
        $this->tablesToDrop[] = $metaCe;

        $source = $this->workRoot . '/source';
        $emptyData = $source . '/migration_empty/data';
        mkdir($emptyData, 0777, true);
        file_put_contents($emptyData . '/.gitkeep', '');
        $emptyConfig = MigrationFixtureSupport::writeCeConfig(
            $source . '/migration_empty',
            $metaEmpty,
            'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Empty' . $suffix,
            $emptyData,
        );

        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Run' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionRun' . $suffix . '.php',
            $ns . '\\VersionRun' . $suffix,
            $markerTable,
            'only',
        );

        $migrations = $this->createMigrations(['empty' => $emptyConfig, 'ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:migrate'));

        $count = (int) DatabaseTestSupport::pdo()->query("SELECT COUNT(*) FROM `{$markerTable}`")->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testDryRunDoesNotCreateMarkerTable(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $this->tablesToDrop[] = $metaCe;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Dry' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionDry' . $suffix . '.php',
            $ns . '\\VersionDry' . $suffix,
            $markerTable,
            'dry',
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:migrate', null, ['--dry-run' => null]));

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerTable}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    #[DataProvider('emptyCommandProvider')]
    public function testEmptyCommandRunsStatus(?string $command): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $this->tablesToDrop[] = $metaCe;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Status' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionStatus' . $suffix . '.php',
            $ns . '\\VersionStatus' . $suffix,
            $markerTable,
            'status',
        );

        $output = new BufferedOutput();
        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $migrations->setOutput($output);
        $this->assertSame(0, $migrations->execute($command));

        $contents = $output->fetch();
        $dbName = getenv('MW_DB_NAME') ?: 'mw_test';
        $this->assertStringContainsString($dbName, $contents);
        $this->assertStringContainsString($metaCe, $contents);

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerTable}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    public static function emptyCommandProvider(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    public function testUnknownEditionReturnsWithoutRunning(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $metaCe = 'oxmigrations_ce_' . $suffix;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\NoEd' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionNoEd' . $suffix . '.php',
            $ns . '\\VersionNoEd' . $suffix,
            $markerTable,
            'x',
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:migrate', 'no-such-edition'));

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerTable}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    public function testReservedConfigurationFlagIsRejected(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Flag' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_ce_' . $suffix, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionFlag' . $suffix . '.php',
            $ns . '\\VersionFlag' . $suffix,
            $markerTable,
            'flag',
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The following flags are not allowed to be overwritten: --configuration');
        $migrations->execute('migrations:migrate', 'ce', ['--configuration' => '/tmp/x']);
    }

    public function testMultipleReservedFlagsListedInOrder(): void
    {
        $migrations = $this->createMigrations(['ce' => $this->dummyConfigPath()]);
        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage(
            'The following flags are not allowed to be overwritten: --db-configuration, -n',
        );
        $migrations->execute('migrations:migrate', 'ce', [
            '--db-configuration' => '/tmp/db.php',
            '-n' => null,
        ]);
    }

    public function testStopsAfterFirstSuiteFailure(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerFail = 'mw_fail_' . $suffix;
        $markerOk = 'mw_ok_' . $suffix;
        $this->tablesToDrop[] = $markerFail;
        $this->tablesToDrop[] = $markerOk;

        $source = $this->workRoot . '/source';
        $nsFail = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Fail' . $suffix;
        $nsOk = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Ok' . $suffix;

        $failConfig = MigrationFixtureSupport::writeCeConfig(
            $source . '/fail',
            'oxmigrations_fail_' . $suffix,
            $nsFail,
            $source . '/fail/migration/data',
        );
        $okConfig = MigrationFixtureSupport::writeCeConfig(
            $source . '/ok',
            'oxmigrations_ok_' . $suffix,
            $nsOk,
            $source . '/ok/migration/data',
        );

        $failFile = $source . '/fail/migration/data/VersionFail' . $suffix . '.php';
        $failClass = $nsFail . '\\VersionFail' . $suffix;
        file_put_contents($failFile, $this->failingMigrationSource($failClass, $markerFail, $suffix));

        MigrationFixtureSupport::writeMigrationClass(
            $source . '/ok/migration/data/VersionOk' . $suffix . '.php',
            $nsOk . '\\VersionOk' . $suffix,
            $markerOk,
            'ok',
        );

        $migrations = $this->createMigrations(['fail' => $failConfig, 'ok' => $okConfig]);

        $runFailed = false;
        try {
            $exitCode = $migrations->execute('migrations:migrate');
            if ($exitCode !== 0) {
                $runFailed = true;
            }
        } catch (\Throwable) {
            $runFailed = true;
        }

        $this->assertTrue($runFailed, 'Expected migration run to fail before the second suite');

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerOk}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    public function testExecuteMigrationWithVersionsAndUp(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $this->tablesToDrop[] = $markerTable;
        $metaCe = 'oxmigrations_ce_' . $suffix;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Exec' . $suffix;
        $fqcn = $ns . '\\VersionExec' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionExec' . $suffix . '.php',
            $fqcn,
            $markerTable,
            'exec',
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:execute', 'ce', [
            '--up' => null,
            'versions' => [$fqcn],
        ]));

        $count = (int) DatabaseTestSupport::pdo()->query("SELECT COUNT(*) FROM `{$markerTable}`")->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testExecuteMigrationDownRemovesMarker(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $this->tablesToDrop[] = $markerTable;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $this->tablesToDrop[] = $metaCe;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Down' . $suffix;
        $fqcn = $ns . '\\VersionDown' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionDown' . $suffix . '.php',
            $fqcn,
            $markerTable,
            'down',
            true,
            true,
        );

        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:execute', 'ce', [
            '--up' => null,
            'versions' => [$fqcn],
        ]));
        $this->assertSame(0, $migrations->execute('migrations:execute', 'ce', [
            '--down' => null,
            'versions' => [$fqcn],
        ]));

        $exists = DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$markerTable}'",
        )->fetchColumn();
        $this->assertSame('0', (string) $exists);
    }

    public function testMissingMigrationClassMentionsSuite(): void
    {
        $ceConfig = $this->dummyConfigPath();
        $migrations = $this->createMigrations(['ce' => $ceConfig]);

        $this->expectException(MigrationClassNotFound::class);
        $this->expectExceptionMessageMatches('/CE/');
        $this->expectExceptionMessage('VersionNope');
        $migrations->execute('migrations:execute', 'ce', [
            'versions' => ['OxidEsales\\Missing\\VersionNope'],
        ]);
    }

    public function testUnknownCommandThrows(): void
    {
        $migrations = $this->createMigrations(['ce' => $this->dummyConfigPath()]);
        $this->expectException(CommandNotFoundException::class);
        $migrations->execute('migrations:not-a-command');
    }

    public function testGenerateCreatesMigrationFileAndSuiteHint(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Gen' . $suffix;
        $dataDir = $source . '/migration/data';
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_gen_' . $suffix, $ns, $dataDir);
        file_put_contents($dataDir . '/.gitkeep', '');

        $before = count(glob($dataDir . '/*.php') ?: []);
        $output = new BufferedOutput();
        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $migrations->setOutput($output);
        $this->assertSame(0, $migrations->execute('migrations:generate', 'ce'));

        $after = glob($dataDir . '/*.php') ?: [];
        $this->assertCount($before + 1, $after);
        $newFile = end($after);
        $this->assertTrue(is_string($newFile) && is_file($newFile));

        $contents = $output->fetch();
        $this->assertStringContainsString('migrations:execute CE', $contents);
    }

    public function testWriteSqlFlagIsForwarded(): void
    {
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $markerTable = 'mw_marker_' . $suffix;
        $this->tablesToDrop[] = $markerTable;
        $metaCe = 'oxmigrations_ce_' . $suffix;
        $this->tablesToDrop[] = $metaCe;

        $source = $this->workRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Sql' . $suffix;
        $ceConfig = MigrationFixtureSupport::writeCeConfig($source, $metaCe, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionSql' . $suffix . '.php',
            $ns . '\\VersionSql' . $suffix,
            $markerTable,
            'sql',
        );

        $sqlFile = $this->workRoot . '/out.sql';
        $migrations = $this->createMigrations(['ce' => $ceConfig]);
        $this->assertSame(0, $migrations->execute('migrations:migrate', 'ce', ['--write-sql' => $sqlFile]));

        $this->assertTrue(is_file($sqlFile));
        $sqlContents = file_get_contents($sqlFile);
        $this->assertIsString($sqlContents);
        $this->assertStringContainsString($markerTable, $sqlContents);
        $count = (int) DatabaseTestSupport::pdo()->query("SELECT COUNT(*) FROM `{$markerTable}`")->fetchColumn();
        $this->assertSame(1, $count);
    }

    private function createMigrations(array $suites): Migrations
    {
        return new Migrations(
            new DoctrineApplicationBuilder(),
            $this->dbConfigPath,
            new MigrationAvailabilityChecker(),
            new MigrationsPathProviderStub($suites),
        );
    }

    private function dummyConfigPath(): string
    {
        $source = $this->workRoot . '/dummy';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Dummy';
        $dataDir = $source . '/migration/data';
        return MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_dummy', $ns, $dataDir);
    }

    private function failingMigrationSource(string $fqcn, string $markerTable, string $suffix): string
    {
        $parts = explode('\\', $fqcn);
        $class = array_pop($parts);
        $namespace = implode('\\', $parts);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class {$class} extends AbstractMigration
{
    public function up(Schema \$schema): void
    {
        \$this->addSql('CREATE TABLE `{$markerTable}` (`id` VARCHAR(64) NOT NULL PRIMARY KEY);');
        \$this->addSql('SELECT * FROM table_that_does_not_exist_{$suffix}');
    }

    public function down(Schema \$schema): void
    {
    }
}

PHP;
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
