<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper;

class MigrationAvailabilityChecker
{
    /**
     * Check if migrations exist.
     * At least one file for migrations must exist.
     * For example configuration exists, but no migration exist yet would result false.
     *
     * @param string $pathToConfiguration path to file which describes configuration for Doctrine Migrations.
     */
    public function migrationExists($pathToConfiguration): bool
    {
        if (!is_file($pathToConfiguration)) {
            return false;
        }

        $pathToMigrationsDirectory = $this->getPathToMigrations($pathToConfiguration);

        if ($this->atLeastOneMigrationFileExist($pathToMigrationsDirectory)) {
            return true;
        }

        return false;
    }

    /**
     * Find path to migration directory.
     * Different path returned for a project migrations.
     *
     * @param string $pathToConfiguration
     */
    private function getPathToMigrations($pathToConfiguration): string
    {
        $pathToMigrationsRootDirectory = \dirname($pathToConfiguration);
        if (strpos($pathToConfiguration, 'project_migrations')) {
            return $pathToMigrationsRootDirectory . DIRECTORY_SEPARATOR . 'project_data';
        }

        return $pathToMigrationsRootDirectory . DIRECTORY_SEPARATOR . 'data';
    }

    /**
     * Check if at least one migration file exist by ignoring other files:
     * - upper directory indicator
     * - .gitkeep which might exist in a directory to keep it in a version system
     *
     *
     */
    private function atLeastOneMigrationFileExist(string $pathToMigrationsDirectory): bool
    {
        $notMigrationFiles = [
            '.',
            '..',
        ];

        if (file_exists($pathToMigrationsDirectory . DIRECTORY_SEPARATOR . '.gitkeep')) {
            $notMigrationFiles[] = '.gitkeep';
        }

        return count(scandir($pathToMigrationsDirectory)) > count($notMigrationFiles);
    }
}
