<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ConnectionRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://github.com/doctrine/DoctrineBundle/blob/master/Command/CreateDatabaseDoctrineCommand.php
 */
final class CreateDatabaseDoctrineCommand extends Command
{
    private ConnectionRegistry $connectionRegistry;

    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        parent::__construct();

        $this->connectionRegistry = $connectionRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setName('dbal:database:create')
            ->setDescription('Creates the configured database')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
            ->addOption(
                'if-not-exists',
                null,
                InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the database already exists'
            )
            ->setHelp(
                <<<'EOT'
                    The <info>%command.name%</info> command creates the default connections database:

                        <info>php %command.full_name%</info>

                    You can also optionally specify the name of a connection to create the database for:

                        <info>php %command.full_name% --connection=default</info>
                    EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $this->getConnectionName($input);

        /** @var Connection $connection */
        $connection = $this->connectionRegistry->getConnection($connectionName);

        $params = $this->getParams($connection);

        $dbName = $this->getDbName($params);

        $isPath = isset($params['path']);

        $ifNotExists = $input->getOption('if-not-exists');

        // Need to get rid of _every_ occurrence of dbname from connection configuration
        unset($params['dbname'], $params['path'], $params['url']);

        $tmpConnection = DriverManager::getConnection($params);
        $shouldNotCreateDatabase = $ifNotExists
            && \in_array($dbName, $tmpConnection->getSchemaManager()->listDatabases(), true);

        // Only quote if we don't have a path
        if (!$isPath) {
            $dbName = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($dbName);
        }

        return $this->createDatabase($output, $connectionName, $tmpConnection, $dbName, $shouldNotCreateDatabase);
    }

    private function getConnectionName(InputInterface $input): string
    {
        /** @var null|string $connectionName */
        $connectionName = $input->getOption('connection');

        if (null !== $connectionName) {
            return $connectionName;
        }

        return $this->connectionRegistry->getDefaultConnectionName();
    }

    /**
     * @return array<mixed>
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
     * @param array<string, string> $params
     */
    private function getDbName(array $params): string
    {
        if (isset($params['path'])) {
            return $params['path'];
        }

        if (isset($params['dbname'])) {
            return $params['dbname'];
        }

        throw new \InvalidArgumentException('Connection does not contain a \'path\' or \'dbname\' parameter.');
    }

    private function createDatabase(
        OutputInterface $output,
        string $connectionName,
        Connection $tmpConnection,
        string $dbName,
        bool $shouldNotCreateDatabase
    ): int {
        try {
            if ($shouldNotCreateDatabase) {
                $output->writeln(
                    sprintf(
                        '<info>Database <comment>%s</comment> for connection named <comment>%s</comment>'
                            .' already exists. Skipped.</info>',
                        $dbName,
                        $connectionName
                    )
                );
            } else {
                $tmpConnection->getSchemaManager()->createDatabase($dbName);
                $output->writeln(
                    sprintf(
                        '<info>Created database <comment>%s</comment>'
                             .' for connection named <comment>%s</comment>.</info>',
                        $dbName,
                        $connectionName
                    )
                );
            }

            return 0;
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>Could not create database <comment>%s</comment>.</error>', $dbName));
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return 1;
        }
    }
}
