<?php

namespace Chubbyphp\Tests\DoctrineDbServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Command\CreateDatabaseDoctrineCommand;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
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

    public function testExecuteSqliteWithoutName()
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'driver' => 'pdo_sqlite',
                'path' => sys_get_temp_dir().'/sample.db',
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getDefaultConnectionName')->with()->willReturn('default'),
            Call::create('getConnection')->with('default')->willReturn($connection),
        ]);

        $input = new ArrayInput([]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame('Created database /tmp/sample.db for connection named default.'.PHP_EOL, $output->fetch());
    }

    public function testExecuteSqliteWithMissingPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Connection does not contain a \'path\' or \'dbname\' parameter.'
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

        $input = new ArrayInput([]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);
        $command->run($input, $output);
    }

    public function testExecuteMysqlWithName()
    {
        $dbName = sprintf('sample-%s', uniqid());

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'dbname' => $dbName,
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'password' => 'root',
                    'port' => 3306,
                    'user' => 'root',
                ],
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            sprintf('Created database `%s` for connection named sample.'.PHP_EOL, $dbName),
            $output->fetch()
        );
    }

    public function testExecuteMysqlWithNameDbExists()
    {
        $dbName = sprintf('sample-%s', uniqid());

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'dbname' => $dbName,
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'password' => 'root',
                    'port' => 3306,
                    'user' => 'root',
                ],
            ]),
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'dbname' => $dbName,
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'password' => 'root',
                    'port' => 3306,
                    'user' => 'root',
                ],
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, new BufferedOutput()));
        self::assertSame(1, $command->run($input, $output));

        self::assertStringEndsWith('; database exists'.PHP_EOL, $output->fetch());
    }

    public function testExecuteMysqlWithNameDbExistsAndIfNotExistsTrue()
    {
        $dbName = sprintf('sample-%s', uniqid());

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'dbname' => $dbName,
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'password' => 'root',
                    'port' => 3306,
                    'user' => 'root',
                ],
            ]),
            Call::create('getParams')->with()->willReturn([
                'master' => [
                    'dbname' => $dbName,
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'password' => 'root',
                    'port' => 3306,
                    'user' => 'root',
                ],
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
            '--if-not-exists' => true,
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, new BufferedOutput()));
        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            sprintf('Database `%s` for connection named sample already exists. Skipped.'.PHP_EOL, $dbName),
            $output->fetch()
        );
    }
}
