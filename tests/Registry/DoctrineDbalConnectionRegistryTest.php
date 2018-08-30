<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineDbalConnectionRegistry;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineDbalConnectionRegistry
 */
class DoctrineDbalConnectionRegistryTest extends TestCase
{
    use MockByCallsTrait;

    public function testGetDefaultConnectionName()
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame('default', $registry->getDefaultConnectionName());
    }

    public function testGetConnection()
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
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame($connection, $registry->getConnection());
    }

    public function testGetMissingConnection()
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
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);
        $registry->getConnection();
    }

    public function testGetConnections()
    {
        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetGet')->with('default')->willReturn($connection),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        $connections = $registry->getConnections();

        self::assertInternalType('array', $connections);

        self::assertCount(1, $connections);

        self::assertSame($connection, $connections['default']);
    }

    public function testGetConnectionNames()
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineDbalConnectionRegistry($container);

        self::assertSame(['default'], $registry->getConnectionNames());
    }
}
