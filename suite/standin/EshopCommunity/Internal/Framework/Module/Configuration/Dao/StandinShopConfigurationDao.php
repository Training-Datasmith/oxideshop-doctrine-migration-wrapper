<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

final class StandinShopConfigurationDao implements ShopConfigurationDaoInterface
{
    public function get(int $shopId): ShopConfiguration
    {
        SuiteShopState::setRecordedShopId($shopId);

        return SuiteShopState::shopConfiguration();
    }
}
