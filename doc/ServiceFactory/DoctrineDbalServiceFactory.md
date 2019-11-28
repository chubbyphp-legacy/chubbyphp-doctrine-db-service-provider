# DoctrineDbalServiceFactory

The *DoctrineDbalServiceFactory* provides integration with the [Doctrine Dbal][1].

## Parameters

* **doctrine.dbal.db.options**: Array of Doctrine DBAL options.

    These options are available:

    * **configuration**

        * **auto_commit**: Auto commit. Defaults to `true`
        * **cache.result**: Array with the cache settings, defaults to `['type' => 'array']`.
            Can be any of: `apcu`, `array`. Add additional cache provider factories by adding new service:
            `$container['doctrine.dbal.db.cache_factory.<type>']`
        * **filter_schema_assets_expression**: An expression to filter for schema (tables)
            `deprecated, use schema_assets_filter instead`
        * **schema_assets_filter**: An filter callable for schema (tables)

    * **connection**:

        * **charset**: Specifies the charset used when connecting to the database.
            Only relevant for `pdo_mysql`, and `pdo_oci/oci8`,
        * **dbname**: The name of the database to connect to.
        * **driver**: The database driver to use, defaults to `pdo_mysql`.
            Can be any of: `pdo_mysql`, `pdo_sqlite`, `pdo_pgsql`,
            `pdo_oci`, `oci8`, `ibm_db2`, `pdo_ibm`, `pdo_sqlsrv`.
        * **host**: The host of the database to connect to. Defaults to localhost.
        * **password**: The password of the database to connect to.
        * **path**: Only relevant for `pdo_sqlite`, specifies the path to the SQLite database.
        * **port**: Only relevant for `pdo_mysql`, `pdo_pgsql`, and `pdo_oci/oci8`,
        * **user**: The user of the database to connect to. Defaults to root.

  These and additional options are described in detail in [Doctrine Dbal Configuration][2].

* **doctrine.dbal.types**: Array of dbal types (additional and/or override)
Example: [Type::STRING => StringType::class]

## Services

* **doctrine.dbal.connection_registry**: The connection registry, instance of `Doctrine\Common\Persistence\ConnectionRegistry`.
* **doctrine.dbal.db**: The database connection, instance of `Doctrine\DBAL\Connection`.
* **doctrine.dbal.db.config**: The doctrine configuration, instance of `Doctrine\DBAL\Configuration`.
* **doctrine.dbal.db.event_manager**: The doctrine event manager, instance of  `Doctrine\Common\EventManager`.

## Registering

### Single connection

```php
$container = new Container();

$container->factories((new Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineDbalServiceFactory())());

$container->factory('doctrine.dbal.db.options', static function () {
    return [
        'connection' => [
            'dbname'    => 'my_database',
            'host'      => 'mysql.someplace.tld',
            'password'  => 'my_password',
            'user'      => 'my_username',
        ],
    ];
});
```

### Multiple connections

```php
$container = new Container();

$container->factories((new Chubbyphp\DoctrineDbServiceProvider\ServiceFactory\DoctrineDbalServiceFactory())());

$container->factory('doctrine.dbal.dbs.options', static function () {
    return [
        'mysql_read' => [
            'connection' => [
                'dbname'    => 'my_database',
                'host'      => 'mysql.read.someplace.tld',
                'password'  => 'my_password',
                'user'      => 'my_username',
            ],
        ],
        'mysql_write' => [
            'connection' => [
                'dbname'    => 'my_database',
                'host'      => 'mysql.write.someplace.tld',
                'password'  => 'my_password',
                'user'      => 'my_username',
            ],
        ],
    ];
});
```

## Usage

### Single connection

```php
$container->get('doctrine.dbal.db')
    ->createQueryBuilder()
    ->select('u')
    ->from('users', 'u')
    ->where($qb->expr()->eq('u.username', ':username'))
    ->setParameter('username', 'john.doe@domain.com')
    ->execute()
    ->fetch(\PDO::FETCH_ASSOC);
```

### Multiple connections

```php
$container->get('doctrine.dbal.dbs')->get('name')
    ->createQueryBuilder()
    ->select('u')
    ->from('users', 'u')
    ->where($qb->expr()->eq('u.username', ':username'))
    ->setParameter('username', 'john.doe@domain.com')
    ->execute()
    ->fetch(\PDO::FETCH_ASSOC);
```

## Copyright

Dominik Zogg <dominik.zogg@gmail.com>

[1]: https://www.doctrine-project.org/projects/dbal
[2]: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
