<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use Symfony\Component\Filesystem\Path;
class Migrations_Builder
{
    public function build(): Migrations
    {
        return new Migrations(new Doctrine_Application_Builder(), Path::join(__DIR__, 'migrations-db.php'), new Migration_Availability_Checker(), new Migrations_Path_Provider());
    }
}