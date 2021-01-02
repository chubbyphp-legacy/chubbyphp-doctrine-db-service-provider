<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\ServiceFactory;

use Chubbyphp\Container\Container;
use Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineDbalServiceFactory;
use Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineOrmServiceFactory;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Annotation\Entity\Annotation;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\ClassMap\Entity\ClassMap;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\ClassMap\Mapping\ClassMapMapping;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Php\Entity\Php;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleXml\Entity\SimpleXml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleYaml\Entity\SimpleYaml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\StaticPhp\Entity\StaticPhp;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Xml\Entity\Xml;
use Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Yaml\Entity\Yaml;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\MappingException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineOrmServiceFactory
 *
 * @internal
 */
final class DoctrineOrmServiceFactoryTest extends TestCase
{
    use MockByCallsTrait;

    public function testRegisterWithDefaults(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());
        $container->factories((new DoctrineOrmServiceFactory())());

        self::assertTrue($container->has('doctrine.orm.em'));
        self::assertTrue($container->has('doctrine.orm.em.config'));
        self::assertTrue($container->has('doctrine.orm.em.default_options'));
        self::assertTrue($container->has('doctrine.orm.ems'));
        self::assertTrue($container->has('doctrine.orm.ems.config'));
        self::assertTrue($container->has('doctrine.orm.ems.options.initializer'));
        self::assertTrue($container->has('doctrine.orm.entity.listener_resolver.default'));
        self::assertTrue($container->has('doctrine.orm.manager_registry'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.annotation'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.class_map'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.php'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.simple_xml'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.simple_yaml'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.static_php'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.xml'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver.factory.yaml'));
        self::assertTrue($container->has('doctrine.orm.mapping_driver_chain'));
        self::assertTrue($container->has('doctrine.orm.repository.factory.default'));
        self::assertTrue($container->has('doctrine.orm.strategy.naming.default'));
        self::assertTrue($container->has('doctrine.orm.strategy.quote.default'));

        // start: doctrine.orm.em
        self::assertSame($container->get('doctrine.orm.em'), $container->get('doctrine.orm.ems')->get('default'));

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.em');

        self::assertInstanceOf(EntityManager::class, $em);
        self::assertSame($container->get('doctrine.dbal.db'), $em->getConnection());
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

        self::assertSame($container->get('doctrine.dbal.db.event_manager'), $em->getEventManager());
        self::assertSame($container->get('doctrine.orm.em.config'), $em->getConfiguration());
        self::assertInstanceOf(ProxyFactory::class, $em->getProxyFactory());
        // end: doctrine.orm.em

        // start: doctrine.orm.em.config
        self::assertSame($container->get('doctrine.orm.em.config'), $container->get('doctrine.orm.ems.config')->get('default'));

