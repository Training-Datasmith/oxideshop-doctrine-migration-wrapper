<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use OxidEsales\DoctrineMigrationWrapper\MigrationAvailabilityChecker;
use PHPUnit\Framework\TestCase;

final class MigrationAvailabilityCheckerTest extends TestCase
{
    public function testReturnFalseWhenFileDoesNotExist(): void
    {
        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists('some_not_existing_file'));
    }

    public function testReturnTrueWhenMigrationExist(): void
    {
        $structure = [
            'migration' => [
                'migrations.yml' => 'configuration for migrations',
                'project_migrations.yml' => 'configuration for migrations  - project',
                'data' => [
                    'Version20170522094119.php' => 'migrations'
                ]
            ]
        ];

        vfsStream::setup('root', 777, $structure);
        $pathToMigrationConfigurationFile = vfsStream::url('root/migration/migrations.yml');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertTrue($availabilityChecker->migrationExists($pathToMigrationConfigurationFile));
    }

    public function testReturnFalseWhenNoMigrationsExist(): void
    {
        $structure = [
            'migration' => [
                'migrations.yml' => 'configuration for migrations',
                'project_migrations.yml' => 'configuration for migrations  - project',
                'data' => []
            ]
        ];

        vfsStream::setup('root', 777, $structure);
        $pathToMigrationConfigurationFile = vfsStream::url('root/migration/migrations.yml');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists($pathToMigrationConfigurationFile));
    }

    public function testReturnFalseWhenDataDirectoryDoesNotExist(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration', 0777, true);
        file_put_contents($root . '/migration/migrations.yml', 'config');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists($root . '/migration/migrations.yml'));

        unlink($root . '/migration/migrations.yml');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnFalseWhenSiblingPathIsAFile(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration', 0777, true);
        file_put_contents($root . '/migration/migrations.yml', 'config');
        file_put_contents($root . '/migration/data', 'not-a-directory');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists($root . '/migration/migrations.yml'));

        unlink($root . '/migration/data');
        unlink($root . '/migration/migrations.yml');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnTrueForBareRelativeProjectMigrationsConfig(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/project_data', 0777, true);
        file_put_contents($root . '/project_migrations.yml', 'config');
        file_put_contents($root . '/project_data/VersionBareProject.php', '<?php');

        $previousCwd = getcwd();
        chdir($root);
        try {
            $availabilityChecker = new MigrationAvailabilityChecker();
            $this->assertTrue($availabilityChecker->migrationExists('project_migrations.yml'));
        } finally {
            chdir($previousCwd);
            unlink($root . '/project_data/VersionBareProject.php');
            unlink($root . '/project_migrations.yml');
            rmdir($root . '/project_data');
            rmdir($root);
        }
    }

    public function testReturnTrueForRelativeProjectMigrationsConfig(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration/project_data', 0777, true);
        file_put_contents($root . '/migration/project_migrations.yml', 'config');
        file_put_contents($root . '/migration/project_data/VersionRelProject.php', '<?php');

        $previousCwd = getcwd();
        chdir($root);
        try {
            $availabilityChecker = new MigrationAvailabilityChecker();
            $this->assertTrue($availabilityChecker->migrationExists('migration/project_migrations.yml'));
        } finally {
            chdir($previousCwd);
            unlink($root . '/migration/project_data/VersionRelProject.php');
            unlink($root . '/migration/project_migrations.yml');
            rmdir($root . '/migration/project_data');
            rmdir($root . '/migration');
            rmdir($root);
        }
    }

    public function testReturnTrueForAbsoluteProjectMigrationsConfig(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration/project_data', 0777, true);
        $config = $root . '/migration/project_migrations.yml';
        file_put_contents($config, 'config');
        file_put_contents($root . '/migration/project_data/VersionAbsProject.php', '<?php');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertTrue($availabilityChecker->migrationExists($config));

        unlink($root . '/migration/project_data/VersionAbsProject.php');
        unlink($config);
        rmdir($root . '/migration/project_data');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnTrueForStandardMigrationsConfigInDataDirectory(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration/data', 0777, true);
        $config = $root . '/migration/migrations.yml';
        file_put_contents($config, 'config');
        $this->assertStringNotContainsString('project_migrations', $config);
        file_put_contents($root . '/migration/data/VersionCe.php', '<?php');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertTrue($availabilityChecker->migrationExists($config));

        unlink($root . '/migration/data/VersionCe.php');
        unlink($config);
        rmdir($root . '/migration/data');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnFalseWhenOnlyProjectDataHasMigrationForCeConfig(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration/data', 0777, true);
        mkdir($root . '/migration/project_data', 0777, true);
        $config = $root . '/migration/migrations.yml';
        file_put_contents($config, 'config');
        file_put_contents($root . '/migration/project_data/VersionOnlyProject.php', '<?php');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists($config));

        unlink($root . '/migration/project_data/VersionOnlyProject.php');
        unlink($config);
        rmdir($root . '/migration/project_data');
        rmdir($root . '/migration/data');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnTrueWhenGitKeepAndMigrationFileExist(): void
    {
        $root = sys_get_temp_dir() . '/mw_checker_' . uniqid('', true);
        mkdir($root . '/migration/data', 0777, true);
        file_put_contents($root . '/migration/migrations.yml', 'config');
        file_put_contents($root . '/migration/data/.gitkeep', '');
        file_put_contents($root . '/migration/data/VersionWithKeep.php', '<?php');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertTrue($availabilityChecker->migrationExists($root . '/migration/migrations.yml'));

        unlink($root . '/migration/data/VersionWithKeep.php');
        unlink($root . '/migration/data/.gitkeep');
        unlink($root . '/migration/migrations.yml');
        rmdir($root . '/migration/data');
        rmdir($root . '/migration');
        rmdir($root);
    }

    public function testReturnFalseWhenGitKeepExist(): void
    {
        $structure = [
            'migration' => [
                'migrations.yml' => 'configuration for migrations',
                'project_migrations.yml' => 'configuration for migrations  - project',
                'data' => [
                    '.gitkeep' => ''
                ]
            ]
        ];

        vfsStream::setup('root', 777, $structure);
        $pathToMigrationConfigurationFile = vfsStream::url('root/migration/migrations.yml');

        $availabilityChecker = new MigrationAvailabilityChecker();
        $this->assertFalse($availabilityChecker->migrationExists($pathToMigrationConfigurationFile));
    }
}
