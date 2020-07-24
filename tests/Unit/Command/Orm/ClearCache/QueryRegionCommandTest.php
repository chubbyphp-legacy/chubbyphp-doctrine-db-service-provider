<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryRegionCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand as BaseQueryRegionCommand;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache\QueryRegionCommand
 *
 * @internal
 */
final class QueryRegionCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf(): void
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseQueryRegionCommand::class, new QueryRegionCommand($managerRegistry));
    }
}
