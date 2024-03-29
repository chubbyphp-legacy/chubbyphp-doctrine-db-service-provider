<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Command;

use Chubbyphp\DoctrineDbServiceProvider\Command\DropDatabaseDoctrineCommand;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\DropDatabaseDoctrineCommand
 *
 * @internal
 */
final class DropDatabaseDoctrineCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testExecuteSqliteWithoutName(): void
    {
        $dbName = sprintf('sample-%s', uniqid());

        $path = sys_get_temp_dir().'/'.$dbName.'.db';

        $setupConnection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ]);

        $setupConnection->getSchemaManager()->createDatabase($path);

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'driver' => 'pdo_sqlite',
                'path' => $path,
            ]),
            Call::create('close'),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getDefaultConnectionName')->with()->willReturn('default'),
            Call::create('getConnection')->with('default')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--force' => true,
        ]);

        $output = new BufferedOutput();

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            str_replace('dbname', $path, 'Dropped database dbname for connection named default.'.PHP_EOL),
            $output->fetch()
        );
    }

    public function testExecuteSqliteWithoutNameAndMissingForce(): void
    {
        $dbName = sprintf('sample-%s', uniqid());

        $path = sys_get_temp_dir().'/'.$dbName.'.db';

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getParams')->with()->willReturn([
                'driver' => 'pdo_sqlite',
                'path' => $path,
            ]),
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getDefaultConnectionName')->with()->willReturn('default'),
            Call::create('getConnection')->with('default')->willReturn($connection),
        ]);

        $input = new ArrayInput([]);

        $output = new BufferedOutput();

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(2, $command->run($input, $output));

        $message = <<<'EOT'
            ATTENTION: This operation should not be executed in a production environment.

            Would drop the database /tmp/sample.db for connection named default.
            Please run the operation with --force to execute
            All data will be lost!

            EOT;

        self::assertSame(str_replace('sample', $dbName, $message), $output->fetch());
    }

    public function testExecuteSqliteWithMissingPath(): void
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

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);
        $command->run($input, $output);
    }

    public function testExecutepgsqlWithName(): void
    {
        $dbName = sprintf('sample-%s', uniqid());

        $setupConnection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'url' => getenv('POSTGRES_URL')
                ? getenv('POSTGRES_URL') : 'pgsql://root:root@localhost:5432?charset=utf8',
        ]);

        $setupConnection->getSchemaManager()->createDatabase('"'.$dbName.'"');

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'url' => getenv('POSTGRES_URL')
                ? getenv('POSTGRES_URL') : 'pgsql://root:root@localhost:5432?charset=utf8',
            'dbname' => $dbName,
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
            '--force' => true,
        ]);

        $output = new BufferedOutput();

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            str_replace('dbname', $dbName, 'Dropped database "dbname" for connection named sample.'.PHP_EOL),
            $output->fetch()
        );
    }

    public function testExecutepgsqlWithNameAndMissingDatabaseIfExists(): void
    {
        $dbName = sprintf('sample-%s', uniqid());

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'url' => getenv('POSTGRES_URL')
                ? getenv('POSTGRES_URL') : 'pgsql://root:root@localhost:5432?charset=utf8',
            'dbname' => $dbName,
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
            '--if-exists' => true,
            '--force' => true,
        ]);

        $output = new BufferedOutput();

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            str_replace(
                'dbname',
                $dbName,
                'Database "dbname" for connection named sample doesn\'t exist. Skipped.'.PHP_EOL
            ),
            $output->fetch()
        );
    }

    public function testExecutepgsqlWithNameAndMissingDatabase(): void
    {
        $dbName = sprintf('sample-%s', uniqid());

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'url' => getenv('POSTGRES_URL')
                ? getenv('POSTGRES_URL') : 'pgsql://root:root@localhost:5432?charset=utf8',
            'dbname' => $dbName,
        ]);

        /** @var ConnectionRegistry|MockObject $connectionRegistry */
        $connectionRegistry = $this->getMockByCalls(ConnectionRegistry::class, [
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
            '--force' => true,
        ]);

        $output = new BufferedOutput();

        $command = new DropDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(1, $command->run($input, $output));

        $message = <<<'EOT'
            Could not drop database "dbname" for connection named sample.
            An exception occurred while executing 'DROP DATABASE "dbname"':
            EOT;

        self::assertStringStartsWith(str_replace('dbname', $dbName, $message), $output->fetch());
    }
}
