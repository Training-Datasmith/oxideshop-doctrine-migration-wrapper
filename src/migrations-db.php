<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Doctrine_Migration_Wrapper;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
return (new Database_Configuration(getenv('OXID_DB_URL')))->get_connection_parameters();