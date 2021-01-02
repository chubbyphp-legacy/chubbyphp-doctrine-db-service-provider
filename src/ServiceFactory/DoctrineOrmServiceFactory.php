<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\ServiceFactory;

use Chubbyphp\Container\Container;
use Chubbyphp\Container\ContainerInterface;
use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapDriver;
use Chubbyphp\DoctrineDbServiceProvider\Registry\Psr\DoctrineOrmManagerRegistry;
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

final class DoctrineOrmServiceFactory
{
    /**
     * @return array<string, callable>
     */
    public function __invoke(): array
    {
        return [
            'doctrine.orm.em' => $this->getOrmEmDefinition(),
            'doctrine.orm.em.config' => $this->getOrmEmConfigDefinition(),
            'doctrine.orm.em.default_options' => $this->getOrmEmDefaultOptions(),
            'doctrine.orm.em.factory' => $this->getOrmEmFactory(),
            'doctrine.orm.ems' => $this->getOrmEmsDefinition(),
            'doctrine.orm.ems.config' => $this->getOrmEmsConfigDefinition(),
            'doctrine.orm.ems.name' => $this->getOrmEmsNameDefinition(),
            'doctrine.orm.ems.options.initializer' => $this->getOrmEmsOptionsInitializerDefinition(),
            'doctrine.orm.entity.listener_resolver.default' => $this->getOrmEntityListenerResolverDefinition(),
            'doctrine.orm.manager_registry' => $this->getOrmManagerRegistryDefintion(),
            'doctrine.orm.mapping_driver.factory.annotation' => $this->getOrmMappingDriverFactoryAnnotation(),
            'doctrine.orm.mapping_driver.factory.class_map' => $this->getOrmMappingDriverFactoryClassMap(),
            'doctrine.orm.mapping_driver.factory.php' => $this->getOrmMappingDriverFactoryPhp(),
            'doctrine.orm.mapping_driver.factory.simple_xml' => $this->getOrmMappingDriverFactorySimpleXml(),
            'doctrine.orm.mapping_driver.factory.simple_yaml' => $this->getOrmMappingDriverFactorySimpleYaml(),
            'doctrine.orm.mapping_driver.factory.static_php' => $this->getOrmMappingDriverFactoryStaticPhp(),
            'doctrine.orm.mapping_driver.factory.xml' => $this->getOrmMappingDriverFactoryXml(),
            'doctrine.orm.mapping_driver.factory.yaml' => $this->getOrmMappingDriverFactoryYaml(),
            'doctrine.orm.mapping_driver_chain' => $this->getOrmMappingDriverChainDefinition(),
            'doctrine.orm.repository.factory.default' => $this->getOrmRepositoryFactoryDefinition(),
            'doctrine.orm.strategy.naming.default' => $this->getOrmNamingStrategyDefinition(),
            'doctrine.orm.strategy.quote.default' => $this->getOrmQuoteStrategyDefinition(),
        ];
    }

    private function getOrmEmDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            /** @var ContainerInterface $ems */
            $ems = $container->get('doctrine.orm.ems');

