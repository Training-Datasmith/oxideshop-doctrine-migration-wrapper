<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

class ShopConfiguration
{
    /** @var array<string, ModuleConfiguration> */
    private array $modules = [];

    public function getModuleConfigurations(): array
    {
        return array_values($this->modules);
    }

    public function addModuleConfiguration(ModuleConfiguration $moduleConfiguration): void
    {
        $this->modules[$moduleConfiguration->getId()] = $moduleConfiguration;
    }
}
