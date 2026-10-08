<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\MigrationsPathProvider;
use OxidEsales\DoctrineMigrationWrapper\Suite\Standin\StandinBasicContext;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\MigrationFixtureSupport;
use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;

final class MigrationsPathProviderTest extends SuiteTestCase
{
    private string $shopRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopRoot = sys_get_temp_dir() . '/mw_shop_' . MigrationFixtureSupport::uniqueSuffix();
        mkdir($this->shopRoot, 0777, true);
        SuiteShopState::setDefaultShopId(7);
        SuiteShopState::setShopConfiguration(new ShopConfiguration());
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->shopRoot);
        parent::tearDown();
    }

    #[DataProvider('editionProvider')]
    public function testGetMigrationsPathForEdition(Edition $edition, array $expectedKeys): void
    {
        $paths = $this->configureShop($edition);
        $provider = new MigrationsPathProvider();
        $result = $provider->getMigrationsPath(null);

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result);
            $this->assertTrue(is_file($result[$key]), 'Expected file for ' . $key);
        }

        foreach (['bare'] as $missing) {
            $this->assertArrayNotHasKey($missing, $result);
        }

        if ($edition === Edition::Community) {
            $this->assertArrayNotHasKey('pe', $result);
            $this->assertArrayNotHasKey('ee', $result);
        }

        $this->assertSame(array_values($expectedKeys), array_keys($result));
        $this->assertSame($paths['ce'], $result['ce']);
        $this->assertSame($paths['pr'], $result['pr']);
        $this->assertSame($paths['acme'], $result['acme']);
    }

    public static function editionProvider(): array
    {
        return [
            'community' => [Edition::Community, ['ce', 'pr', 'acme']],
            'professional' => [Edition::Professional, ['ce', 'pe', 'pr', 'acme']],
            'enterprise' => [Edition::Enterprise, ['ce', 'pe', 'ee', 'pr', 'acme']],
        ];
    }

    public function testGetMigrationsPathFiltersByEditionCaseInsensitive(): void
    {
        $paths = $this->configureShop(Edition::Professional);
        $provider = new MigrationsPathProvider();

        $prOnly = $provider->getMigrationsPath('pR');
        $this->assertSame(['pr' => $paths['pr']], $prOnly);

        $acmeOnly = $provider->getMigrationsPath('ACME');
        $this->assertSame(['acme' => $paths['acme']], $acmeOnly);
    }

    public function testGetMigrationsPathReturnsEmptyForUnknownEdition(): void
    {
        $this->configureShop(Edition::Community);
        $provider = new MigrationsPathProvider();
        $this->assertSame([], $provider->getMigrationsPath('nope'));
    }

    public function testShopConfigurationDaoReceivesDefaultShopId(): void
    {
        $this->configureShop(Edition::Community);
        SuiteShopState::setRecordedShopId(null);
        $provider = new MigrationsPathProvider();
        $provider->getMigrationsPath(null);

        $this->assertNotNull(SuiteShopState::recordedShopId());
        $this->assertSame(7, SuiteShopState::recordedShopId());
    }

    private function configureShop(Edition $edition): array
    {
        $source = $this->shopRoot . '/source';
        $pe = $this->shopRoot . '/pe';
        $ee = $this->shopRoot . '/ee';
        mkdir($source . '/migration/data', 0777, true);
        mkdir($source . '/migration/project_data', 0777, true);
        mkdir($pe . '/migration/data', 0777, true);
        mkdir($ee . '/migration/data', 0777, true);

        $ceConfig = $source . '/migration/migrations.yml';
        $prConfig = $source . '/migration/project_migrations.yml';
        $peConfig = $pe . '/migration/migrations.yml';
        $eeConfig = $ee . '/migration/migrations.yml';
        foreach ([$ceConfig, $prConfig, $peConfig, $eeConfig] as $file) {
            file_put_contents($file, 'table_storage: { table_name: oxmigrations_test }');
        }

        $acmeRoot = $this->shopRoot . '/modules/acme';
        mkdir($acmeRoot . '/migration', 0777, true);
        $acmeConfig = $acmeRoot . '/migration/migrations.yml';
        file_put_contents($acmeConfig, 'table_storage: { table_name: oxmigrations_acme }');

        $bareRoot = $this->shopRoot . '/modules/bare';
        mkdir($bareRoot, 0777, true);

        $acme = (new ModuleConfiguration())->setId('acme')->setModuleSource('modules/acme');
        $bare = (new ModuleConfiguration())->setId('bare')->setModuleSource('modules/bare');
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration($acme);
        $shopConfig->addModuleConfiguration($bare);
        SuiteShopState::setShopConfiguration($shopConfig);

        SuiteShopState::setContext(new StandinBasicContext(
            $this->shopRoot,
            $source,
            $edition,
            $pe,
            $ee,
            7,
        ));

        return [
            'ce' => $ceConfig,
            'pe' => $peConfig,
            'ee' => $eeConfig,
            'pr' => $prConfig,
            'acme' => $acmeConfig,
        ];
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}
