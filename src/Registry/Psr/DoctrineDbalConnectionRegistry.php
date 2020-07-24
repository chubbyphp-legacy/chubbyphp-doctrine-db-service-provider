<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry\Psr;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Psr\Container\ContainerInterface;

final class DoctrineDbalConnectionRegistry implements ConnectionRegistry
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

    private function loadConnections(): void
    {
        if (null === $this->connections) {
            $this->connections = $this->container->get('doctrine.dbal.dbs');
            $this->connectionNames = $this->container->get('doctrine.dbal.dbs.name');
            $this->defaultConnectionName = $this->container->get('doctrine.dbal.dbs.default');
        }
    }
}
