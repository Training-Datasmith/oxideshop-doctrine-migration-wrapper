<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

class ModuleConfiguration
{
    private string $id = '';

    private string $moduleSource = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleSource(): string
    {
        return $this->moduleSource;
    }

    public function setModuleSource(string $moduleSource): self
    {
        $this->moduleSource = $moduleSource;

        return $this;
    }
}
