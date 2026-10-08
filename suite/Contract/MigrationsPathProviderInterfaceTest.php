<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite\Contract;

use OxidEsales\DoctrineMigrationWrapper\MigrationsPathProvider;
use OxidEsales\DoctrineMigrationWrapper\MigrationsPathProviderInterface;
use OxidEsales\DoctrineMigrationWrapper\Suite\SuiteTestCase;
use ReflectionClass;
use ReflectionMethod;

final class MigrationsPathProviderInterfaceTest extends SuiteTestCase
{
    public function testInterfaceSignature(): void
    {
        $method = new ReflectionMethod(MigrationsPathProviderInterface::class, 'getMigrationsPath');
        $this->assertSame('getMigrationsPath', $method->getName());
        $this->assertTrue($method->getReturnType()?->getName() === 'array');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertTrue($parameters[0]->allowsNull());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNull($parameters[0]->getDefaultValue());
    }

    public function testMigrationsPathProviderImplementsInterface(): void
    {
        $reflection = new ReflectionClass(MigrationsPathProvider::class);
        $this->assertTrue($reflection->implementsInterface(MigrationsPathProviderInterface::class));
    }
}
