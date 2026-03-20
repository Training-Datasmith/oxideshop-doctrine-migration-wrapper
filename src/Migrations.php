<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use Doctrine\Migrations\Exception\Migration_Class_Not_Found;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Array_Input;
use Symfony\Component\Console\Output\Console_Output;
use Symfony\Component\Console\Output\Output;
/**
 * Class to run Doctrine Migration commands.
 * OXID eShop might have several migrations to run for different edition and project.
 * This class ensures that all needed migrations run.
 */
class Migrations
{
    /** Command for doctrine to run database migrations. */
    public const MIGRATE_COMMAND = 'migrations:migrate';
    private const STATUS_COMMAND = 'migrations:status';
    /** @var Output Add a possibility to provide a custom output handler */
    private ?\Symfony\Component\Console\Output\Output $output = null;
    /**
     * @var string[]
     */
    private array $predefined_command_keys = ['configuration' => '--configuration', 'dbConfiguration' => '--db-configuration', 'noInteraction' => '-n'];
    /**
     *
     * @param $doctrineApplicationBuilder
     * @param $dbFilePath
     * @param $migrationAvailabilityChecker
     * @param $migrationsPathProvider
     * @param \OxidEsales\DoctrineMigrationWrapper\DoctrineApplicationBuilder $doctrineApplicationBuilder
     * @param string $dbFilePath
     * @param \OxidEsales\DoctrineMigrationWrapper\MigrationsPathProvider $migrationsPathProvider
     */
    public function __construct(
        private $doctrine_application_builder,
        /** @var string path to file which contains database configuration for Doctrine Migrations */
        private $db_file_path,
        /** @var  \OxidEsales\DoctrineMigrationWrapper\$MigrationAvailabilityChecker */
        private $migration_availability_checker,
        private $migrations_path_provider
    )
    {
    }
    /**
     * @param Output|null $output Add a possibility to provide a custom output handler
     */
    public function set_output(?Output $output = null): void
    {
        $this->output = $output;
    }
    /**
     * Execute Doctrine Migration command for all needed Shop edition and project.
     */
    public function execute(?string $command, ?string $edition = null, array $flags = []): int
    {
        $command = (string) $command;
        $migration_paths = $this->migrations_path_provider->get_migrations_path($edition);
        $this->validate_flags($flags);
        foreach ($migration_paths as $suite => $migration_path) {
            $suite = strtoupper((string) $suite);
            if ($this->should_run_command($command, $migration_path)) {
                $doctrine_application = $this->doctrine_application_builder->build();
                $input = $this->form_doctrine_input($command, $migration_path, $this->db_file_path, $flags);
                try {
                    if ($command && $suite) {
                        $this->add_suite_to_command_name($doctrine_application, $command, $suite);
                    }
                    $error_code = $doctrine_application->run($input, $this->output);
                    if ($suite && $this->is_migrations_generate_command($command)) {
                        $this->append_suite_info_after_help_message_output($suite);
                    }
                } catch (Migration_Class_Not_Found $exception) {
                    throw new Migration_Class_Not_Found("Error running migration for suite type '{$suite}': " . $exception->get_message());
                }
                if ($error_code) {
                    return $error_code;
                }
            }
        }
        return 0;
    }
    /**
     * Form input which is expected by Doctrine.
     *
     * @param string $command command to run.
     * @param string $migrationPath path to migration configuration file.
     * @param string $dbFilePath path to database configuration file.
     * @param array $flags flags for command
     */
    private function form_doctrine_input(string $command, string $migration_path, string $db_file_path, array $flags): Array_Input
    {
        $formed_input = [$this->predefined_command_keys['configuration'] => $migration_path, $this->predefined_command_keys['dbConfiguration'] => $db_file_path, $this->predefined_command_keys['noInteraction'] => true, 'command' => !empty($command) ? $command : self::STATUS_COMMAND];
        $formed_input = array_merge($formed_input, $flags);
        return new Array_Input($formed_input);
    }
    private function validate_flags(array $flags): void
    {
        $not_allowed_flags = array_filter($this->predefined_command_keys, fn(string $var) => array_key_exists($var, $flags));
        if (!empty($not_allowed_flags)) {
            throw new \Symfony\Component\Console\Exception\Invalid_Option_Exception('The following flags are not allowed to be overwritten: ' . implode(', ', $not_allowed_flags));
        }
    }
    /**
     * Check if command should be performed:
     * - All commands should be performed without additional check except migrate
     * - Migrate command should be performed only if actual migrations exist.
     *
     * @param string $command command to run.
     * @param string $migrationPath path to migration configuration file.
     */
    private function should_run_command(string $command, $migration_path): bool
    {
        return $command !== self::MIGRATE_COMMAND || $this->migration_availability_checker->migration_exists($migration_path);
    }
    private function add_suite_to_command_name(Application $doctrine_application, string $command, string $suite): void
    {
        $command_object = $doctrine_application->get($command);
        $command_object->set_name($command_object->get_name() . " {$suite}");
    }
    private function is_migrations_generate_command(string $command): bool
    {
        return $command === 'migrations:generate';
    }
    private function append_suite_info_after_help_message_output(string $suite): void
    {
        ($this->output ?? new Console_Output())->writeln(" Don't forget to add the correct Suite_Type to the above commands <info>migrations:execute {$suite} [options] [--] <versions>...</info>\n");
    }
}