<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;

/**
 * @see https://github.com/doctrine/DoctrineBundle/blob/master/Command/CreateDatabaseDoctrineCommand.php
 */
class CreateDatabaseDoctrineCommand extends Command
{
    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @param ConnectionRegistry $connectionRegistry
     */
    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        parent::__construct();

        $this->connectionRegistry = $connectionRegistry;
    }

    protected function configure()
    {
        $this
            ->setName('dbal:database:create')
            ->setDescription('Creates the configured database')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
            ->addOption(
                'if-not-exists',
                null,
                InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the database already exists'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates the default connections database:

    <info>php %command.full_name%</info>

You can also optionally specify the name of a connection to create the database for:

    <info>php %command.full_name% --connection=default</info>
EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $this->getConnectionName($input);

        $connection = $this->connectionRegistry->getConnection($connectionName);

        $ifNotExists = $input->getOption('if-not-exists');

        $params = $this->getParams($connection);

        // Cannot inject `shard` option in parent::getDoctrineConnection
        // cause it will try to connect to a non-existing database
        $params = $this->fixShardInformation($input, $params);

        $hasPath = isset($params['path']);

        $name = $hasPath ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
        if (!$name) {
            throw new \InvalidArgumentException(
                'Connection does not contain a \'path\' or \'dbname\' parameter and cannot be dropped.'
            );
        }

        // Need to get rid of _every_ occurrence of dbname from connection configuration
        // and we have already extracted all relevant info from url
        unset($params['dbname'], $params['path'], $params['url']);

        $tmpConnection = DriverManager::getConnection($params);
        $tmpConnection->connect($input->getOption('shard'));
        $shouldNotCreateDatabase = $ifNotExists && in_array($name, $tmpConnection->getSchemaManager()->listDatabases());

        // Only quote if we don't have a path
        if (!$hasPath) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $error = false;
        try {
            if ($shouldNotCreateDatabase) {
                $output->writeln(
                    sprintf(
                        '<info>Database <comment>%s</comment> for connection named <comment>%s</comment>'
                            .' already exists. Skipped.</info>',
                        $name,
                        $connectionName
                    )
                );
            } else {
                $tmpConnection->getSchemaManager()->createDatabase($name);
                $output->writeln(
                    sprintf(
                        '<info>Created database <comment>%s</comment>'
                            .' for connection named <comment>%s</comment></info>',
                        $name,
                        $connectionName
                    )
                );
            }
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>Could not create database <comment>%s</comment>'
                        .' for connection named <comment>%s</comment></error>',
                    $name,
                    $connectionName
                )
            );
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            $error = true;
        }

        $tmpConnection->close();

        return $error ? 1 : 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getConnectionName(InputInterface $input): string
    {
        $connectionName = $input->getOption('connection');

        if ('' !== $connectionName) {
            return $connectionName;
        }

        return $this->connectionRegistry->getDefaultConnectionName();
    }

    /**
     * @param Connection $connection
     *
     * @return array
     */
    private function getParams(Connection $connection): array
    {
        $params = $connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        return $params;
    }

    /**
     * @param InputInterface $input
     * @param array          $params
     *
     * @return array
     */
    private function fixShardInformation(InputInterface $input, array $params): array
    {
        if (isset($params['shards'])) {
            $shards = $params['shards'];
            // Default select global
            $params = array_merge($params, $params['global']);
            unset($params['global']['dbname']);
            if ($input->getOption('shard')) {
                foreach ($shards as $i => $shard) {
                    if ($shard['id'] === (int) $input->getOption('shard')) {
                        // Select sharded database
                        $params = array_merge($params, $shard);
                        unset($params['shards'][$i]['dbname'], $params['id']);
                        break;
                    }
                }
            }
        }

        return $params;
    }
}
