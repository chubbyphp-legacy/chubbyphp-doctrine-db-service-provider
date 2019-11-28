<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\ServiceFactory;

use Chubbyphp\Container\Container;
use Chubbyphp\Container\ContainerInterface;
use Chubbyphp\DoctrineDbServiceProvider\Logger\DoctrineDbalLogger;
use Chubbyphp\DoctrineDbServiceProvider\Registry\Psr\DoctrineDbalConnectionRegistry;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;

final class DoctrineDbalServiceFactory
{
    public function __invoke(): array
    {
        return [
            'doctrine.dbal.connection_registry' => $this->getDbConnectionRegistryDefintion(),
            'doctrine.dbal.db' => $this->getDbDefinition(),
            'doctrine.dbal.db.cache_factory.apcu' => $this->getDbApcuCacheFactoryDefinition(),
            'doctrine.dbal.db.cache_factory.array' => $this->getDbArrayCacheFactoryDefinition(),
            'doctrine.dbal.db.config' => $this->getDbConfigDefinition(),
            'doctrine.dbal.db.default_options' => $this->getDbDefaultOptions(),
            'doctrine.dbal.db.event_manager' => $this->getDbEventManagerDefinition(),
            'doctrine.dbal.dbs' => $this->getDbsDefinition(),
            'doctrine.dbal.dbs.config' => $this->getDbsConfigDefinition(),
            'doctrine.dbal.dbs.event_manager' => $this->getDbsEventManagerDefinition(),
            'doctrine.dbal.dbs.name' => $this->getDbsNameDefinition(),
            'doctrine.dbal.dbs.options.initializer' => $this->getDbsOptionsInitializerDefinition(),
            'doctrine.dbal.types' => $this->getTypesDefinition(),
        ];
    }

    private function getDbConnectionRegistryDefintion(): \Closure
    {
        return static function (ContainerInterface $container) {
            return new DoctrineDbalConnectionRegistry($container);
        };
    }

    private function getDbDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            /** @var Container $dbs */
            $dbs = $container->get('doctrine.dbal.dbs');

            return $dbs->get($container->get('doctrine.dbal.dbs.default'));
        };
    }

    private function getDbApcuCacheFactoryDefinition(): \Closure
    {
        return static function () {
            return static function () {
                return new ApcuCache();
            };
        };
    }

    private function getDbArrayCacheFactoryDefinition(): \Closure
    {
        return static function () {
            return static function () {
                return new ArrayCache();
            };
        };
    }

    private function getDbConfigDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            /** @var Container $dbsConfigs */
            $dbsConfigs = $container->get('doctrine.dbal.dbs.config');

            return $dbsConfigs->get($container->get('doctrine.dbal.dbs.default'));
        };
    }

    private function getDbDefaultOptions(): \Closure
    {
        return static function () {
            return [
                'configuration' => [
                    'auto_commit' => true,
                    'cache.result' => ['type' => 'array'],
                    'filter_schema_assets_expression' => null, // @deprecated
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
            ];
        };
    }

    private function getDbEventManagerDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            /** @var Container $dbEvents */
            $dbEvents = $container->get('doctrine.dbal.dbs.event_manager');

            return $dbEvents->get($container->get('doctrine.dbal.dbs.default'));
        };
    }

    private function getDbsDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            $container->get('doctrine.dbal.dbs.options.initializer')();

            $dbs = new Container();
            foreach ($container->get('doctrine.dbal.dbs.options') as $name => $options) {
                if ($container->get('doctrine.dbal.dbs.default') === $name) {
                    $config = $container->get('doctrine.dbal.db.config');
                    $manager = $container->get('doctrine.dbal.db.event_manager');
                } else {
                    $config = $container->get('doctrine.dbal.dbs.config')->get($name);
                    $manager = $container->get('doctrine.dbal.dbs.event_manager')->get($name);
                }

                $dbs->factory($name, static function () use ($options, $config, $manager) {
                    return DriverManager::getConnection($options['connection'], $config, $manager);
                });
            }

            return $dbs;
        };
    }

    private function getDbsConfigDefinition(): \Closure
    {
        return function (ContainerInterface $container) {
            $container->get('doctrine.dbal.dbs.options.initializer')();

            $logger = $container->has('logger') ? $container->get('logger') : null;

            $configs = new Container();
            foreach ($container->get('doctrine.dbal.dbs.options') as $name => $options) {
                $configs->factory($name, function () use ($logger, $container, $options) {
                    $configOptions = $options['configuration'];

                    $config = new Configuration();

                    if (null !== $logger) {
                        $config->setSQLLogger(new DoctrineDbalLogger($logger));
                    }

                    $config->setResultCacheImpl($this->getCache($container, $configOptions['cache.result']));

                    if (null !== $configOptions['filter_schema_assets_expression']) {
                        // @deprecated
                        $config->setFilterSchemaAssetsExpression($configOptions['filter_schema_assets_expression']);
                    }

                    if (null !== $configOptions['schema_assets_filter']) {
                        $config->setSchemaAssetsFilter($configOptions['schema_assets_filter']);
                    }

                    $config->setAutoCommit($configOptions['auto_commit']);

                    return $config;
                });
            }

            return $configs;
        };
    }

    private function getCache(ContainerInterface $container, array $cacheDefinition): Cache
    {
        $cacheType = $cacheDefinition['type'];
        $cacheOptions = $cacheDefinition['options'] ?? [];

        $cacheFactory = $container->get(sprintf('doctrine.dbal.db.cache_factory.%s', $cacheType));

        return $cacheFactory($cacheOptions);
    }

    private function getDbsEventManagerDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            $container->get('doctrine.dbal.dbs.options.initializer')();

            $managers = new Container();
            foreach ($container->get('doctrine.dbal.dbs.name') as $name) {
                $managers->factory((string) $name, static function () {
                    return new EventManager();
                });
            }

            return $managers;
        };
    }

    private function getDbsNameDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            $container->get('doctrine.dbal.dbs.options.initializer')();

            return array_keys($container->get('doctrine.dbal.dbs.options'));
        };
    }

    private function getDbsOptionsInitializerDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            return static function () use ($container): void {
                static $initialized = false;

                if ($initialized) {
                    return;
                }

                $initialized = true;

                foreach ($container->get('doctrine.dbal.types') as $typeName => $typeClass) {
                    if (Type::hasType($typeName)) {
                        Type::overrideType($typeName, $typeClass);
                    } else {
                        Type::addType($typeName, $typeClass);
                    }
                }

                if (!$container->has('doctrine.dbal.dbs.options')) {
                    $container->factory(
                        'doctrine.dbal.dbs.options',
                        static function (ContainerInterface $container) {
                            return [
                                'default' => $container->has('doctrine.dbal.db.options')
                                    ? $container->get('doctrine.dbal.db.options')
                                    : [],
                            ];
                        }
                    );
                }

                $tmp = $container->get('doctrine.dbal.dbs.options');
                foreach ($tmp as $name => &$options) {
                    $options = array_replace_recursive(
                        $container->get('doctrine.dbal.db.default_options'),
                        $options
                    );

                    if (!$container->has('doctrine.dbal.dbs.default')) {
                        $container->factory('doctrine.dbal.dbs.default', static function () use ($name) {
                            return $name;
                        });
                    }
                }

                $container->factory('doctrine.dbal.dbs.options', static function () use ($tmp) {
                    return $tmp;
                });
            };
        };
    }

    private function getTypesDefinition(): \Closure
    {
        return static function () {
            return [];
        };
    }
}
