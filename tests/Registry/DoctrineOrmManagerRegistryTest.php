<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Registry;

use Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineOrmManagerRegistry;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineOrmManagerRegistry
 *
 * @internal
 */
final class DoctrineOrmManagerRegistryTest extends TestCase
{
    use MockByCallsTrait;

    public function testGetDefaultConnectionName(): void
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

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
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($connection, $registry->getConnection());
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
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->getConnection();
    }

    public function testGetConnections(): void
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

        $registry = new DoctrineOrmManagerRegistry($container);

        $connections = $registry->getConnections();

        self::assertIsArray($connections);

        self::assertCount(1, $connections);

        self::assertSame($connection, $connections['default']);
    }

    public function testGetConnectionNames(): void
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

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame(['default'], $registry->getConnectionNames());
    }

    public function testGetDefaultManagerName(): void
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame('default', $registry->getDefaultManagerName());
    }

    public function testGetManager(): void
    {
        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($manager, $registry->getManager());
    }

    public function testGetResetedManager(): void
    {
        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        $propertyReflection = new \ReflectionProperty($registry, 'resetedManagers');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($registry, ['default' => $manager]);

        self::assertSame($manager, $registry->getManager());
    }

    public function testGetMissingManager(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing manager with name "default".');

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(false),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->getManager();
    }

    public function testGetManagers(): void
    {
        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        $managers = $registry->getManagers();

        self::assertIsArray($managers);

        self::assertCount(1, $managers);

        self::assertSame($manager, $managers['default']);
    }

    public function testGetResetedManagers(): void
    {
        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        $propertyReflection = new \ReflectionProperty($registry, 'resetedManagers');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($registry, ['default' => $manager]);

        $managers = $registry->getManagers();

        self::assertIsArray($managers);

        self::assertCount(1, $managers);

        self::assertSame($manager, $managers['default']);
    }

    public function testGetManagerNames(): void
    {
        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame(['default'], $registry->getManagerNames());
    }

    public function testResetManager(): void
    {
        /** @var EventManager|MockObject $eventManager */
        $eventManager = $this->getMockByCalls(EventManager::class);

        /** @var MappingDriver|MockObject $mappingDriver */
        $mappingDriver = $this->getMockByCalls(MappingDriver::class);

        /** @var Connection|MockObject $connection */
        $connection = $this->getMockByCalls(Connection::class, [
            Call::create('getEventManager')->with()->willReturn($eventManager),
            Call::create('getEventManager')->with()->willReturn($eventManager),
        ]);

        /** @var Configuration|MockObject $configuration */
        $configuration = $this->getMockBuilder(Configuration::class)->disableOriginalConstructor()->getMock();
        $configuration->expects(self::once())->method('getMetadataDriverImpl')->willReturn($mappingDriver);
        $configuration->expects(self::once())->method('getClassMetadataFactoryName')->willReturn(ClassMetadataFactory::class);
        $configuration->expects(self::once())->method('getProxyDir')->willReturn(sys_get_temp_dir());
        $configuration->expects(self::once())->method('getProxyNamespace')->willReturn('DoctrineProxy');

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getConnection')->with()->willReturn($connection),
            Call::create('getConfiguration')->with()->willReturn($configuration),
            Call::create('getEventManager')->with()->willReturn($eventManager),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
            Call::create('offsetGet')->with('doctrine.orm.em.factory')->willReturn(
                function (Connection $connection, Configuration $config, EventManager $eventManager) {
                    return EntityManager::create($connection, $config, $eventManager);
                }
            ),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->resetManager();
    }

    public function testResetMissingManager(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing manager with name "default".');

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(false),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->resetManager();
    }

    public function testGetAliasNamespace(): void
    {
        /** @var Configuration|MockObject $configuration */
        $configuration = $this->getMockByCalls(Configuration::class, [
            Call::create('getEntityNamespace')->with('alias')->willReturn('original'),
        ]);

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getConfiguration')->with()->willReturn($configuration),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame('original', $registry->getAliasNamespace('alias'));
    }

    public function testGetAliasNamespaceWithOrmException(): void
    {
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Unknown Entity namespace alias \'alias\'.');

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getConfiguration')->with()->willThrowException(new ORMException('')),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame('original', $registry->getAliasNamespace('alias'));
    }

    public function testGetRepository(): void
    {
        /** @var EntityRepository|MockObject $repository */
        $repository = $this->getMockByCalls(EntityRepository::class);

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getRepository')->with(\stdClass::class)->willReturn($repository),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($repository, $registry->getRepository(\stdClass::class, 'default'));
    }

    public function testGetManagerForClassFound(): void
    {
        /** @var ClassMetadataFactory|MockObject $classMetadataFactory */
        $classMetadataFactory = $this->getMockByCalls(ClassMetadataFactory::class, [
            Call::create('isTransient')->with(Sample::class)->willReturn(false),
        ]);

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getMetadataFactory')->with()->willReturn($classMetadataFactory),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($manager, $registry->getManagerForClass(SampleProxy::class));
    }

    public function testGetManagerForClassNotFound(): void
    {
        /** @var ClassMetadataFactory|MockObject $classMetadataFactory */
        $classMetadataFactory = $this->getMockByCalls(ClassMetadataFactory::class, [
            Call::create('isTransient')->with(Sample::class)->willReturn(true),
        ]);

        /** @var EntityManager|MockObject $manager */
        $manager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getMetadataFactory')->with()->willReturn($classMetadataFactory),
        ]);

        /** @var Container|MockObject $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertNull($registry->getManagerForClass(SampleProxy::class));
    }
}

class Sample
{
}

final class SampleProxy extends Sample implements Proxy
{
    public function __load(): void
    {
    }

    /**
     * @return bool
     */
    public function __isInitialized()
    {
        return false;
    }
}
