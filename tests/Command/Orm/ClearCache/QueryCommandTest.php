<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand as BaseQueryCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryCommand
 */
class QueryCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $entityManager */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseQueryCommand::class, new QueryCommand($managerRegistry));
    }
}
