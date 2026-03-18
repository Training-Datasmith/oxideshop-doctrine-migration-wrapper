<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\DoctrineMigrationWrapper;

interface MigrationsPathProviderInterface
{
    public function getMigrationsPath($edition = null): array;
}
