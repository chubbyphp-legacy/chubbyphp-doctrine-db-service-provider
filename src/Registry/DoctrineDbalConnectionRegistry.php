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
     * @var Container
     */
    private $connections;

    /**
     * @var string
     */
    private $defaultConnectionName;

    public function __construct(Container $container)
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
     * @return array<string, Connection>
     */
    public function getConnections(): array
    {
        $this->loadConnections();

        $connections = [];
        /** @var string $name */
        foreach ($this->connections->keys() as $name) {
            /** @var Connection $connection */
            $connection = $this->connections[$name];
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

        return $this->connections->keys();
    }

    private function loadConnections(): void
    {
        if (null === $this->connections) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
    }
}
