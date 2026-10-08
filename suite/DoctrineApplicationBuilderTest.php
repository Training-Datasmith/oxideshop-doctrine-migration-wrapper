<?php

declare(strict_types=1);

namespace OxidEsales\DoctrineMigrationWrapper\Suite;

use OxidEsales\DoctrineMigrationWrapper\DoctrineApplicationBuilder;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class DoctrineApplicationBuilderTest extends SuiteTestCase
{
    public function testBuildReturnsFreshApplicationInstances(): void
    {
        $builder = new DoctrineApplicationBuilder();
        $first = $builder->build();
        $second = $builder->build();

        $this->assertNotSame(spl_object_id($first), spl_object_id($second));
    }

    public function testRunPropagatesCommandNotFoundException(): void
    {
        $application = (new DoctrineApplicationBuilder())->build();

        $this->expectException(CommandNotFoundException::class);
        $application->run(new ArrayInput(['command' => 'no-such-command']), new NullOutput());
    }
}
