<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\ServiceProvider;

use Chubbyphp\DoctrineDbServiceProvider\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\DoctrineDbServiceProvider\ServiceProvider\DoctrineOrmServiceProvider;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Annotation\Entity\Annotation;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\ClassMap\Entity\ClassMap;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\ClassMap\Mapping\ClassMapMapping;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Php\Entity\Php;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleXml\Entity\SimpleXml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleYaml\Entity\SimpleYaml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\StaticPhp\Entity\StaticPhp;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Xml\Entity\Xml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Yaml\Entity\Yaml;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCache;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\ServiceProvider\DoctrineOrmServiceProvider
 */
class DoctrineOrmServiceProviderTest extends TestCase
{
    use MockByCallsTrait;

    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        self::assertArrayHasKey('doctrine.orm.em', $container);
        self::assertArrayHasKey('doctrine.orm.em.config', $container);
        self::assertArrayHasKey('doctrine.orm.em.default_options', $container);
        self::assertArrayHasKey('doctrine.orm.ems', $container);
        self::assertArrayHasKey('doctrine.orm.ems.config', $container);
        self::assertArrayHasKey('doctrine.orm.ems.options.initializer', $container);
        self::assertArrayHasKey('doctrine.orm.entity.listener_resolver.default', $container);
        self::assertArrayHasKey('doctrine.orm.manager_registry', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.annotation', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.class_map', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.php', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_yaml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.static_php', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.yaml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver_chain', $container);
        self::assertArrayHasKey('doctrine.orm.repository.factory.default', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.naming.default', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.quote.default', $container);

        // start: doctrine.orm.em
        self::assertSame($container['doctrine.orm.em'], $container['doctrine.orm.ems']['default']);

        /** @var EntityManager $em */
        $em = $container['doctrine.orm.em'];

        self::assertInstanceOf(EntityManager::class, $em);
        self::assertSame($container['doctrine.dbal.db'], $em->getConnection());
        self::assertInstanceOf(ClassMetadataFactory::class, $em->getMetadataFactory());
        self::assertNull($em->getCache());

        try {
            $hasMappingException = false;
            $em->getClassMetadata(\stdClass::class);
        } catch (MappingException $mappingException) {
            $hasMappingException = true;
        }

        self::assertTrue($hasMappingException);

        try {
            $hasMappingException = false;
            $em->getRepository(\stdClass::class);
        } catch (MappingException $mappingException) {
            $hasMappingException = true;
        }

        self::assertTrue($hasMappingException);

        self::assertSame($container['doctrine.dbal.db.event_manager'], $em->getEventManager());
        self::assertSame($container['doctrine.orm.em.config'], $em->getConfiguration());
        self::assertInstanceOf(ProxyFactory::class, $em->getProxyFactory());
        // end: doctrine.orm.em

        // start: doctrine.orm.em.config
        self::assertSame($container['doctrine.orm.em.config'], $container['doctrine.orm.ems.config']['default']);

        /** @var Configuration $config */
        $config = $container['doctrine.orm.em.config'];

        self::assertSame(AbstractProxyFactory::AUTOGENERATE_ALWAYS, $config->getAutoGenerateProxyClasses());
        self::assertSame(sys_get_temp_dir().'/doctrine/orm/proxies', $config->getProxyDir());
        self::assertSame('DoctrineProxy', $config->getProxyNamespace());
        self::assertInstanceOf(MappingDriverChain::class, $config->getMetadataDriverImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getQueryCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getHydrationCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getMetadataCacheImpl());

        self::assertNotSame($config->getQueryCacheImpl(), $config->getHydrationCacheImpl());
        self::assertNotSame($config->getQueryCacheImpl(), $config->getMetadataCacheImpl());
        self::assertNotSame($config->getQueryCacheImpl(), $config->getResultCacheImpl());
        self::assertNotSame($config->getHydrationCacheImpl(), $config->getMetadataCacheImpl());
        self::assertNotSame($config->getHydrationCacheImpl(), $config->getResultCacheImpl());
        self::assertNotSame($config->getMetadataCacheImpl(), $config->getResultCacheImpl());

        self::assertSame(ClassMetadataFactory::class, $config->getClassMetadataFactoryName());
        self::assertSame(EntityRepository::class, $config->getDefaultRepositoryClassName());
        self::assertInstanceOf(DefaultNamingStrategy::class, $config->getNamingStrategy());
        self::assertInstanceOf(DefaultQuoteStrategy::class, $config->getQuoteStrategy());
        self::assertInstanceOf(DefaultEntityListenerResolver::class, $config->getEntityListenerResolver());
        self::assertInstanceOf(DefaultRepositoryFactory::class, $config->getRepositoryFactory());
        self::assertFalse($config->isSecondLevelCacheEnabled());
        self::assertNull($config->getSecondLevelCacheConfiguration());
        self::assertSame([], $config->getDefaultQueryHints());
        self::assertNull($config->getSQLLogger());
        self::assertSame($container['doctrine.dbal.db.config']->getResultCacheImpl(), $config->getResultCacheImpl());
        self::assertNull($config->getFilterSchemaAssetsExpression());
        self::assertTrue($config->getAutoCommit());
        // end: doctrine.orm.em.config

        // start: doctrine.orm.em.default_options
        self::assertEquals([
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
        ], $container['doctrine.orm.em.default_options']);
        // end: doctrine.orm.em.default_options

        // start: doctrine.orm.ems
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems']);
        // end: doctrine.orm.ems

        // start: doctrine.orm.ems.config
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems.config']);
        // end: doctrine.orm.ems.config

        // start: doctrine.orm.ems.options.initializer
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.ems.options.initializer']);
        // end: doctrine.orm.ems.options.initializer

        // start: doctrine.orm.manager_registry
        self::assertInstanceOf(ManagerRegistry::class, $container['doctrine.orm.manager_registry']);

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $container['doctrine.orm.manager_registry'];

        self::assertSame('default', $managerRegistry->getDefaultConnectionName());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnection());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnections()['default']);
        self::assertSame(['default'], $managerRegistry->getConnectionNames());

        self::assertSame('default', $managerRegistry->getDefaultManagerName());
        self::assertSame($container['doctrine.orm.em'], $managerRegistry->getManager());
        self::assertSame($container['doctrine.orm.em'], $managerRegistry->getManagers()['default']);
        self::assertSame(['default'], $managerRegistry->getManagerNames());
        // end: doctrine.orm.manager_registry

        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.annotation']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.class_map']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.php']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_yaml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.static_php']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.yaml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver_chain']);

        self::assertSame($config->getRepositoryFactory(), $container['doctrine.orm.repository.factory.default']);
        self::assertSame($config->getNamingStrategy(), $container['doctrine.orm.strategy.naming.default']);
        self::assertSame($config->getQuoteStrategy(), $container['doctrine.orm.strategy.quote.default']);
    }

    public function testRegisterWithOneManager()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.orm.entity.listener_resolver.other'] = function () {
            return new DefaultEntityListenerResolver();
        };

        $container['doctrine.orm.repository.factory.other'] = function () {
            return new DefaultRepositoryFactory();
        };

        $container['doctrine.orm.strategy.naming.other'] = function () {
            return new DefaultNamingStrategy();
        };

        $container['doctrine.orm.strategy.quote.other'] = function () {
            return new DefaultQuoteStrategy();
        };

        $classMetadataFactory = new class() extends ClassMetadataFactory {
        };

        $classMetadataFactoryClass = get_class($classMetadataFactory);

        $repository = new class() extends EntityRepository {
            public function __construct()
            {
            }
        };

        $repositoryClass = get_class($repository);

        $container['doctrine.orm.em.options'] = [
            'cache.hydration' => ['type' => 'apcu'],
            'cache.metadata' => ['type' => 'apcu'],
            'cache.query' => ['type' => 'apcu'],
            'class_metadata.factory.name' => $classMetadataFactoryClass,
            'custom.functions.datetime' => [
                'date' => \stdClass::class,
            ],
            'custom.functions.numeric' => [
                'numeric' => \stdClass::class,
            ],
            'custom.functions.string' => [
                'string' => \stdClass::class,
            ],
            'custom.hydration_modes' => [
                'hydrator' => \stdClass::class,
            ],
            'entity.listener_resolver' => 'other',
            'mappings' => [
                [
                    'type' => 'annotation',
                    'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Annotation\Entity',
                    'path' => __DIR__.'/../Resources/Annotation/Entity',
                ],
            ],
            'proxies.auto_generate' => false,
            'proxies.dir' => sys_get_temp_dir().'/doctrine/orm/otherproxies',
            'proxies.namespace' => 'DoctrineOtherProxy',
            'query_hints' => [
                'hint' => \stdClass::class,
            ],
            'repository.default.class' => $repositoryClass,
            'repository.factory' => 'other',
            'second_level_cache' => ['type' => 'apcu'],
            'second_level_cache.enabled' => true,
            'strategy.naming' => 'other',
            'strategy.quote' => 'other',
        ];

        /** @var EntityManager $em */
        $em = $container['doctrine.orm.em'];

        self::assertInstanceOf(DefaultCache::class, $em->getCache());

        $config = $em->getConfiguration();

        self::assertInstanceOf(ApcuCache::class, $config->getHydrationCacheImpl());
        self::assertInstanceOf(ApcuCache::class, $config->getMetadataCacheImpl());
        self::assertInstanceOf(ApcuCache::class, $config->getQueryCacheImpl());
        self::assertSame($classMetadataFactoryClass, $config->getClassMetadataFactoryName());
        self::assertSame(\stdClass::class, $config->getCustomDatetimeFunction('date'));
        self::assertSame(\stdClass::class, $config->getCustomNumericFunction('numeric'));
        self::assertSame(\stdClass::class, $config->getCustomStringFunction('string'));
        self::assertSame(\stdClass::class, $config->getCustomHydrationMode('hydrator'));
        self::assertSame(
            $container['doctrine.orm.entity.listener_resolver.other'],
            $config->getEntityListenerResolver()
        );
        self::assertInstanceOf(EntityRepository::class, $em->getRepository(Annotation::class));
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_NEVER, $config->getAutoGenerateProxyClasses());
        self::assertSame(sys_get_temp_dir().'/doctrine/orm/otherproxies', $config->getProxyDir());
        self::assertSame('DoctrineOtherProxy', $config->getProxyNamespace());
        self::assertSame(\stdClass::class, $config->getDefaultQueryHint('hint'));
        self::assertSame($repositoryClass, $config->getDefaultRepositoryClassName());
        self::assertSame(
            $container['doctrine.orm.repository.factory.other'],
            $config->getRepositoryFactory()
        );
        self::assertInstanceOf(CacheConfiguration::class, $config->getSecondLevelCacheConfiguration());

        $cacheFactory = $config->getSecondLevelCacheConfiguration()->getCacheFactory();

        self::assertInstanceOf(DefaultCacheFactory::class, $cacheFactory);

        $reflectionProperty = new \ReflectionProperty(DefaultCacheFactory::class, 'cache');
        $reflectionProperty->setAccessible(true);

        self::assertInstanceOf(ApcuCache::class, $reflectionProperty->getValue($cacheFactory));

        self::assertSame(
            $container['doctrine.orm.strategy.naming.other'],
            $config->getNamingStrategy()
        );

        self::assertSame(
            $container['doctrine.orm.strategy.quote.other'],
            $config->getQuoteStrategy()
        );
    }

    public function testRegisterWithMultipleManager()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.dbal.dbs.options'] = [
            'one' => [],
            'two' => [],
        ];

        $container['doctrine.orm.ems.options'] = [
            'annotation' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Annotation\Entity',
                        'alias' => 'Entity\Annotation',
                        'path' => __DIR__.'/../Resources/Annotation/Entity',
                    ],
                ],
            ],
            'classMap' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'class_map',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\ClassMap\Entity',
                        'alias' => 'Entity\ClassMap',
                        'map' => [
                            ClassMap::class => ClassMapMapping::class,
                        ],
                    ],
                ],
            ],
            'php' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'php',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Php\Entity',
                        'alias' => 'Entity\Php',
                        'path' => __DIR__.'/../Resources/Php/config',
                    ],
                ],
            ],
            'simpleYaml' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'simple_yaml',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleYaml\Entity',
                        'alias' => 'Entity\SimpleYaml',
                        'path' => __DIR__.'/../Resources/SimpleYaml/config',
                    ],
                ],
            ],
            'simpleXml' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleXml\Entity',
                        'alias' => 'Entity\SimpleXml',
                        'path' => __DIR__.'/../Resources/SimpleXml/config',
                    ],
                ],
            ],
            'yaml' => [
                'connection' => 'two',
                'mappings' => [
                    [
                        'type' => 'yaml',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Yaml\Entity',
                        'alias' => 'Entity\Yaml',
                        'path' => __DIR__.'/../Resources/Yaml/config',
                    ],
                ],
            ],
            'xml' => [
                'connection' => 'two',
                'mappings' => [
                    [
                        'type' => 'xml',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Xml\Entity',
                        'alias' => 'Entity\Xml',
                        'path' => __DIR__.'/../Resources/Xml/config',
                    ],
                ],
            ],
            'staticPhp' => [
                'connection' => 'two',
                'mappings' => [
                    [
                        'type' => 'static_php',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\StaticPhp\Entity',
                        'alias' => 'Entity\StaticPhp',
                        'path' => __DIR__.'/../Resources/StaticPhp/Entity',
                    ],
                ],
            ],
        ];

        /** @var EntityManager $annotationEm */
        $annotationEm = $container['doctrine.orm.ems']['annotation'];

        self::assertSame($container['doctrine.dbal.dbs']['one'], $annotationEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $annotationEm->getRepository(Annotation::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Annotation\Entity',
            $annotationEm->getConfiguration()->getEntityNamespace('Entity\Annotation')
        );

        /** @var EntityManager $classMapEm */
        $classMapEm = $container['doctrine.orm.ems']['classMap'];

        self::assertSame($container['doctrine.dbal.dbs']['one'], $classMapEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $classMapEm->getRepository(ClassMap::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\ClassMap\Entity',
            $classMapEm->getConfiguration()->getEntityNamespace('Entity\ClassMap')
        );

        /** @var EntityManager $php */
        $php = $container['doctrine.orm.ems']['php'];

        self::assertSame($container['doctrine.dbal.dbs']['one'], $php->getConnection());
        self::assertInstanceOf(EntityRepository::class, $php->getRepository(Php::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Php\Entity',
            $php->getConfiguration()->getEntityNamespace('Entity\Php')
        );

        /** @var EntityManager $simpleYamlEm */
        $simpleYamlEm = $container['doctrine.orm.ems']['simpleYaml'];

        self::assertSame($container['doctrine.dbal.dbs']['one'], $simpleYamlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $simpleYamlEm->getRepository(SimpleYaml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleYaml\Entity',
            $simpleYamlEm->getConfiguration()->getEntityNamespace('Entity\SimpleYaml')
        );

        /** @var EntityManager $simpleXmlEm */
        $simpleXmlEm = $container['doctrine.orm.ems']['simpleXml'];

        self::assertSame($container['doctrine.dbal.dbs']['one'], $simpleXmlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $simpleXmlEm->getRepository(SimpleXml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\SimpleXml\Entity',
            $simpleXmlEm->getConfiguration()->getEntityNamespace('Entity\SimpleXml')
        );

        /** @var EntityManager $yamlEm */
        $yamlEm = $container['doctrine.orm.ems']['yaml'];

        self::assertSame($container['doctrine.dbal.dbs']['two'], $yamlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $yamlEm->getRepository(Yaml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Yaml\Entity',
            $yamlEm->getConfiguration()->getEntityNamespace('Entity\Yaml')
        );

        /** @var EntityManager $xmlEm */
        $xmlEm = $container['doctrine.orm.ems']['xml'];

        self::assertSame($container['doctrine.dbal.dbs']['two'], $xmlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $xmlEm->getRepository(Xml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Xml\Entity',
            $xmlEm->getConfiguration()->getEntityNamespace('Entity\Xml')
        );

        /** @var EntityManager $staticPhp */
        $staticPhp = $container['doctrine.orm.ems']['staticPhp'];

        self::assertSame($container['doctrine.dbal.dbs']['two'], $staticPhp->getConnection());
        self::assertInstanceOf(EntityRepository::class, $staticPhp->getRepository(StaticPhp::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\StaticPhp\Entity',
            $staticPhp->getConfiguration()->getEntityNamespace('Entity\StaticPhp')
        );
    }
}