        /** @var Configuration $config */
        $config = $container->get('doctrine.orm.em.config');

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
        self::assertSame($container->get('doctrine.dbal.db.config')->getResultCacheImpl(), $config->getResultCacheImpl());
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
        ], $container->get('doctrine.orm.em.default_options'));
        // end: doctrine.orm.em.default_options

        // start: doctrine.orm.ems
        self::assertInstanceOf(Container::class, $container->get('doctrine.orm.ems'));
        // end: doctrine.orm.ems

        // start: doctrine.orm.ems.config
        self::assertInstanceOf(Container::class, $container->get('doctrine.orm.ems.config'));
        // end: doctrine.orm.ems.config

        // start: doctrine.orm.ems.options.initializer
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.ems.options.initializer'));
        // end: doctrine.orm.ems.options.initializer

        // start: doctrine.orm.manager_registry
        self::assertInstanceOf(ManagerRegistry::class, $container->get('doctrine.orm.manager_registry'));

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $container->get('doctrine.orm.manager_registry');

        self::assertSame('default', $managerRegistry->getDefaultConnectionName());
        self::assertSame($container->get('doctrine.dbal.db'), $managerRegistry->getConnection());
        self::assertSame($container->get('doctrine.dbal.db'), $managerRegistry->getConnections()['default']);
        self::assertSame(['default'], $managerRegistry->getConnectionNames());

        self::assertSame('default', $managerRegistry->getDefaultManagerName());
        self::assertSame($container->get('doctrine.orm.em'), $managerRegistry->getManager());
        self::assertSame($container->get('doctrine.orm.em'), $managerRegistry->getManagers()['default']);
        self::assertSame(['default'], $managerRegistry->getManagerNames());
        // end: doctrine.orm.manager_registry

        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.annotation'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.class_map'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.php'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.simple_xml'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.simple_yaml'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.static_php'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.xml'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver.factory.yaml'));
        self::assertInstanceOf(\Closure::class, $container->get('doctrine.orm.mapping_driver_chain'));

        self::assertSame($config->getRepositoryFactory(), $container->get('doctrine.orm.repository.factory.default'));
        self::assertSame($config->getNamingStrategy(), $container->get('doctrine.orm.strategy.naming.default'));
        self::assertSame($config->getQuoteStrategy(), $container->get('doctrine.orm.strategy.quote.default'));
    }

    public function testRegisterWithOneManager(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());
        $container->factories((new DoctrineOrmServiceFactory())());

        $container->factory('logger', fn () => $this->getMockByCalls(LoggerInterface::class));

        $container->factory('doctrine.orm.entity.listener_resolver.other', static fn () => new DefaultEntityListenerResolver());

        $container->factory('doctrine.orm.repository.factory.other', static fn () => new DefaultRepositoryFactory());

        $container->factory('doctrine.orm.strategy.naming.other', static fn () => new DefaultNamingStrategy());

        $container->factory('doctrine.orm.strategy.quote.other', static fn () => new DefaultQuoteStrategy());

        $classMetadataFactory = new class() extends ClassMetadataFactory {
        };

        $classMetadataFactoryClass = get_class($classMetadataFactory);

        $repository = new class() extends EntityRepository {
            public function __construct()
            {
            }
        };

        $repositoryClass = get_class($repository);

        $container->factory('doctrine.orm.em.options', static fn () => [
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
                    'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Annotation\Entity',
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
        ]);

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.em');

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
            $container->get('doctrine.orm.entity.listener_resolver.other'),
            $config->getEntityListenerResolver()
        );
        self::assertInstanceOf(EntityRepository::class, $em->getRepository(Annotation::class));
        self::assertSame(AbstractProxyFactory::AUTOGENERATE_NEVER, $config->getAutoGenerateProxyClasses());
        self::assertSame(sys_get_temp_dir().'/doctrine/orm/otherproxies', $config->getProxyDir());
        self::assertSame('DoctrineOtherProxy', $config->getProxyNamespace());
        self::assertSame(\stdClass::class, $config->getDefaultQueryHint('hint'));
        self::assertSame($repositoryClass, $config->getDefaultRepositoryClassName());
        self::assertSame(
            $container->get('doctrine.orm.repository.factory.other'),
            $config->getRepositoryFactory()
        );
        self::assertInstanceOf(CacheConfiguration::class, $config->getSecondLevelCacheConfiguration());

        $cacheFactory = $config->getSecondLevelCacheConfiguration()->getCacheFactory();

        self::assertInstanceOf(DefaultCacheFactory::class, $cacheFactory);

        $reflectionProperty = new \ReflectionProperty(DefaultCacheFactory::class, 'cache');
        $reflectionProperty->setAccessible(true);

        self::assertInstanceOf(ApcuCache::class, $reflectionProperty->getValue($cacheFactory));

        self::assertSame(
            $container->get('doctrine.orm.strategy.naming.other'),
            $config->getNamingStrategy()
        );

        self::assertSame(
            $container->get('doctrine.orm.strategy.quote.other'),
            $config->getQuoteStrategy()
        );
    }

    public function testRegisterWithMultipleManager(): void
    {
        $container = new Container();
        $container->factories((new DoctrineDbalServiceFactory())());
        $container->factories((new DoctrineOrmServiceFactory())());

        $container->factory('logger', fn () => $this->getMockByCalls(LoggerInterface::class));

        $container->factory('doctrine.dbal.dbs.options', static fn () => [
            'one' => [],
            'two' => [],
        ]);

        $container->factory('doctrine.orm.ems.options', static fn () => [
            'annotation' => [
                'connection' => 'one',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Annotation\Entity',
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
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\ClassMap\Entity',
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
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Php\Entity',
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
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleYaml\Entity',
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
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleXml\Entity',
                        'alias' => 'Entity\SimpleXml',
                        'path' => __DIR__.'/../Resources/SimpleXml/config',
                    ],
                ],
            ],
            'staticPhp' => [
                'connection' => 'two',
                'mappings' => [
                    [
                        'type' => 'static_php',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\StaticPhp\Entity',
                        'alias' => 'Entity\StaticPhp',
                        'path' => __DIR__.'/../Resources/StaticPhp/Entity',
                    ],
                ],
            ],
            'yaml' => [
                'connection' => 'two',
                'mappings' => [
                    [
                        'type' => 'yaml',
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Yaml\Entity',
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
                        'namespace' => 'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Xml\Entity',
                        'alias' => 'Entity\Xml',
                        'path' => __DIR__.'/../Resources/Xml/config',
                    ],
                ],
            ],
        ]);

        // start: annotation
        /** @var EntityManager $annotationEm */
        $annotationEm = $container->get('doctrine.orm.ems')->get('annotation');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('one'), $annotationEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $annotationEm->getRepository(Annotation::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Annotation\Entity',
            $annotationEm->getConfiguration()->getEntityNamespace('Entity\Annotation')
        );

        $annotationClassMetadata = $annotationEm->getClassMetadata(Annotation::class);

        self::assertSame(['name' => 'annotation'], $annotationClassMetadata->table);
        self::assertArrayHasKey('id', $annotationClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $annotationClassMetadata->fieldMappings);
        // end: annotation

        // start: class_map
        /** @var EntityManager $classMapEm */
        $classMapEm = $container->get('doctrine.orm.ems')->get('classMap');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('one'), $classMapEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $classMapEm->getRepository(ClassMap::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\ClassMap\Entity',
            $classMapEm->getConfiguration()->getEntityNamespace('Entity\ClassMap')
        );

        $classMapClassMetadata = $classMapEm->getClassMetadata(ClassMap::class);

        self::assertSame(['name' => 'class_map'], $classMapClassMetadata->table);
        self::assertArrayHasKey('id', $classMapClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $classMapClassMetadata->fieldMappings);
        // end: class_map

        // start: php
        /** @var EntityManager $phpEm */
        $phpEm = $container->get('doctrine.orm.ems')->get('php');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('one'), $phpEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $phpEm->getRepository(Php::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Php\Entity',
            $phpEm->getConfiguration()->getEntityNamespace('Entity\Php')
        );

        $phpClassMetadata = $phpEm->getClassMetadata(Php::class);

        self::assertSame(['name' => 'php'], $phpClassMetadata->table);
        self::assertArrayHasKey('id', $phpClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $phpClassMetadata->fieldMappings);
        // end: php

        // start: simple_yaml
        /** @var EntityManager $simpleYamlEm */
        $simpleYamlEm = $container->get('doctrine.orm.ems')->get('simpleYaml');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('one'), $simpleYamlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $simpleYamlEm->getRepository(SimpleYaml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleYaml\Entity',
            $simpleYamlEm->getConfiguration()->getEntityNamespace('Entity\SimpleYaml')
        );

        $simpleYamlClassMetadata = $simpleYamlEm->getClassMetadata(SimpleYaml::class);

        self::assertSame(['name' => 'simple_yaml'], $simpleYamlClassMetadata->table);
        self::assertArrayHasKey('id', $simpleYamlClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $simpleYamlClassMetadata->fieldMappings);
        // end: simple_yaml

        // start: simple_xml
        /** @var EntityManager $simpleXmlEm */
        $simpleXmlEm = $container->get('doctrine.orm.ems')->get('simpleXml');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('one'), $simpleXmlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $simpleXmlEm->getRepository(SimpleXml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\SimpleXml\Entity',
            $simpleXmlEm->getConfiguration()->getEntityNamespace('Entity\SimpleXml')
        );

        $simpleXmlClassMetadata = $simpleXmlEm->getClassMetadata(SimpleXml::class);

        self::assertSame(['name' => 'simple_xml'], $simpleXmlClassMetadata->table);
        self::assertArrayHasKey('id', $simpleXmlClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $simpleXmlClassMetadata->fieldMappings);
        // end: simple_xml

        // start: static_php
        /** @var EntityManager $staticPhp */
        $staticPhp = $container->get('doctrine.orm.ems')->get('staticPhp');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('two'), $staticPhp->getConnection());
        self::assertInstanceOf(EntityRepository::class, $staticPhp->getRepository(StaticPhp::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\StaticPhp\Entity',
            $staticPhp->getConfiguration()->getEntityNamespace('Entity\StaticPhp')
        );

        $staticPhpClassMetadata = $staticPhp->getClassMetadata(StaticPhp::class);

        self::assertSame(['name' => 'static_php'], $staticPhpClassMetadata->table);
        self::assertArrayHasKey('id', $staticPhpClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $staticPhpClassMetadata->fieldMappings);
        // end: static_php

        // start: yaml
        /** @var EntityManager $yamlEm */
        $yamlEm = $container->get('doctrine.orm.ems')->get('yaml');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('two'), $yamlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $yamlEm->getRepository(Yaml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Yaml\Entity',
            $yamlEm->getConfiguration()->getEntityNamespace('Entity\Yaml')
        );

        $yamlClassMetadata = $yamlEm->getClassMetadata(Yaml::class);

        self::assertSame(['name' => 'yaml'], $yamlClassMetadata->table);
        self::assertArrayHasKey('id', $yamlClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $yamlClassMetadata->fieldMappings);
        // end: yaml

        // start: xml
        /** @var EntityManager $xmlEm */
        $xmlEm = $container->get('doctrine.orm.ems')->get('xml');

        self::assertSame($container->get('doctrine.dbal.dbs')->get('two'), $xmlEm->getConnection());
        self::assertInstanceOf(EntityRepository::class, $xmlEm->getRepository(Xml::class));
        self::assertSame(
            'Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\Xml\Entity',
            $xmlEm->getConfiguration()->getEntityNamespace('Entity\Xml')
        );

        $xmlClassMetadata = $xmlEm->getClassMetadata(Xml::class);

        self::assertSame(['name' => 'xml'], $xmlClassMetadata->table);
        self::assertArrayHasKey('id', $xmlClassMetadata->fieldMappings);
        self::assertArrayHasKey('name', $xmlClassMetadata->fieldMappings);
        // end: xml
    }
}
