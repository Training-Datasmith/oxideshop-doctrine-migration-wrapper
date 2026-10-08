<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;

final class MigrationsDbFileTest extends SuiteTestCase
{
    protected function tearDown(): void
    {
        putenv('OXID_DB_URL');
        unset($_ENV['OXID_DB_URL']);
        parent::tearDown();
    }

    public function testMigrationsDbFileReturnsConnectionParametersFromOxidDbUrl(): void
    {
        $url = 'mysql://mw_test:mw_test@127.0.0.1:3306/mw_test';
        putenv('OXID_DB_URL=' . $url);
        $_ENV['OXID_DB_URL'] = $url;

        $expected = (new DatabaseConfiguration($url))->getConnectionParameters();
        $actual = require dirname(__DIR__) . '/src/migrations-db.php';

        $this->assertSame($expected, $actual);
        $this->assertSame($url, SuiteShopState::lastDatabaseUrl());
    }
}
