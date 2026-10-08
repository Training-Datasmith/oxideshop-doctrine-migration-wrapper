<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\DoctrineMigrationWrapper\Suite\Standin\StandinBasicContext;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\DatabaseTestSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\MigrationFixtureSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

final class MigrationsBuilderTest extends SuiteTestCase
{
    private string $shopRoot;

    private string $markerTable;

    protected function setUp(): void
    {
        parent::setUp();
        $suffix = MigrationFixtureSupport::uniqueSuffix();
        $this->shopRoot = sys_get_temp_dir() . '/mw_builder_' . $suffix;
        $this->markerTable = 'mw_builder_' . $suffix;
        mkdir($this->shopRoot, 0777, true);

        $source = $this->shopRoot . '/source';
        $ns = 'OxidEsales\\DoctrineMigrationWrapper\\Suite\\Fixture\\Builder' . $suffix;
        MigrationFixtureSupport::writeCeConfig($source, 'oxmigrations_builder_' . $suffix, $ns, $source . '/migration/data');
        MigrationFixtureSupport::writeMigrationClass(
            $source . '/migration/data/VersionBuilder' . $suffix . '.php',
            $ns . '\\VersionBuilder' . $suffix,
            $this->markerTable,
            'built',
        );

        SuiteShopState::setShopConfiguration(new ShopConfiguration());
        SuiteShopState::setContext(new StandinBasicContext(
            $this->shopRoot,
            $source,
            Edition::Community,
            $this->shopRoot . '/pe',
            $this->shopRoot . '/ee',
        ));
    }

    protected function tearDown(): void
    {
        DatabaseTestSupport::dropTableIfExists($this->markerTable);
        putenv('OXID_DB_URL');
        unset($_ENV['OXID_DB_URL']);
        $this->removeTree($this->shopRoot);
        parent::tearDown();
    }

    public function testBuildReturnsMigrationsInstance(): void
    {
        $this->assertInstanceOf(Migrations::class, (new MigrationsBuilder())->build());
    }

    public function testBuiltMigrationsMigrateUsingOxidDbUrl(): void
    {
        $url = sprintf(
            'mysql://%s:%s@%s:%s/%s',
            getenv('MW_DB_USER') ?: 'mw_test',
            getenv('MW_DB_PASSWORD') ?: 'mw_test',
            getenv('MW_DB_HOST') ?: '127.0.0.1',
            getenv('MW_DB_PORT') ?: '3306',
            getenv('MW_DB_NAME') ?: 'mw_test',
        );
        putenv('OXID_DB_URL=' . $url);
        $_ENV['OXID_DB_URL'] = $url;

        $result = (new MigrationsBuilder())->build()->execute('migrations:migrate', 'ce');
        $this->assertSame(0, $result);
        $this->assertSame($url, SuiteShopState::lastDatabaseUrl());

        $count = (int) DatabaseTestSupport::pdo()->query(
            "SELECT COUNT(*) FROM `{$this->markerTable}` WHERE id = 'built'",
        )->fetchColumn();
        $this->assertSame(1, $count);
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
