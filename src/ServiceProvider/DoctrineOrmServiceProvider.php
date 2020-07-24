<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\ServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapDriver;
use Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineOrmManagerRegistry;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * This provider is heavily inspired by
 * https://github.com/dflydev/dflydev-doctrine-orm-service-provider/blob/master/src/Dflydev/Provider/DoctrineOrm/DoctrineOrmServiceProvider.php.
 */
final class DoctrineOrmServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container['doctrine.orm.em'] = $this->getOrmEmDefinition($container);
        $container['doctrine.orm.em.config'] = $this->getOrmEmConfigDefinition($container);
        $container['doctrine.orm.em.default_options'] = $this->getOrmEmDefaultOptions();
        $container['doctrine.orm.em.factory'] = $this->getOrmEmFactory($container);
        $container['doctrine.orm.ems'] = $this->getOrmEmsDefinition($container);
        $container['doctrine.orm.ems.config'] = $this->getOrmEmsConfigDefinition($container);
        $container['doctrine.orm.ems.name'] = $this->getOrmEmsNameDefinition($container);
        $container['doctrine.orm.ems.options.initializer'] = $this->getOrmEmsOptionsInitializerDefinition($container);
        $container['doctrine.orm.entity.listener_resolver.default'] = $this->getOrmEntityListenerResolverDefinition();
        $container['doctrine.orm.manager_registry'] = $this->getOrmManagerRegistryDefintion($container);
        $container['doctrine.orm.mapping_driver.factory.annotation'] =
            $this->getOrmMappingDriverFactoryAnnotation($container);
        $container['doctrine.orm.mapping_driver.factory.class_map'] =
            $this->getOrmMappingDriverFactoryClassMap($container);
        $container['doctrine.orm.mapping_driver.factory.php'] = $this->getOrmMappingDriverFactoryPhp($container);
        $container['doctrine.orm.mapping_driver.factory.simple_xml'] =
            $this->getOrmMappingDriverFactorySimpleXml($container);
        $container['doctrine.orm.mapping_driver.factory.simple_yaml'] =
            $this->getOrmMappingDriverFactorySimpleYaml($container);
        $container['doctrine.orm.mapping_driver.factory.static_php'] =
            $this->getOrmMappingDriverFactoryStaticPhp($container);
        $container['doctrine.orm.mapping_driver.factory.xml'] = $this->getOrmMappingDriverFactoryXml($container);
        $container['doctrine.orm.mapping_driver.factory.yaml'] = $this->getOrmMappingDriverFactoryYaml($container);
        $container['doctrine.orm.mapping_driver_chain'] = $this->getOrmMappingDriverChainDefinition($container);
        $container['doctrine.orm.repository.factory.default'] = $this->getOrmRepositoryFactoryDefinition();
        $container['doctrine.orm.strategy.naming.default'] = $this->getOrmNamingStrategyDefinition();
        $container['doctrine.orm.strategy.quote.default'] = $this->getOrmQuoteStrategyDefinition();
    }

    private function getOrmEmDefinition(Container $container): callable
    {
        return static function () use ($container) {
            $ems = $container['doctrine.orm.ems'];

            return $ems[$container['doctrine.orm.ems.default']];
        };
    }

    private function getOrmEmConfigDefinition(Container $container): callable
    {
        return static function () use ($container) {
            $configs = $container['doctrine.orm.ems.config'];

            return $configs[$container['doctrine.orm.ems.default']];
        };
    }

    /**
     * @return array<string, array|string|float|int|bool>
     */
    private function getOrmEmDefaultOptions(): array
    {
        return [
            'cache.hydration' => ['type' => 'array'],
            'cache.metadata' => ['type' => 'array'],
            'cache.query' => ['type' => 'array'],
            'class_metadata.factory.name' => ClassMetadataFactory::class,
            'connection' => 'default',
            'custom.functions.datetime' => [],
            'custom.functions.numeric' => [],
            'custom.functions.string' => [],
            'custom.hydration_modes' => [],
            'entity.listener_resolver' => 'default',
            'mappings' => [],
            'proxies.auto_generate' => true,
            'proxies.dir' => sys_get_temp_dir().'/doctrine/orm/proxies',
            'proxies.namespace' => 'DoctrineProxy',
            'query_hints' => [],
            'repository.default.class' => EntityRepository::class,
            'repository.factory' => 'default',
            'second_level_cache' => ['type' => 'array'],
            'second_level_cache.enabled' => false,
            'strategy.naming' => 'default',
            'strategy.quote' => 'default',
        ];
    }

    private function getOrmEmFactory(Container $container): callable
    {
        return $container->protect(
            static function (Connection $connection, Configuration $config, EventManager $eventManager) {
                return EntityManager::create($connection, $config, $eventManager);
            }
        );
    }

    private function getOrmEmsDefinition(Container $container): callable
    {
        return static function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $ems = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
                if ($container['doctrine.orm.ems.default'] === $name) {
                    $config = $container['doctrine.orm.em.config'];
                } else {
                    $config = $container['doctrine.orm.ems.config'][$name];
                }

                $ems[$name] = static function () use ($container, $options, $config) {
                    return $container['doctrine.orm.em.factory'](
                        $container['doctrine.dbal.dbs'][$options['connection']],
                        $config,
                        $container['doctrine.dbal.dbs.event_manager'][$options['connection']]
                    );
                };
            }

            return $ems;
        };
    }

    private function getOrmEmsConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $configs = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
                $configs[$name] = function () use ($container, $options) {
                    $connectionName = $options['connection'];

                    $config = new Configuration();

                    $config->setSQLLogger($container['doctrine.dbal.dbs.config'][$connectionName]->getSQLLogger());

                    $config->setQueryCacheImpl($this->getCache($container, $options['cache.query']));
                    $config->setHydrationCacheImpl($this->getCache($container, $options['cache.hydration']));
                    $config->setMetadataCacheImpl($this->getCache($container, $options['cache.metadata']));

                    $config->setResultCacheImpl(
                        $container['doctrine.dbal.dbs.config'][$connectionName]->getResultCacheImpl()
                    );

                    $config->setClassMetadataFactoryName($options['class_metadata.factory.name']);

                    $config->setCustomDatetimeFunctions($options['custom.functions.datetime']);
                    $config->setCustomHydrationModes($options['custom.hydration_modes']);
                    $config->setCustomNumericFunctions($options['custom.functions.numeric']);
                    $config->setCustomStringFunctions($options['custom.functions.string']);

                    $config->setEntityListenerResolver(
                        $container[
                            sprintf('doctrine.orm.entity.listener_resolver.%s', $options['entity.listener_resolver'])
                        ]
                    );

                    $config->setMetadataDriverImpl(
                        $container['doctrine.orm.mapping_driver_chain']($config, $options['mappings'])
                    );

                    $config->setAutoGenerateProxyClasses($options['proxies.auto_generate']);
                    $config->setProxyDir($options['proxies.dir']);
                    $config->setProxyNamespace($options['proxies.namespace']);

                    $config->setDefaultQueryHints($options['query_hints']);

                    $config->setRepositoryFactory(
                        $container[sprintf('doctrine.orm.repository.factory.%s', $options['repository.factory'])]
                    );
                    $config->setDefaultRepositoryClassName($options['repository.default.class']);

                    $this->assignSecondLevelCache($container, $config, $options);

                    $config->setNamingStrategy(
                        $container[sprintf('doctrine.orm.strategy.naming.%s', $options['strategy.naming'])]
                    );
                    $config->setQuoteStrategy(
                        $container[sprintf('doctrine.orm.strategy.quote.%s', $options['strategy.quote'])]
                    );

                    return $config;
                };
            }

            return $configs;
        };
    }

    /**
     * @param array<mixed> $cacheDefinition
     */
    private function getCache(Container $container, array $cacheDefinition): Cache
    {
        $cacheType = $cacheDefinition['type'];
        $cacheOptions = $cacheDefinition['options'] ?? [];

        $cacheFactory = $container[sprintf('doctrine.dbal.db.cache_factory.%s', $cacheType)];

        return $cacheFactory($cacheOptions);
    }

    /**
     * @param array<mixed> $options
     */
    private function assignSecondLevelCache(Container $container, Configuration $config, array $options): void
    {
        if (!$options['second_level_cache.enabled']) {
            $config->setSecondLevelCacheEnabled(false);

            return;
        }

        $regionsCacheConfiguration = new RegionsConfiguration();
        $factory = new DefaultCacheFactory(
            $regionsCacheConfiguration,
            $this->getCache($container, $options['second_level_cache'])
        );

        $cacheConfiguration = new CacheConfiguration();
        $cacheConfiguration->setCacheFactory($factory);
        $cacheConfiguration->setRegionsConfiguration($regionsCacheConfiguration);

        $config->setSecondLevelCacheEnabled(true);
        $config->setSecondLevelCacheConfiguration($cacheConfiguration);
    }

    private function getOrmEmsNameDefinition(Container $container): callable
    {
        return static function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            return array_keys($container['doctrine.orm.ems.options']);
        };
    }

    private function getOrmEmsOptionsInitializerDefinition(Container $container): callable
    {
        return $container->protect(static function () use ($container): void {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['doctrine.orm.ems.options'])) {
                $container['doctrine.orm.ems.options'] = [
                    'default' => $container['doctrine.orm.em.options'] ?? [],
                ];
            }

            $tmp = $container['doctrine.orm.ems.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container['doctrine.orm.em.default_options'], $options);

                if (!isset($container['doctrine.orm.ems.default'])) {
                    $container['doctrine.orm.ems.default'] = $name;
                }
            }

            $container['doctrine.orm.ems.options'] = $tmp;
        });
    }

    private function getOrmEntityListenerResolverDefinition(): callable
    {
        return static function () {
            return new DefaultEntityListenerResolver();
        };
    }

    private function getOrmManagerRegistryDefintion(Container $container): callable
    {
        return static function () use ($container) {
            return new DoctrineOrmManagerRegistry($container);
        };
    }

    private function getOrmMappingDriverFactoryAnnotation(Container $container): callable
    {
        return $container->protect(static function (array $mapping, Configuration $config) {
            return $config->newDefaultAnnotationDriver($mapping['path'], false);
        });
    }

    private function getOrmMappingDriverFactoryClassMap(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new ClassMapDriver($mapping['map']);
        });
    }

    private function getOrmMappingDriverFactoryPhp(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new PHPDriver($mapping['path']);
        });
    }

    private function getOrmMappingDriverFactorySimpleYaml(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new SimplifiedYamlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedYamlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    private function getOrmMappingDriverFactorySimpleXml(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new SimplifiedXmlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    private function getOrmMappingDriverFactoryStaticPhp(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new StaticPHPDriver($mapping['path']);
        });
    }

    private function getOrmMappingDriverFactoryYaml(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new YamlDriver($mapping['path'], $mapping['extension'] ?? YamlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    private function getOrmMappingDriverFactoryXml(Container $container): callable
    {
        return $container->protect(static function (array $mapping) {
            return new XmlDriver($mapping['path'], $mapping['extension'] ?? XmlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    private function getOrmMappingDriverChainDefinition(Container $container): callable
    {
        return $container->protect(static function (Configuration $config, array $mappings) use ($container) {
            $chain = new MappingDriverChain();
            foreach ($mappings as $mapping) {
                if (isset($mapping['alias'])) {
                    $config->addEntityNamespace($mapping['alias'], $mapping['namespace']);
                }

                $factoryKey = sprintf('doctrine.orm.mapping_driver.factory.%s', $mapping['type']);

                $chain->addDriver($container[$factoryKey]($mapping, $config), $mapping['namespace']);
            }

            return $chain;
        });
    }

    private function getOrmRepositoryFactoryDefinition(): callable
    {
        return static function () {
            return new DefaultRepositoryFactory();
        };
    }

    private function getOrmNamingStrategyDefinition(): callable
    {
        return static function () {
            return new DefaultNamingStrategy();
        };
    }

    private function getOrmQuoteStrategyDefinition(): callable
    {
        return static function () {
            return new DefaultQuoteStrategy();
        };
    }
}
