<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\ServiceProvider;

use Chubbyphp\Container\Container;
use Chubbyphp\DoctrineDbServiceProvider\Logger\DoctrineDbalLogger;
use Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineDbalServiceFactory;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineDbalServiceFactory
 *
 * @internal
 */
final class DoctrineDbalServiceFactoryTest extends TestCase
{
    use MockByCallsTrait;

    public function testRegisterWithDefaults(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());

        self::assertTrue($container->has('doctrine.dbal.connection_registry'));
        self::assertTrue($container->has('doctrine.dbal.db'));
        self::assertTrue($container->has('doctrine.dbal.db.config'));
        self::assertTrue($container->has('doctrine.dbal.db.default_options'));
        self::assertTrue($container->has('doctrine.dbal.db.event_manager'));
        self::assertTrue($container->has('doctrine.dbal.dbs'));
        self::assertTrue($container->has('doctrine.dbal.dbs.config'));
        self::assertTrue($container->has('doctrine.dbal.dbs.event_manager'));
        self::assertTrue($container->has('doctrine.dbal.dbs.options.initializer'));
        self::assertTrue($container->has('doctrine.dbal.types'));

        // start: doctrine.dbal.connection_registry

        /** @var ConnectionRegistry $connectionRegistry */
        $connectionRegistry = $container->get('doctrine.dbal.connection_registry');

        self::assertInstanceOf(ConnectionRegistry::class, $connectionRegistry);

        self::assertSame('default', $connectionRegistry->getDefaultConnectionName());
        self::assertSame($container->get('doctrine.dbal.db'), $connectionRegistry->getConnection());
        self::assertSame($container->get('doctrine.dbal.db'), $connectionRegistry->getConnections()['default']);
        self::assertSame(['default'], $connectionRegistry->getConnectionNames());
        // end: doctrine.dbal.connection_registry

        // start: doctrine.dbal.db
        /** @var Connection $connection */
        $connection = $container->get('doctrine.dbal.db');

        self::assertInstanceOf(Connection::class, $connection);

