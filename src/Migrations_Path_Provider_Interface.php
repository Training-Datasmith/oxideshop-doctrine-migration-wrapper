<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

interface Migrations_Path_Provider_Interface
{
    public function get_migrations_path($edition = null): array;
}