            return $ems->get($container->get('doctrine.orm.ems.default'));
        };
    }

    private function getOrmEmConfigDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            /** @var ContainerInterface $configs */
            $configs = $container->get('doctrine.orm.ems.config');

            return $configs->get($container->get('doctrine.orm.ems.default'));
        };
    }

    private function getOrmEmDefaultOptions(): \Closure
    {
        return static fn () => [
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

    private function getOrmEmFactory(): \Closure
    {
        return static fn () => static fn (Connection $connection, Configuration $config, EventManager $eventManager) => EntityManager::create($connection, $config, $eventManager);
    }

    private function getOrmEmsDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            $container->get('doctrine.orm.ems.options.initializer')();

            $ems = new Container();
            foreach ($container->get('doctrine.orm.ems.options') as $name => $options) {
                if ($container->get('doctrine.orm.ems.default') === $name) {
                    $config = $container->get('doctrine.orm.em.config');
                } else {
                    $config = $container->get('doctrine.orm.ems.config')->get($name);
                }

                $ems->factory($name, static fn () => $container->get('doctrine.orm.em.factory')(
                    $container->get('doctrine.dbal.dbs')->get($options['connection']),
                    $config,
                    $container->get('doctrine.dbal.dbs.event_manager')->get($options['connection'])
                ));
            }

            return $ems;
        };
    }

    private function getOrmEmsConfigDefinition(): \Closure
    {
        return function (ContainerInterface $container) {
            $container->get('doctrine.orm.ems.options.initializer')();

            $configs = new Container();
            foreach ($container->get('doctrine.orm.ems.options') as $name => $options) {
                $configs->factory($name, function () use ($container, $options) {
                    $connectionName = $options['connection'];

                    $config = new Configuration();

                    $config->setSQLLogger(
                        $container->get('doctrine.dbal.dbs.config')->get($connectionName)->getSQLLogger()
                    );

                    $config->setQueryCacheImpl($this->getCache($container, $options['cache.query']));
                    $config->setHydrationCacheImpl($this->getCache($container, $options['cache.hydration']));
                    $config->setMetadataCacheImpl($this->getCache($container, $options['cache.metadata']));

                    $config->setResultCacheImpl(
                        $container->get('doctrine.dbal.dbs.config')->get($connectionName)->getResultCacheImpl()
                    );

                    $config->setClassMetadataFactoryName($options['class_metadata.factory.name']);

                    $config->setCustomDatetimeFunctions($options['custom.functions.datetime']);
                    $config->setCustomHydrationModes($options['custom.hydration_modes']);
                    $config->setCustomNumericFunctions($options['custom.functions.numeric']);
                    $config->setCustomStringFunctions($options['custom.functions.string']);

                    $config->setEntityListenerResolver(
                        $container->get(
                            sprintf('doctrine.orm.entity.listener_resolver.%s', $options['entity.listener_resolver'])
                        )
                    );

                    $config->setMetadataDriverImpl(
                        $container->get('doctrine.orm.mapping_driver_chain')($config, $options['mappings'])
                    );

                    $config->setAutoGenerateProxyClasses($options['proxies.auto_generate']);
                    $config->setProxyDir($options['proxies.dir']);
                    $config->setProxyNamespace($options['proxies.namespace']);

                    $config->setDefaultQueryHints($options['query_hints']);

                    $config->setRepositoryFactory(
                        $container->get(sprintf('doctrine.orm.repository.factory.%s', $options['repository.factory']))
                    );
                    $config->setDefaultRepositoryClassName($options['repository.default.class']);

                    $this->assignSecondLevelCache($container, $config, $options);

                    $config->setNamingStrategy(
                        $container->get(sprintf('doctrine.orm.strategy.naming.%s', $options['strategy.naming']))
                    );
                    $config->setQuoteStrategy(
                        $container->get(sprintf('doctrine.orm.strategy.quote.%s', $options['strategy.quote']))
                    );

                    return $config;
                });
            }

            return $configs;
        };
    }

    /**
     * @param array<mixed> $cacheDefinition
     */
    private function getCache(ContainerInterface $container, array $cacheDefinition): Cache
    {
        $cacheType = $cacheDefinition['type'];
        $cacheOptions = $cacheDefinition['options'] ?? [];

        $cacheFactory = $container->get(sprintf('doctrine.dbal.db.cache_factory.%s', $cacheType));

        return $cacheFactory($cacheOptions);
    }

    /**
     * @param array<mixed> $options
     */
    private function assignSecondLevelCache(ContainerInterface $container, Configuration $config, array $options): void
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

    private function getOrmEmsNameDefinition(): \Closure
    {
        return static function (ContainerInterface $container) {
            $container->get('doctrine.orm.ems.options.initializer')();

            return array_keys($container->get('doctrine.orm.ems.options'));
        };
    }

    private function getOrmEmsOptionsInitializerDefinition(): \Closure
    {
        return static fn (ContainerInterface $container) => static function () use ($container): void {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!$container->has('doctrine.orm.ems.options')) {
                $container->factory('doctrine.orm.ems.options', static fn (ContainerInterface $container) => [
                    'default' => $container->has('doctrine.orm.em.options')
                        ? $container->get('doctrine.orm.em.options')
                        : [],
                ]);
            }

            $tmp = $container->get('doctrine.orm.ems.options');
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container->get('doctrine.orm.em.default_options'), $options);

                if (!$container->has('doctrine.orm.ems.default')) {
                    $container->factory('doctrine.orm.ems.default', static fn () => $name);
                }
            }

            $container->factory('doctrine.orm.ems.options', static fn () => $tmp);
        };
    }

    private function getOrmEntityListenerResolverDefinition(): \Closure
    {
        return static fn () => new DefaultEntityListenerResolver();
    }

    private function getOrmManagerRegistryDefintion(): \Closure
    {
        return static fn (ContainerInterface $container) => new DoctrineOrmManagerRegistry($container);
    }

    private function getOrmMappingDriverFactoryAnnotation(): \Closure
    {
        return static fn () => static fn (array $mapping, Configuration $config) => $config->newDefaultAnnotationDriver($mapping['path'], false);
    }

    private function getOrmMappingDriverFactoryClassMap(): \Closure
    {
        return static fn () => static fn (array $mapping) => new ClassMapDriver($mapping['map']);
    }

    private function getOrmMappingDriverFactoryPhp(): \Closure
    {
        return static fn () => static fn (array $mapping) => new PHPDriver($mapping['path']);
    }

    private function getOrmMappingDriverFactorySimpleYaml(): \Closure
    {
        return static fn () => static fn (array $mapping) => new SimplifiedYamlDriver(
            [$mapping['path'] => $mapping['namespace']],
            $mapping['extension'] ?? SimplifiedYamlDriver::DEFAULT_FILE_EXTENSION
        );
    }

    private function getOrmMappingDriverFactorySimpleXml(): \Closure
    {
        return static fn () => static fn (array $mapping) => new SimplifiedXmlDriver(
            [$mapping['path'] => $mapping['namespace']],
            $mapping['extension'] ?? SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION
        );
    }

    private function getOrmMappingDriverFactoryStaticPhp(): \Closure
    {
        return static fn () => static fn (array $mapping) => new StaticPHPDriver($mapping['path']);
    }

    private function getOrmMappingDriverFactoryYaml(): \Closure
    {
        return static fn () => static fn (array $mapping) => new YamlDriver($mapping['path'], $mapping['extension'] ?? YamlDriver::DEFAULT_FILE_EXTENSION);
    }

    private function getOrmMappingDriverFactoryXml(): \Closure
    {
        return static fn () => static fn (array $mapping) => new XmlDriver($mapping['path'], $mapping['extension'] ?? XmlDriver::DEFAULT_FILE_EXTENSION);
    }

    private function getOrmMappingDriverChainDefinition(): \Closure
    {
        return static fn (ContainerInterface $container) => static function (Configuration $config, array $mappings) use ($container) {
            $chain = new MappingDriverChain();
            foreach ($mappings as $mapping) {
                if (isset($mapping['alias'])) {
                    $config->addEntityNamespace($mapping['alias'], $mapping['namespace']);
                }

                $factoryKey = sprintf('doctrine.orm.mapping_driver.factory.%s', $mapping['type']);

                $chain->addDriver($container->get($factoryKey)($mapping, $config), $mapping['namespace']);
            }

            return $chain;
        };
    }

    private function getOrmRepositoryFactoryDefinition(): \Closure
    {
        return static fn () => new DefaultRepositoryFactory();
    }

    private function getOrmNamingStrategyDefinition(): \Closure
    {
        return static fn () => new DefaultNamingStrategy();
    }

    private function getOrmQuoteStrategyDefinition(): \Closure
    {
        return static fn () => new DefaultQuoteStrategy();
    }
}
