<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryRegionCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand as BaseQueryRegionCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryRegionCommand
 */
class QueryRegionCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $entityManager */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseQueryRegionCommand::class, new QueryRegionCommand($managerRegistry));
    }
}
