<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Command\Orm;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait
 *
 * @internal
 */
class DoctrineOrmCommandTraitTest extends TestCase
{
    use MockByCallsTrait;

    public function testRun()
    {
        $input = new ArrayInput([
            '--em' => 'test',
        ]);

        $output = new BufferedOutput();

        /** @var EntityManager $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class);

        /** @var ManagerRegistry $entityManager */
        $managerRegistry = $this->getMockByCalls(ManagerRegistry::class, [
            Call::create('getManager')->with('test')->willReturn($entityManager),
        ]);

        $command = new class($managerRegistry) extends BaseCommand {
            use DoctrineOrmCommandTrait;
        };

        self::assertSame(0, $command->run($input, $output));

        self::assertSame('success'.PHP_EOL, $output->fetch());
    }
}

abstract class BaseCommand extends Command
{
    protected function configure()
    {
        $this->setName('test');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('success');

        return 0;
    }
}
