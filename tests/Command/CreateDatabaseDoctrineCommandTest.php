<?php

namespace Chubbyphp\Tests\DoctrineDbServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Command\CreateDatabaseDoctrineCommand;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\DBAL\Sharding\ShardChoser\MultiTenantShardChoser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\CreateDatabaseDoctrineCommand
 */
class CreateDatabaseDoctrineCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testExecuteWithNameAndShard()
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'wrapperClass' => PoolingShardConnection::class,
                    'driver' => 'pdo_sqlite',
                    'global' => array('path' => sys_get_temp_dir().'/sample.db'),
                    'shards' => array(
                        array('id' => 1, 'memory' => true),
                        array('id' => 2, 'memory' => true),
                    ),
                    'shardChoser' => MultiTenantShardChoser::class,
                ],
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
            '--if-not-exists' => false,
            '--shard' => 2,
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame('Created database /tmp/sample.db for connection named sample'.PHP_EOL, $output->fetch());
    }

    public function testExecuteWithoutName()
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'driver' => 'pdo_sqlite',
                'path' => sys_get_temp_dir().'/sample.db'
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getDefaultConnectionName')->with()->willReturn('default'),
            Call::create('getConnection')->with('default')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--if-not-exists' => false,
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame('Created database /tmp/sample.db for connection named default'.PHP_EOL, $output->fetch());
    }

    public function testWithMissingPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Connection does not contain a \'path\' or \'dbname\' parameter and cannot be dropped.'
        );

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'driver' => 'pdo_sqlite',
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getDefaultConnectionName')->with()->willReturn('default'),
            Call::create('getConnection')->with('default')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--if-not-exists' => false,
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);
        $command->run($input, $output);
    }
}
