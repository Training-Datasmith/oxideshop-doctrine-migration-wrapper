<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use function str_starts_with;
class Migration_Argument_Parser
{
    private ?string $command;
    private ?string $edition;
    private array $flags;
    public function __construct(array $command_line_arguments)
    {
        $this->parse($command_line_arguments);
    }
    protected function parse(array $argv)
    {
        $this->command = $argv[1] ?? null;
        $edition = $argv[2] ?? null;
        // Just in case the second argument is a flag and edition is not set
        if (isset($edition) && str_starts_with($edition, '-')) {
            array_splice($argv, 3, 0, $edition);
            $argv[2] = null;
            $edition = null;
        }
        $this->edition = $edition;
        $flags = [];
        if (isset($argv[3])) {
            // Do not alter $argv itself
            $copy_of_argv = $argv;
            unset($copy_of_argv[0], $copy_of_argv[1], $copy_of_argv[2]);
            $versions = [];
            foreach ($copy_of_argv as $flag) {
                if ($this->is_version_argument((string) $flag)) {
                    $versions[] = $flag;
                    continue;
                }
                /*
                 * Determines if a param has also a value
                 * if case  : --write-sql=/var/www/html/source/migration/project_data/
                 * else case: --dry-run
                 */
                $key_value_pair = explode('=', (string) $flag);
                if (count($key_value_pair) === 2) {
                    $flags[$key_value_pair[0]] = $key_value_pair[1];
                } else {
                    $flags[$flag] = null;
                }
            }
            if ($versions) {
                $flags['versions'] = $versions;
            }
        }
        $this->flags = $flags;
    }
    public function get_command(): ?string
    {
        return $this->command;
    }
    public function get_edition(): ?string
    {
        return $this->edition;
    }
    public function get_flags(): array
    {
        return $this->flags;
    }
    private function is_version_argument(string $flag): bool
    {
        return !str_starts_with($flag, '-');
    }
}