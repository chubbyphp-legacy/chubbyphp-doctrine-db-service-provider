<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\ServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\Registry\DoctrineOrmManagerRegistry;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
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
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapDriver;

/**
 * This provider is heavily inspired by
 * https://github.com/dflydev/dflydev-doctrine-orm-service-provider/blob/master/src/Dflydev/Provider/DoctrineOrm/DoctrineOrmServiceProvider.php.
 */
final class DoctrineOrmServiceProvider implements ServiceProviderInterface
{
    /**
     * Register ORM service.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['doctrine.orm.em'] = $this->getOrmEmDefinition($container);
        $container['doctrine.orm.em.config'] = $this->getOrmEmConfigDefinition($container);
        $container['doctrine.orm.em.default_options'] = $this->getOrmEmDefaultOptions();
        $container['doctrine.orm.ems'] = $this->getOrmEmsDefinition($container);
        $container['doctrine.orm.ems.config'] = $this->getOrmEmsConfigServiceProvider($container);
        $container['doctrine.orm.ems.options.initializer'] = $this->getOrmEmsOptionsInitializerDefinition($container);
        $container['doctrine.orm.entity.listener_resolver.default'] = $this->getOrmEntityListenerResolverDefinition($container);
        $container['doctrine.orm.manager_registry'] = $this->getOrmManagerRegistryDefintion($container);
        $container['doctrine.orm.mapping_driver.factory.annotation'] = $this->getOrmMappingDriverFactoryAnnotation($container);
        $container['doctrine.orm.mapping_driver.factory.class_map'] = $this->getOrmMappingDriverFactoryClassMap($container);
        $container['doctrine.orm.mapping_driver.factory.simple_xml'] = $this->getOrmMappingDriverFactorySimpleXml($container);
        $container['doctrine.orm.mapping_driver.factory.simple_yaml'] = $this->getOrmMappingDriverFactorySimpleYaml($container);
        $container['doctrine.orm.mapping_driver.factory.static_php'] = $this->getOrmMappingDriverFactoryStaticPhp($container);
        $container['doctrine.orm.mapping_driver.factory.xml'] = $this->getOrmMappingDriverFactoryXml($container);
        $container['doctrine.orm.mapping_driver.factory.yaml'] = $this->getOrmMappingDriverFactoryYaml($container);
        $container['doctrine.orm.mapping_driver_chain'] = $this->getOrmMappingDriverChainDefinition($container);
        $container['doctrine.orm.repository.factory.default'] = $this->getOrmRepositoryFactoryDefinition($container);
        $container['doctrine.orm.strategy.naming.default'] = $this->getOrmNamingStrategyDefinition($container);
        $container['doctrine.orm.strategy.quote.default'] = $this->getOrmQuoteStrategyDefinition($container);
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmDefinition(Container $container): callable
    {
        return function () use ($container) {
            $ems = $container['doctrine.orm.ems'];

            return $ems[$container['doctrine.orm.ems.default']];
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $configs = $container['doctrine.orm.ems.config'];

            return $configs[$container['doctrine.orm.ems.default']];
        };
    }

    /**
     * @return array
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

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmsDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $ems = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
                if ($container['doctrine.orm.ems.default'] === $name) {
                    $config = $container['doctrine.orm.em.config'];
                } else {
                    $config = $container['doctrine.orm.ems.config'][$name];
                }

                $ems[$name] = function () use ($container, $options, $config) {
                    return EntityManager::create(
                        $container['doctrine.dbal.dbs'][$options['connection']],
                        $config,
                        $container['doctrine.dbal.dbs.event_manager'][$options['connection']]
                    );
                };
            }

            return $ems;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmsConfigServiceProvider(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $configs = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
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

                $configs[$name] = $config;
            }

            return $configs;
        };
    }

    /**
     * @param Container    $container
     * @param string|array $cacheDefinition
     *
     * @return Cache
     */
    private function getCache(Container $container, $cacheDefinition): Cache
    {
        $cacheType = $cacheDefinition['type'];
        $cacheOptions = $cacheDefinition['options'] ?? [];

        $cacheFactory = $container[sprintf('doctrine.dbal.db.cache_factory.%s', $cacheType)];

        return $cacheFactory($cacheOptions);
    }

    /**
     * @param Container     $container
     * @param Configuration $config
     * @param array         $options
     */
    private function assignSecondLevelCache(Container $container, Configuration $config, array $options)
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

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmsOptionsInitializerDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['doctrine.orm.ems.options'])) {
                $container['doctrine.orm.ems.options'] = [
                    'default' => isset($container['doctrine.orm.em.options']) ?
                        $container['doctrine.orm.em.options'] : [],
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

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEntityListenerResolverDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultEntityListenerResolver();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmManagerRegistryDefintion(Container $container): callable
    {
        return function ($container) {
            return new DoctrineOrmManagerRegistry($container);
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryAnnotation(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return $config->newDefaultAnnotationDriver((array) $mapping['path'], false);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryClassMap(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new ClassMapDriver($mapping['map']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryStaticPhp(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new StaticPHPDriver($mapping['path']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleYaml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new SimplifiedYamlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedYamlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleXml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new SimplifiedXmlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryYaml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new YamlDriver($mapping['path'], $mapping['extension'] ?? YamlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryXml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new XmlDriver($mapping['path'], $mapping['extension'] ?? XmlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverChainDefinition(Container $container): callable
    {
        return $container->protect(function (Configuration $config, array $mappings) use ($container) {
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

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmRepositoryFactoryDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultRepositoryFactory();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmNamingStrategyDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultNamingStrategy();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmQuoteStrategyDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultQuoteStrategy();
        };
    }
}
