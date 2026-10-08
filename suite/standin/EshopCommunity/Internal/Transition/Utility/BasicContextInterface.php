<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Transition\Utility;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;

interface BasicContextInterface
{
    public function getActiveModuleServicesFilePath(int $shopId): string;

    public function getAllShopIds(): array;

    public function getCacheDirectory(): string;

    public function getComposerVendorName(): string;

    public function getConfigTableName(): string;

    public function getContainerCacheFilePath(int $shopId): string;

    public function getDatabaseUrl(): string;

    public function getDefaultShopId(): int;

    public function getEdition(): Edition;

    public function getEditionSourcePath(Edition $edition): string;

    public function getGeneratedServicesFilePath(): string;

    public function getModuleCacheDirectory(): string;

    public function getOutPath(): string;

    public function getProjectConfigurationDirectory(): string;

    public function getShopBaseUrl(): string;

    public function getShopConfigurationDirectory(int $shopId): string;

    public function getShopRootPath(): string;

    public function getSourcePath(): string;

    public function getVendorPath(): string;
}
