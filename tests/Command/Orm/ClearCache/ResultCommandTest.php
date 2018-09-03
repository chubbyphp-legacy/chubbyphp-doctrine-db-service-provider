<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\ResultCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand as BaseResultCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\ResultCommand
 */
class ResultCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $entityManager */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseResultCommand::class, new ResultCommand($managerRegistry));
    }
}
