<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Registry;

use Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineDbalConnectionRegistry;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineDbalConnectionRegistry
 *
 * @internal
 */
final class DoctrineDbalConnectionRegistryTest extends TestCase
{
    use MockByCallsTrait;

    public function testGetDefaultConnectionName(): void
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn(['default']),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame('default', $registry->getDefaultConnectionName());
    }

    public function testGetConnection(): void
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($connection),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn(['default']),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame($connection, $registry->getConnection());
    }

    public function testGetConnectionByName(): void
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('somename')->willReturn(true),
                    Call::create('offsetGet')->with('somename')->willReturn($connection),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn(['default', 'somename']),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame($connection, $registry->getConnection('somename'));
    }

    public function testGetMissingConnection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing connection with name "default".');

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(false),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn([]),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);
        $registry->getConnection();
    }

    public function testGetConnections(): void
    {
        /** @var Connection|MockObject $connection1 */
        $connection1 = $this->getMockByCalls(Connection::class);

        /** @var Connection|MockObject $connection2 */
        $connection2 = $this->getMockByCalls(Connection::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetGet')->with('default')->willReturn($connection1),
                    Call::create('offsetGet')->with('somename')->willReturn($connection2),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn(['default', 'somename']),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        $connections = $registry->getConnections();

        self::assertIsArray($connections);

        self::assertCount(2, $connections);

        self::assertSame($connection1, $connections['default']);
        self::assertSame($connection2, $connections['somename']);
    }

    public function testGetConnectionNames(): void
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class)
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.name')->willReturn(['default']),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame(['default'], $registry->getConnectionNames());
    }
}
