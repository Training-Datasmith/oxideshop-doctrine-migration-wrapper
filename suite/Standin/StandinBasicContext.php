<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Standin;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

final class StandinBasicContext implements BasicContextInterface
{
    private string $shopRootPath;

    private string $sourcePath;

    private Edition $edition;

    private string $peSourcePath;

    private string $eeSourcePath;

    private int $defaultShopId;

    public function __construct(
        string $shopRootPath,
        string $sourcePath,
        Edition $edition,
        string $peSourcePath,
        string $eeSourcePath,
        int $defaultShopId = 7,
    ) {
        $this->shopRootPath = $shopRootPath;
        $this->sourcePath = $sourcePath;
        $this->edition = $edition;
        $this->peSourcePath = $peSourcePath;
        $this->eeSourcePath = $eeSourcePath;
        $this->defaultShopId = $defaultShopId;
    }

    public static function fromEnvironment(): self
    {
        $root = getenv('MW_TEST_PROJECT_ROOT');
        if ($root === false || $root === '') {
            $root = sys_get_temp_dir() . '/mw-default-shop';
        }

        $editionName = getenv('MW_TEST_EDITION');
        $edition = Edition::Community;
        if ($editionName === 'PE' || $editionName === 'Professional') {
            $edition = Edition::Professional;
        } elseif ($editionName === 'EE' || $editionName === 'Enterprise') {
            $edition = Edition::Enterprise;
        }

        $source = $root . '/source';

        return new self(
            $root,
            is_dir($source) ? $source : $root,
            $edition,
            $root . '/pe',
            $root . '/ee',
            7,
        );
    }

    public function getActiveModuleServicesFilePath(int $shopId): string
    {
        return $this->shopRootPath . '/var/services_' . $shopId . '.yaml';
    }

    public function getAllShopIds(): array
    {
        return [1];
    }

    public function getCacheDirectory(): string
    {
        return $this->shopRootPath . '/var/cache';
    }

    public function getComposerVendorName(): string
    {
        return 'vendor';
    }

    public function getConfigTableName(): string
    {
        return 'oxconfig';
    }

    public function getContainerCacheFilePath(int $shopId): string
    {
        return $this->getCacheDirectory() . '/container_' . $shopId . '.php';
    }

    public function getDatabaseUrl(): string
    {
        $url = getenv('OXID_DB_URL');

        return $url !== false && $url !== '' ? $url : 'mysql://mw_test:mw_test@127.0.0.1:3306/mw_test';
    }

    public function getDefaultShopId(): int
    {
        return $this->defaultShopId;
    }

    public function getEdition(): Edition
    {
        return $this->edition;
    }

    public function getEditionSourcePath(Edition $edition): string
    {
        return match ($edition) {
            Edition::Professional => $this->peSourcePath,
            Edition::Enterprise => $this->eeSourcePath,
            default => $this->sourcePath,
        };
    }

    public function getGeneratedServicesFilePath(): string
    {
        return $this->shopRootPath . '/var/generated/services.yaml';
    }

    public function getModuleCacheDirectory(): string
    {
        return $this->getCacheDirectory() . '/modules';
    }

    public function getOutPath(): string
    {
        return $this->shopRootPath . '/out';
    }

    public function getProjectConfigurationDirectory(): string
    {
        return $this->shopRootPath . '/var/configuration';
    }

    public function getShopBaseUrl(): string
    {
        return 'http://localhost/';
    }

    public function getShopConfigurationDirectory(int $shopId): string
    {
        return $this->getProjectConfigurationDirectory() . '/shops/' . $shopId;
    }

    public function getShopRootPath(): string
    {
        return $this->shopRootPath;
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getVendorPath(): string
    {
        return $this->shopRootPath . '/vendor';
    }
}
