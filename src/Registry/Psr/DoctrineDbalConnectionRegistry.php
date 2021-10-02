<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Registry\Psr;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Psr\Container\ContainerInterface;

final class DoctrineDbalConnectionRegistry implements ConnectionRegistry
{
    private ContainerInterface $container;

    private ?ContainerInterface $connections = null;

    /**
     * @var null|array<int, string>
     */
    private ?array $connectionNames = null;

    private ?string $defaultConnectionName = null;

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
     * @param null|string $name
     *
     * @throws \InvalidArgumentException
     */
    public function getConnection($name = null): Connection
    {
        $this->loadConnections();

        $name ??= $this->getDefaultConnectionName();

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
