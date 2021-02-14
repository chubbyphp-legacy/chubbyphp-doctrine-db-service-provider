<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Command;

use Chubbyphp\DoctrineDbServiceProvider\Command\CreateDatabaseDoctrineCommand;
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
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Command\CreateDatabaseDoctrineCommand
 *
 * @internal
 */
final class CreateDatabaseDoctrineCommandTest extends TestCase
{
    use MockByCallsTrait;

    public function testExecuteSqliteWithoutName(): void
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

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            str_replace('dbname', $path, 'Created database dbname for connection named default.'.PHP_EOL),
            $output->fetch()
        );
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

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);
        $command->run($input, $output);
    }

    public function testExecutepgsqlWithName(): void
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
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, $output));

        self::assertSame(
            str_replace('dbname', $dbName, 'Created database "dbname" for connection named sample.'.PHP_EOL),
            $output->fetch()
        );
    }

    public function testExecutepgsqlWithNameDbExists(): void
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
            Call::create('getConnection')->with('sample')->willReturn($connection),
        ]);

        $input = new ArrayInput([
            '--connection' => 'sample',
        ]);

        $output = new BufferedOutput();

        $command = new CreateDatabaseDoctrineCommand($connectionRegistry);

        self::assertSame(0, $command->run($input, new BufferedOutput()));
        self::assertSame(1, $command->run($input, $output));

        $message = <<<'EOT'
Could not create database "dbname".
An exception occurred while executing 'CREATE DATABASE "dbname"':
EOT;

        self::assertStringStartsWith(str_replace('dbname', $dbName, $message), $output->fetch());
    }

    public function testExecutepgsqlWithNameDbExistsAndIfNotExistsTrue(): void
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
            str_replace(
                'dbname',
                $dbName,
                'Database "dbname" for connection named sample already exists. Skipped.'.PHP_EOL
            ),
            $output->fetch()
        );
    }
}
