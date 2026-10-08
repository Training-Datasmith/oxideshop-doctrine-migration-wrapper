<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Support;

use OxidEsales\DoctrineMigrationWrapper\MigrationsPathProviderInterface;

final class MigrationsPathProviderStub implements MigrationsPathProviderInterface
{
    public function __construct(private array $paths)
    {
    }

    public function getMigrationsPath($edition = null): array
    {
        if ($edition === null) {
            return $this->paths;
        }

        foreach ($this->paths as $migrationEdition => $migrationPath) {
            if (strtolower((string) $migrationEdition) === strtolower((string) $edition)) {
                return [$migrationEdition => $migrationPath];
            }
        }

        return [];
    }
}
