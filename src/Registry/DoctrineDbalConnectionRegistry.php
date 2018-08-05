<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Pimple\Container;

final class DoctrineDbalConnectionRegistry implements ConnectionRegistry
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
     * @return Connection
     *
     * @throws \InvalidArgumentException
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

        $connections = array();
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

    private function loadConnections()
    {
        if (null === $this->connections) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
    }
}
