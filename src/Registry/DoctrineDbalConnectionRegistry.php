<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Pimple\Container;

final class DoctrineDbalConnectionRegistry implements ConnectionRegistry
{
    private Container $container;

    private ?Container $connections = null;

    /**
     * @var null|array<int, string>
     */
    private ?array $connectionNames = null;

    private ?string $defaultConnectionName;

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
     * @param null|string $name
     *
     * @throws \InvalidArgumentException
     */
    public function getConnection($name = null): Connection
    {
        $this->loadConnections();

        $name ??= $this->getDefaultConnectionName();

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
        foreach ($this->connectionNames as $name) {
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

        return $this->connectionNames;
    }

    private function loadConnections(): void
    {
        if (null === $this->connections) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->connectionNames = $this->container['doctrine.dbal.dbs.name'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
    }
}
