<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use Oxid_Esales\Eshop_Community\Internal\Container\Bootstrap_Container_Factory;
use Oxid_Esales\Eshop_Community\Internal\Framework\Edition\Edition;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
class Migrations_Path_Provider implements Migrations_Path_Provider_Interface
{
    private readonly Basic_Context_Interface $context;
    private readonly Shop_Configuration $shop_configuration;
    private readonly string $default_filename;
    public function __construct()
    {
        $this->default_filename = 'migrations.yml';
        $this->context = Bootstrap_Container_Factory::get_bootstrap_container()->get(Basic_Context_Interface::class);
        $this->shop_configuration = Bootstrap_Container_Factory::get_bootstrap_container()->get(Shop_Configuration_Dao_Interface::class)->get($this->context->get_default_shop_id());
    }
    public function get_migrations_path($edition = null): array
    {
        $all_migration_paths = array_merge($this->get_shop_paths(), $this->get_modules_path());
        if ($edition === null) {
            return $all_migration_paths;
        }
        $migration_paths = [];
        foreach ($all_migration_paths as $migration_edition => $migration_path) {
            if (strtolower((string) $migration_edition) === strtolower($edition)) {
                $migration_paths[$migration_edition] = $migration_path;
                break;
            }
        }
        return $migration_paths;
    }
    private function get_shop_paths(): array
    {
        $paths = ['ce' => $this->get_migration_file_path($this->context->get_source_path(), $this->default_filename)];
        if (!$this->context->get_edition()->is_community_edition()) {
            $paths['pe'] = $this->get_migration_file_path($this->context->get_edition_source_path(Edition::Professional), $this->default_filename);
        }
        if ($this->context->get_edition() === Edition::Enterprise) {
            $paths['ee'] = $this->get_migration_file_path($this->context->get_edition_source_path(Edition::Enterprise), $this->default_filename);
        }
        $paths['pr'] = $this->get_migration_file_path($this->context->get_source_path(), 'project_migrations.yml');
        return $paths;
    }
    private function get_modules_path(): array
    {
        $paths = [];
        foreach ($this->shop_configuration->get_module_configurations() as $module_configuration) {
            $module_source = Path::join($this->context->get_shop_root_path(), $module_configuration->get_module_source());
            $migration_configuration_path = $this->get_migration_file_path($module_source, $this->default_filename);
            if (file_exists($migration_configuration_path)) {
                $paths[$module_configuration->get_id()] = $migration_configuration_path;
            }
        }
        return $paths;
    }
    private function get_migration_file_path(string $source_path, string $filename): string
    {
        return Path::join($source_path, 'migration', $filename);
    }
}