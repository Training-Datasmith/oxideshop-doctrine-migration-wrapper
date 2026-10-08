<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Support;

use OxidEsales\DoctrineMigrationWrapper\Suite\Standin\StandinBasicContext;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

final class SuiteShopState
{
    private static ?StandinBasicContext $context = null;

    private static ?ShopConfiguration $shopConfiguration = null;

    private static int $defaultShopId = 7;

    private static ?int $recordedShopId = null;

    private static ?string $lastDatabaseUrl = null;

    public static function reset(): void
    {
        self::$context = null;
        self::$shopConfiguration = null;
        self::$defaultShopId = 7;
        self::$recordedShopId = null;
        self::$lastDatabaseUrl = null;
    }

    public static function context(): StandinBasicContext
    {
        if (self::$context === null) {
            self::$context = StandinBasicContext::fromEnvironment();
        }

        return self::$context;
    }

    public static function setContext(StandinBasicContext $context): void
    {
        self::$context = $context;
    }

    public static function shopConfiguration(): ShopConfiguration
    {
        if (self::$shopConfiguration === null) {
            self::$shopConfiguration = new ShopConfiguration();
        }

        return self::$shopConfiguration;
    }

    public static function setShopConfiguration(ShopConfiguration $configuration): void
    {
        self::$shopConfiguration = $configuration;
    }

    public static function defaultShopId(): int
    {
        return self::$defaultShopId;
    }

    public static function setDefaultShopId(int $shopId): void
    {
        self::$defaultShopId = $shopId;
    }

    public static function recordedShopId(): ?int
    {
        return self::$recordedShopId;
    }

    public static function setRecordedShopId(?int $shopId): void
    {
        self::$recordedShopId = $shopId;
    }

    public static function recordDatabaseUrl(string $url): void
    {
        self::$lastDatabaseUrl = $url;
    }

    public static function lastDatabaseUrl(): ?string
    {
        return self::$lastDatabaseUrl;
    }

    public static function addModule(ModuleConfiguration $module): void
    {
        self::shopConfiguration()->addModuleConfiguration($module);
    }
}
