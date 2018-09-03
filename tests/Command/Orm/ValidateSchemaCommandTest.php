<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ValidateSchemaCommand;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand as BaseValidateSchemaCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ValidateSchemaCommand
 */
class ValidateSchemaCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testInstanceOf()
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class);

        self::assertInstanceOf(BaseValidateSchemaCommand::class, new ValidateSchemaCommand($managerRegistry));
    }
}
