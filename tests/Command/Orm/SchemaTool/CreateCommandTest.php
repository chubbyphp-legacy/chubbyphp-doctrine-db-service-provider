<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool\CreateCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand as BaseCreateCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool\CreateCommand
 *
 * @internal
 */
final class CreateCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf(): void
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseCreateCommand::class, new CreateCommand($managerRegistry));
    }
}
