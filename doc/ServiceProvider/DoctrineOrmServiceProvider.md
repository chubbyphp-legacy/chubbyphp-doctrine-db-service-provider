# DoctrineOrmServiceProvider

The *DoctrineOrmServiceProvider* provides integration with the [Doctrine ORM][1].

## Install

```sh
composer require doctrine/orm "^2.5"
```

## Parameters

* **doctrine.orm.em.options**: Array of Doctrine ORM options.

    These options are available:

    * **cache.hydration**: Array with the cache settings, defaults to `['type' => 'array']`.
        Can be any of: `apcu`, `array`. Add additional cache provider factories by adding new service:
        `$container['doctrine.dbal.db.cache_factory.<type>']`
    * **cache.metadata**: Array with the cache settings, defaults to `['type' => 'array']`.
        Can be any of: `apcu`, `array`. Add additional cache provider factories by adding new service:
        `$container['doctrine.dbal.db.cache_factory.<type>']`
    * **cache.query**: Array with the cache settings, defaults to `['type' => 'array']`.
        Can be any of: `apcu`, `array`. Add additional cache provider factories by adding new service:
        `$container['doctrine.dbal.db.cache_factory.<type>']`
    * **class_metadata.factory.name**: String with class, defaults to `Doctrine\ORM\Mapping\ClassMetadataFactory`.
    * **connection**: The connection name of the Doctrine DBAL configuration. Defaults to `default`.
    * **custom.functions.datetime**: Array of datetime related [custom functions][2].
    * **custom.functions.numeric**: Array of numeric related [custom functions][2].
    * **custom.functions.string**: Array of string related [custom functions][2].
    * **custom.hydration_modes**: Array of [hydration modes][3].
    * **entity.listener_resolver**: String with the resolver type, defaults to `default`.
        Add additional resolvers by adding new service:
        `$container['doctrine.orm.entity.listener_resolver.<type>']`.
    * **mappings**: Array of Mappings.
        * **type**: The mapping driver to use. Can be any of: `annotation`, `yaml`, `simple_yaml`, `xml`, `simple_xml`,  or `static_php`.
            Add additional mapping driver factories by adding new service:
            `$container['doctrine.orm.mapping_driver.factory.<type>']`
        * **namespace**: The entity namespace. Example: `One\Entity`
        * **path**: The path to the entities. Example: `/path/to/project/One/Entity`
        * **alias**: The entity alias to the namespace. Example: `Alias\Entity`
        * **extension**: The file extension to search for mappings. Example: `.dcm.xml`
    * **proxies.auto_generate**: Enable or disable the auto generation of proxies. Defaults to `true`.
    * **proxies.dir**: The directory where generated proxies get saved. Example: `var/cache/doctrine/orm/proxies`.
    * **proxies.namespace**: The namespace of generated proxies. Defaults to `DoctrineProxy`.
    * **query_hints**: Array of [query hints][4].
    * **repository.default.class**: String with class, defaults to `Doctrine\ORM\EntityRepository`.
    * **repository.factory**: String with the repository factory type, defaults to `default`.
        Add additional repository factories by adding new service: `$container['doctrine.orm.repository.factory.<type>']`.
    * **second_level_cache.enabled**: Enable or disable second level cache, defaults to `false`.
    * **second_level_cache**: Array with the cache settings, defaults to `['type' => 'array']`.
        Can be any of: `apcu`, `array`. Add additional cache provider factories by adding new service:
        `$container['doctrine.dbal.db.cache_factory.<type>']`
    * **strategy.naming**: String with the naming strategy type, defaults to `default`.
        Add additional naming stratigies by adding new service: `$container['doctrine.orm.strategy.naming.<type>']`.
    * **strategy.quote**: String with the quote strategy type, defaults to `default`.
        Add additional quote stratigies by adding new service: `$container['doctrine.orm.strategy.quote.<type>']`.

## Services

* **doctrine.orm.em**: The entity manager, instance of `Doctrine\ORM\EntityManager`.
* **doctrine.orm.em.config**: Configuration object for Doctrine. Defaults to an empty `Doctrine\ORM\Configuration`.
* **doctrine.orm.manager_registry**: The manager registry, instance of `Doctrine\Common\Persistence\ManagerRegistry`.

## Registering

### Single connection

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\ServiceProvider\DoctrineDbalServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\ServiceProvider\DoctrineOrmServiceProvider()));

$container['doctrine.dbal.db.options'] = [
    'connection' => [
        'driver'    => 'pdo_mysql',
        'host'      => 'mysql.someplace.tld',
        'dbname'    => 'my_database',
        'user'      => 'my_username',
        'password'  => 'my_password',
        'charset'   => 'utf8mb4',
    ],
];

$container['doctrine.orm.em.options'] = [
    'mappings' => [
        [
            'type' => 'annotation',
            'namespace' => 'One\Entity',
            'path' => __DIR__.'/src/One/Entity',
        ]
    ]
];
```

### Multiple connections

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\ServiceProvider\DoctrineDbalServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\ServiceProvider\DoctrineOrmServiceProvider()));

$container['doctrine.dbal.dbs.options'] = [
    'mysql_read' => [
        'connection' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'mysql_read.someplace.tld',
            'dbname'    => 'my_database',
            'user'      => 'my_username',
            'password'  => 'my_password',
            'charset'   => 'utf8mb4',
        ],
    ],
    'mysql_write' => [
        'connection' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'mysql_write.someplace.tld',
            'dbname'    => 'my_database',
            'user'      => 'my_username',
            'password'  => 'my_password',
            'charset'   => 'utf8mb4',
        ],
    ],
];

$container['doctrine.orm.ems.options'] = [
    'mysql_read' => [
        'connection' => 'mysql_read',
        'mappings' => [
            [
                'type' => 'annotation',
                'namespace' => 'One\Entity',
                'alias' => 'One',
                'path' => __DIR__.'/src/One/Entity',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ],
    'mysql_write' => [
        'connection' => 'mysql_write',
        'mappings' => [
            [
                'type' => 'annotation',
                'namespace' => 'One\Entity',
                'path' => __DIR__.'/src/One/Entity',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ],
];
```

## Usage

### Single connection

```php
$container['doctrine.orm.em']
    ->getRepository(User::class)
    ->findOneBy(['username' => 'john.doe@domain.com']);
```

### Multiple connections

```php
$container['doctrine.orm.ems']['name']
    ->getRepository(User::class)
    ->findOneBy(['username' => 'john.doe@domain.com']);
```

## Copyright

* Beau Simensen <beau@dflydev.com> (https://github.com/dflydev/dflydev-doctrine-orm-service-provider)
* Dominik Zogg <dominik.zogg@gmail.com>

[1]: https://www.doctrine-project.org/projects/orm
[2]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-user-defined-functions.html
[3]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#custom-hydration-modes
[4]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints

