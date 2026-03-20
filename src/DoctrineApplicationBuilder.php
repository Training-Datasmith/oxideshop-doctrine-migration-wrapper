<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use Doctrine\Migrations\Tools\Console\Console_Runner;
use Symfony\Component\Console\Application;
class Doctrine_Application_Builder
{
    /**
     * Return new application for each build.
     * Application has a reference to command which has internal cache.
     * Reusing same application object with same command leads to an errors due to an old configuration.
     * For example first run with a CE migrations
     * second run with PE migrations
     * both runs would take path to CE migrations.
     */
    public function build(): Application
    {
        $doctrine_application = Console_Runner::create_application();
        $doctrine_application->set_auto_exit(false);
        $doctrine_application->set_catch_exceptions(false);
        // we handle the exception on our own!
        return $doctrine_application;
    }
}