        self::assertSame($connection, $container->get('doctrine.dbal.dbs')->get('default'));

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => null,
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'password' => null,
            'path' => null,
            'port' => 3306,
            'user' => 'root',
        ], $connection->getParams());
        // end: doctrine.dbal.db

        // start: doctrine.dbal.db.config
        /** @var Configuration $configuration */
        $configuration = $container->get('doctrine.dbal.db.config');

        self::assertInstanceOf(Configuration::class, $configuration);

        self::assertSame($configuration, $container->get('doctrine.dbal.dbs.config')->get('default'));

        self::assertNull($configuration->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configuration->getResultCacheImpl());
        self::assertNull($configuration->getFilterSchemaAssetsExpression());
        self::assertTrue($configuration->getAutoCommit());
        // end: doctrine.dbal.db.config

        // start: doctrine.dbal.db.default_options
        self::assertEquals([
            'configuration' => [
                'auto_commit' => true,
                'cache.result' => ['type' => 'array'],
                'filter_schema_assets_expression' => null,
                'schema_assets_filter' => null,
            ],
            'connection' => [
                'charset' => 'utf8mb4',
                'dbname' => null,
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'password' => null,
                'path' => null,
                'port' => 3306,
                'user' => 'root',
            ],
            'eventManager' => [
                'listener' => [],
                'subscriber' => [],
            ],
        ], $container->get('doctrine.dbal.db.default_options'));
        // end: doctrine.dbal.db.default_options

        // start: doctrine.dbal.db.event_manager
        /** @var EventManager $eventManager */
        $eventManager = $container->get('doctrine.dbal.db.event_manager');

        self::assertInstanceOf(EventManager::class, $eventManager);

        self::assertSame(
            $eventManager,
            $container->get('doctrine.dbal.dbs.event_manager')->get('default')
        );
        // end: doctrine.dbal.db.event_manager

        self::assertInstanceOf(Container::class, $container->get('doctrine.dbal.dbs'));
        self::assertInstanceOf(Container::class, $container->get('doctrine.dbal.dbs.config'));
        self::assertInstanceOf(Container::class, $container->get('doctrine.dbal.dbs.event_manager'));
        self::assertSame(['default'], $container->get('doctrine.dbal.dbs.name'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.dbal.dbs.options.initializer'));
        self::assertSame([], $container->get('doctrine.dbal.types'));
    }

    public function testRegisterWithOneConnetion(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());

        $container->factory('logger', fn () => $this->getMockByCalls(LoggerInterface::class));

        $container->factory('listener1', static fn () => new \stdClass());

        $container->factory('subscriber1', function () {
            return $this->getMockByCalls(EventSubscriber::class, [
                Call::create('getSubscribedEvents')->willReturn(['event3', 'event4']),
            ]);
        });

        $container->factory('doctrine.dbal.types', static function () {
            return [
                Type::STRING => IntegerType::class,
                'anotherType'.uniqid() => StringType::class,
            ];
        });

        $container->factory('doctrine.dbal.db.cache_factory.filesystem', static fn () => static fn (array $options) => new FilesystemCache($options['directory']));

        $directory = sys_get_temp_dir();

        $container->factory('doctrine.dbal.db.options', static function () use ($directory) {
            return [
                'configuration' => [
                    'auto_commit' => true,
                    'cache.result' => ['type' => 'filesystem', 'options' => ['directory' => $directory]],
                ],
                'connection' => [
                    'dbname' => 'my_database',
                    'host' => 'mysql.someplace.tld',
                    'password' => 'my_password',
                    'user' => 'my_username',
                ],
                'eventManager' => [
                    'listener' => [
                        ['events' => ['event1', 'event2'], 'listener' => 'listener1'],
                    ],
                    'subscriber' => [
                        'subscriber1',
                    ],
                ],
            ];
        });

        /** @var Connection $db */
        $db = $container->get('doctrine.dbal.db');

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $db->getParams());

        /** @var Configuration $config */
        $config = $container->get('doctrine.dbal.db.config');

        /** @var FilesystemCache $resultCache */
        $resultCache = $config->getResultCacheImpl();

        self::assertInstanceOf(FilesystemCache::class, $resultCache);

        self::assertSame($directory, $resultCache->getDirectory());

        /** @var EventManager $eventManager */
        $eventManager = $container->get('doctrine.dbal.db.event_manager');

        $listeners = $eventManager->getListeners();

        self::assertCount(4, $listeners);

        self::assertSame($container->get('listener1'), array_shift($listeners['event1']));
        self::assertSame($container->get('listener1'), array_shift($listeners['event2']));
        self::assertSame($container->get('subscriber1'), array_shift($listeners['event3']));
        self::assertSame($container->get('subscriber1'), array_shift($listeners['event4']));
    }

    public function testRegisterWithMultipleConnetions(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());

        $container->factory('logger', fn () => $this->getMockByCalls(LoggerInterface::class));

        $container->factory('doctrine.dbal.dbs.options', static function () {
            return [
                'mysql_read' => [
                    'configuration' => [
                        'auto_commit' => false,
                        'cache.result' => ['type' => 'apcu'],
                        'filter_schema_assets_expression' => '/^.*$/',
                    ],
                    'connection' => [
                        'dbname' => 'my_database',
                        'host' => 'mysql_read.someplace.tld',
                        'password' => 'my_password',
                        'user' => 'my_username',
                    ],
                ],
                'mysql_write' => [
                    'configuration' => [
                        'cache.result' => ['type' => 'array'],
                        'schema_assets_filter' => static fn (string $assetName) => preg_match('/^.*$/', $assetName),
                    ],
                    'connection' => [
                        'dbname' => 'my_database',
                        'host' => 'mysql_write.someplace.tld',
                        'password' => 'my_password',
                        'user' => 'my_username',
                    ],
                ],
            ];
        });

        self::assertSame(['mysql_read', 'mysql_write'], $container->get('doctrine.dbal.dbs.name'));

        self::assertFalse($container->get('doctrine.dbal.dbs')->has('default'));
        self::assertTrue($container->get('doctrine.dbal.dbs')->has('mysql_read'));
        self::assertTrue($container->get('doctrine.dbal.dbs')->has('mysql_write'));

        /** @var Connection $dbRead */
        $dbRead = $container->get('doctrine.dbal.dbs')->get('mysql_read');

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql_read.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $dbRead->getParams());

        /** @var Configuration $configurationReadDb */
        $configurationReadDb = $container->get('doctrine.dbal.dbs.config')->get('mysql_read');

        $schemaAssetFilterReadDb = $configurationReadDb->getSchemaAssetsFilter();

        self::assertInstanceOf(DoctrineDbalLogger::class, $configurationReadDb->getSQLLogger());
        self::assertInstanceOf(ApcuCache::class, $configurationReadDb->getResultCacheImpl());
        self::assertSame('/^.*$/', $configurationReadDb->getFilterSchemaAssetsExpression());
        self::assertIsCallable($schemaAssetFilterReadDb);
        self::assertSame(1, $schemaAssetFilterReadDb('assetName'));
        self::assertFalse($configurationReadDb->getAutoCommit());

        /** @var Connection $dbWrite */
        $dbWrite = $container->get('doctrine.dbal.dbs')->get('mysql_write');

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql_write.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $dbWrite->getParams());

        /** @var Configuration $configurationWriteDb */
        $configurationWriteDb = $container->get('doctrine.dbal.dbs.config')->get('mysql_write');

        $schemaAssetFilterWriteDb = $configurationWriteDb->getSchemaAssetsFilter();

        self::assertInstanceOf(DoctrineDbalLogger::class, $configurationWriteDb->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configurationWriteDb->getResultCacheImpl());
        self::assertNull($configurationWriteDb->getFilterSchemaAssetsExpression());
        self::assertIsCallable($schemaAssetFilterWriteDb);
        self::assertSame(1, $schemaAssetFilterWriteDb('assetName'));
        self::assertTrue($configurationWriteDb->getAutoCommit());
    }
}
