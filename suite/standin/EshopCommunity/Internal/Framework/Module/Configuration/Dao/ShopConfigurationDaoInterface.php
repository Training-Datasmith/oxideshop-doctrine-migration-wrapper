<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;

interface ShopConfigurationDaoInterface
{
    public function get(int $shopId): ShopConfiguration;
}
