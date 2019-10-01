<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Pimple\Container;

final class DoctrineOrmManagerRegistry implements ManagerRegistry
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Container|Connection[]
     */
    private $connections;

    /**
     * @var string
     */
    private $defaultConnectionName;

    /**
     * @var Container|EntityManager[]
     */
    private $originalManagers;

    /**
     * @var EntityManager[]
     */
    private $resetedManagers = [];

    /**
     * @var string
     */
    private $defaultManagerName;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        $this->loadConnections();

        return $this->defaultConnectionName;
    }

    /**
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     *
     * @return Connection
     */
    public function getConnection($name = null): Connection
    {
        $this->loadConnections();

        $name = $name ?? $this->getDefaultConnectionName();

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing connection with name "%s".', $name));
        }

        return $this->connections[$name];
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        $this->loadConnections();

        $connections = [];
        foreach ($this->connections->keys() as $name) {
            $connections[$name] = $this->connections[$name];
        }

        return $connections;
    }

    /**
     * @return string[]
     */
    public function getConnectionNames(): array
    {
        $this->loadConnections();

        return $this->connections->keys();
    }

    /**
     * @return string
     */
    public function getDefaultManagerName(): string
    {
        $this->loadManagers();

        return $this->defaultManagerName;
    }

    /**
     * @param string|null $name
     *
     * @return EntityManager|ObjectManager
     */
    public function getManager($name = null): ObjectManager
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!isset($this->originalManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        if (isset($this->resetedManagers[$name])) {
            return $this->resetedManagers[$name];
        }

        return $this->originalManagers[$name];
    }

    /**
     * @return EntityManager[]|ObjectManager[]
     */
    public function getManagers(): array
    {
        $this->loadManagers();

        $managers = [];
        foreach ($this->originalManagers->keys() as $name) {
            if (isset($this->resetedManagers[$name])) {
                $manager = $this->resetedManagers[$name];
            } else {
                $manager = $this->originalManagers[$name];
            }

            $managers[$name] = $manager;
        }

        return $managers;
    }

    /**
     * @return array
     */
    public function getManagerNames(): array
    {
        $this->loadManagers();

        return $this->originalManagers->keys();
    }

    /**
     * @param string|null $name
     *
     * @return EntityManager|ObjectManager
     */
    public function resetManager($name = null)
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!isset($this->originalManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        $originalManager = $this->originalManagers[$name];

        /** @var callable $entityManagerFactory */
        $entityManagerFactory = $this->container['doctrine.orm.em.factory'];

        $this->resetedManagers[$name] = $entityManagerFactory(
            $originalManager->getConnection(),
            $originalManager->getConfiguration(),
            $originalManager->getEventManager()
        );

        return $this->resetedManagers[$name];
    }

    /**
     * @param string $alias
     *
     * @throws ORMException
     *
     * @return string
     */
    public function getAliasNamespace($alias): string
    {
        foreach ($this->getManagerNames() as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $e) {
                // throw the exception only if no manager can solve it
            }
        }
        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * @param string $persistentObject
     * @param null   $persistentManagerName
     *
     * @return EntityRepository|ObjectRepository
     */
    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        return $this->getManager($persistentManagerName)->getRepository($persistentObject);
    }

    /**
     * @param string $class
     *
     * @return EntityManager|ObjectManager|null
     */
    public function getManagerForClass($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        if ($reflectionClass->implementsInterface(Proxy::class)) {
            $class = $reflectionClass->getParentClass()->name;
        }

        foreach ($this->getManagerNames() as $name) {
            $manager = $this->getManager($name);
            if (!$manager->getMetadataFactory()->isTransient($class)) {
                return $manager;
            }
        }
    }

    private function loadConnections()
    {
        if (null === $this->connections) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
    }

    private function loadManagers()
    {
        if (null === $this->originalManagers) {
            $this->originalManagers = $this->container['doctrine.orm.ems'];
            $this->defaultManagerName = $this->container['doctrine.orm.ems.default'];
        }
    }
}
