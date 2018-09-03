<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\EnsureProductionSettingsCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand as BaseEnsureProductionSettingsCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\EnsureProductionSettingsCommand
 */
class EnsureProductionSettingsCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $entityManager */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseEnsureProductionSettingsCommand::class, new EnsureProductionSettingsCommand($managerRegistry));
    }
}
