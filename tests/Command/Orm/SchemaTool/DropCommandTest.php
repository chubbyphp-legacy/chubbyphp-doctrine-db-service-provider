<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool\DropCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand as BaseDropCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool\DropCommand
 */
class DropCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseDropCommand::class, new DropCommand($managerRegistry));
    }
}
