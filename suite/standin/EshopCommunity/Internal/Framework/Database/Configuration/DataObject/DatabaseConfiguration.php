<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;

final class DatabaseConfiguration
{
    public function __construct(private readonly string $databaseUrl)
    {
        SuiteShopState::recordDatabaseUrl($databaseUrl);
    }

    public function getConnectionParameters(): array
    {
        if (!preg_match('#^mysql://([^:]+):([^@]+)@([^:]+):(\d+)/([^?]+)#', $this->databaseUrl, $matches)) {
            throw new \InvalidArgumentException('Unsupported MW test database URL format');
        }

        return [
            'driver' => 'pdo_mysql',
            'user' => $matches[1],
            'password' => $matches[2],
            'host' => $matches[3],
            'port' => (int) $matches[4],
            'dbname' => $matches[5],
        ];
    }
}
