<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\ServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Logger\DoctrineDbalLogger;
use Chubbyphp\DoctrineDbServiceProvider\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\ServiceProvider\DoctrineDbalServiceProvider
 *
 * @internal
 */
final class DoctrineDbalServiceProviderTest extends TestCase
{
    use MockByCallsTrait;

    public function testRegisterWithDefaults(): void
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        self::assertTrue($container->offsetExists('doctrine.dbal.connection_registry'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.default_options'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.options.initializer'));
        self::assertTrue($container->offsetExists('doctrine.dbal.types'));

        // start: doctrine.dbal.connection_registry
        self::assertInstanceOf(ConnectionRegistry::class, $container['doctrine.dbal.connection_registry']);

        /** @var ConnectionRegistry $managerRegistry */
        $managerRegistry = $container['doctrine.dbal.connection_registry'];

        self::assertSame('default', $managerRegistry->getDefaultConnectionName());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnection());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnections()['default']);
        self::assertSame(['default'], $managerRegistry->getConnectionNames());
        // end: doctrine.dbal.connection_registry

        // start: doctrine.dbal.db
        self::assertInstanceOf(Connection::class, $container['doctrine.dbal.db']);

        self::assertSame($container['doctrine.dbal.db'], $container['doctrine.dbal.dbs']['default']);

        /** @var Connection $connection */
        $connection = $container['doctrine.dbal.db'];

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
        self::assertInstanceOf(Configuration::class, $container['doctrine.dbal.db.config']);

        self::assertSame($container['doctrine.dbal.db.config'], $container['doctrine.dbal.dbs.config']['default']);

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.db.config'];

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
        ], $container['doctrine.dbal.db.default_options']);
        // end: doctrine.dbal.db.default_options

        // start: doctrine.dbal.db.event_manager
        self::assertInstanceOf(EventManager::class, $container['doctrine.dbal.db.event_manager']);

        self::assertSame(
            $container['doctrine.dbal.db.event_manager'],
            $container['doctrine.dbal.dbs.event_manager']['default']
        );
        // end: doctrine.dbal.db.event_manager

        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs']);
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.config']);
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.event_manager']);
        self::assertSame(['default'], $container['doctrine.dbal.dbs.name']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.dbal.dbs.options.initializer']);
        self::assertSame([], $container['doctrine.dbal.types']);
    }

    public function testRegisterWithOneConnetion(): void
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.dbal.types'] = [
            Type::STRING => IntegerType::class,
            'anotherType'.uniqid() => StringType::class,
        ];

        $container['doctrine.dbal.db.cache_factory.filesystem'] = $container->protect(
            function (array $options) use ($container) {
                return new FilesystemCache($options['directory']);
            }
        );

        $directory = sys_get_temp_dir();

        $container['doctrine.dbal.db.options'] = [
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
        ];

        /** @var Connection $db */
        $db = $container['doctrine.dbal.db'];

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
        $config = $container['doctrine.dbal.db.config'];

        /** @var FilesystemCache $resultCache */
        $resultCache = $config->getResultCacheImpl();

        self::assertInstanceOf(FilesystemCache::class, $resultCache);

        self::assertSame($directory, $resultCache->getDirectory());
    }

    public function testRegisterWithMultipleConnetions(): void
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.dbal.dbs.options'] = [
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
                    'schema_assets_filter' => function (string $assetName) { return preg_match('/^.*$/', $assetName); },
                ],
                'connection' => [
                    'dbname' => 'my_database',
                    'host' => 'mysql_write.someplace.tld',
                    'password' => 'my_password',
                    'user' => 'my_username',
                ],
            ],
        ];

        self::assertSame(['mysql_read', 'mysql_write'], $container['doctrine.dbal.dbs.name']);

        self::assertFalse($container['doctrine.dbal.dbs']->offsetExists('default'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_read'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_write'));

        /** @var Connection $dbRead */
        $dbRead = $container['doctrine.dbal.dbs']['mysql_read'];

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
        $configurationReadDb = $container['doctrine.dbal.dbs.config']['mysql_read'];

        $schemaAssetFilterReadDb = $configurationReadDb->getSchemaAssetsFilter();

        self::assertInstanceOf(DoctrineDbalLogger::class, $configurationReadDb->getSQLLogger());
        self::assertInstanceOf(ApcuCache::class, $configurationReadDb->getResultCacheImpl());
        self::assertSame('/^.*$/', $configurationReadDb->getFilterSchemaAssetsExpression());
        self::assertIsCallable($schemaAssetFilterReadDb);
        self::assertSame(1, $schemaAssetFilterReadDb('assetName'));
        self::assertFalse($configurationReadDb->getAutoCommit());

        /** @var Connection $dbWrite */
        $dbWrite = $container['doctrine.dbal.dbs']['mysql_write'];

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
        $configurationWriteDb = $container['doctrine.dbal.dbs.config']['mysql_write'];

        $schemaAssetFilterWriteDb = $configurationWriteDb->getSchemaAssetsFilter();

        self::assertInstanceOf(DoctrineDbalLogger::class, $configurationWriteDb->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configurationWriteDb->getResultCacheImpl());
        self::assertNull($configurationWriteDb->getFilterSchemaAssetsExpression());
        self::assertIsCallable($schemaAssetFilterWriteDb);
        self::assertSame(1, $schemaAssetFilterWriteDb('assetName'));
        self::assertTrue($configurationWriteDb->getAutoCommit());
    }
}
