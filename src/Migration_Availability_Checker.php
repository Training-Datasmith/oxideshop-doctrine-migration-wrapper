<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

class Migration_Availability_Checker
{
    /**
     * Check if migrations exist.
     * At least one file for migrations must exist.
     * For example configuration exists, but no migration exist yet would result false.
     *
     * @param string $pathToConfiguration path to file which describes configuration for Doctrine Migrations.
     */
    public function migration_exists($path_to_configuration): bool
    {
        if (!is_file($path_to_configuration)) {
            return false;
        }
        $path_to_migrations_directory = $this->get_path_to_migrations($path_to_configuration);
        if ($this->at_least_one_migration_file_exist($path_to_migrations_directory)) {
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
    private function get_path_to_migrations($path_to_configuration): string
    {
        $path_to_migrations_root_directory = \dirname($path_to_configuration);
        if (strpos($path_to_configuration, 'project_migrations')) {
            return $path_to_migrations_root_directory . DIRECTORY_SEPARATOR . 'project_data';
        }
        return $path_to_migrations_root_directory . DIRECTORY_SEPARATOR . 'data';
    }
    /**
     * Check if at least one migration file exist by ignoring other files:
     * - upper directory indicator
     * - .gitkeep which might exist in a directory to keep it in a version system
     *
     *
     */
    private function at_least_one_migration_file_exist(string $path_to_migrations_directory): bool
    {
        $not_migration_files = ['.', '..'];
        if (file_exists($path_to_migrations_directory . DIRECTORY_SEPARATOR . '.gitkeep')) {
            $not_migration_files[] = '.gitkeep';
        }
        return count(scandir($path_to_migrations_directory)) > count($not_migration_files);
    }
}