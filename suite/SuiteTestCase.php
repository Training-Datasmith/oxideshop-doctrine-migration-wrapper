<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\Suite\Support\SuiteShopState;
use PHPUnit\Framework\TestCase;

abstract class SuiteTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SuiteShopState::reset();
    }
}
