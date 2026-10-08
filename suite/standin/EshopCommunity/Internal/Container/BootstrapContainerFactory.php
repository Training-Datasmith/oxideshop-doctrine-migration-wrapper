<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Container;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\StandinShopConfigurationDao;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Psr\Container\ContainerInterface;

final class BootstrapContainerFactory
{
    public static function getBootstrapContainer(): ContainerInterface
    {
        return new StandinContainer();
    }
}

final class StandinContainer implements ContainerInterface
{
    public function get(string $id)
    {
        if ($id === BasicContextInterface::class) {
            return SuiteShopState::context();
        }

        if ($id === ShopConfigurationDaoInterface::class) {
            return new StandinShopConfigurationDao();
        }

        throw new \RuntimeException('Unknown service: ' . $id);
    }

    public function has(string $id): bool
    {
        return $id === BasicContextInterface::class || $id === ShopConfigurationDaoInterface::class;
    }
}
