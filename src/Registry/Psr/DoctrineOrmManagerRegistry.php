<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry\Psr;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerInterface;

final class DoctrineOrmManagerRegistry implements ManagerRegistry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ContainerInterface
     */
    private $connections;

    /**
     * @var array<int, string>
     */
    private $connectionNames;

    /**
     * @var string
     */
    private $defaultConnectionName;

    /**
     * @var ContainerInterface
     */
    private $originalEntityManagers;

    /**
     * @var array<string, EntityManagerInterface>
     */
    private $resetedManagers = [];

    /**
     * @var array<int, string>
     */
    private $managerNames;

    /**
     * @var string
     */
    private $defaultManagerName;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getDefaultConnectionName(): string
    {
        $this->loadConnections();

        return $this->defaultConnectionName;
    }

    /**
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     */
    public function getConnection($name = null): Connection
    {
        $this->loadConnections();

        $name = $name ?? $this->getDefaultConnectionName();

        if (!$this->connections->has($name)) {
            throw new \InvalidArgumentException(sprintf('Missing connection with name "%s".', $name));
        }

        return $this->connections->get($name);
    }

    /**
     * @return array<string, Connection>
     */
    public function getConnections(): array
    {
        $this->loadConnections();

        $connections = [];
        /** @var string $name */
        foreach ($this->connectionNames as $name) {
            /** @var Connection $connection */
            $connection = $this->connections->get($name);
            $connections[$name] = $connection;
        }

        return $connections;
    }

    /**
     * @return array<string>
     */
    public function getConnectionNames(): array
    {
        $this->loadConnections();

        return $this->connectionNames;
    }

    public function getDefaultManagerName(): string
    {
        $this->loadManagers();

        return $this->defaultManagerName;
    }

    /**
     * @param string|null $name
     *
     * @return EntityManagerInterface|ObjectManager
     */
    public function getManager($name = null): ObjectManager
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!$this->originalEntityManagers->has($name)) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        if (isset($this->resetedManagers[$name])) {
            return $this->resetedManagers[$name];
        }

        return $this->originalEntityManagers->get($name);
    }

    /**
     * @return array<string, EntityManagerInterface>|array<string, ObjectManager>
     */
    public function getManagers(): array
    {
        $this->loadManagers();

        $entityManagers = [];
        /** @var string $name */
        foreach ($this->managerNames as $name) {
            /* @var EntityManagerInterface $entityManager */
            if (isset($this->resetedManagers[$name])) {
                $entityManager = $this->resetedManagers[$name];
            } else {
                $entityManager = $this->originalEntityManagers->get($name);
            }

            $entityManagers[$name] = $entityManager;
        }

        return $entityManagers;
    }

    /**
     * @return array<string>
     */
    public function getManagerNames(): array
    {
        $this->loadManagers();

        return $this->managerNames;
    }

    /**
     * @param string|null $name
     *
     * @return EntityManagerInterface|ObjectManager
     */
    public function resetManager($name = null)
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!$this->originalEntityManagers->has($name)) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        /** @var EntityManagerInterface $originalEntityManager */
        $originalEntityManager = $this->originalEntityManagers->get($name);

        /** @var callable $entityManagerFactory */
        $entityManagerFactory = $this->container->get('doctrine.orm.em.factory');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $entityManagerFactory(
            $originalEntityManager->getConnection(),
            $originalEntityManager->getConfiguration(),
            $originalEntityManager->getEventManager()
        );

        $this->resetedManagers[$name] = $entityManager;

        return $entityManager;
    }

    /**
     * @param string $alias
     *
     * @throws ORMException
     */
    public function getAliasNamespace($alias): string
    {
        foreach ($this->getManagerNames() as $name) {
            try {
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $this->getManager($name);

                return $entityManager->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $exception) {
                // throw the exception only if no manager can solve it
            }
        }
        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * @param string      $persistentObject
     * @param string|null $persistentManagerName
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
     * @return EntityManagerInterface|ObjectManager|null
     */
    public function getManagerForClass($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        if ($reflectionClass->implementsInterface(Proxy::class)) {
            /** @var \ReflectionClass $reflectionParentClass */
            $reflectionParentClass = $reflectionClass->getParentClass();
            $class = $reflectionParentClass->getName();
        }

        foreach ($this->getManagerNames() as $name) {
            $entityManager = $this->getManager($name);
            if (!$entityManager->getMetadataFactory()->isTransient($class)) {
                return $entityManager;
            }
        }
    }

    private function loadConnections(): void
    {
        if (null === $this->connections) {
            $this->connections = $this->container->get('doctrine.dbal.dbs');
            $this->connectionNames = $this->container->get('doctrine.dbal.dbs.name');
            $this->defaultConnectionName = $this->container->get('doctrine.dbal.dbs.default');
        }
    }

    private function loadManagers(): void
    {
        if (null === $this->originalEntityManagers) {
            $this->originalEntityManagers = $this->container->get('doctrine.orm.ems');
            $this->managerNames = $this->container->get('doctrine.orm.ems.name');
            $this->defaultManagerName = $this->container->get('doctrine.orm.ems.default');
        }
    }
}
