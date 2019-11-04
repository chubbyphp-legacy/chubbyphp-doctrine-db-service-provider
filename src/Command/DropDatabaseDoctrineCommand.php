<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://github.com/doctrine/DoctrineBundle/blob/master/Command/DropDatabaseDoctrineCommand.php
 */
final class DropDatabaseDoctrineCommand extends Command
{
    const RETURN_CODE_NOT_DROP = 1;

    const RETURN_CODE_NO_FORCE = 2;

    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    public function __construct(ConnectionRegistry $connectionRegistry)
    {
        parent::__construct();

        $this->connectionRegistry = $connectionRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setName('dbal:database:drop')
            ->setDescription('Drops the configured database')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
            ->addOption(
                'if-exists',
                null,
                InputOption::VALUE_NONE,
                'Don\'t trigger an error, when the database doesn\'t exist'
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command drops the default connections database:

    <info>php %command.full_name%</info>

The <info>--force</info> parameter has to be used to actually drop the database.

You can also optionally specify the name of a connection to drop the database for:

    <info>php %command.full_name% --connection=default</info>

<error>Be careful: All data in a given database will be lost when executing this command.</error>
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

        if (!$input->getOption('force')) {
            $this->writeMissingForceOutput($output, $dbName, $connectionName);

            return self::RETURN_CODE_NO_FORCE;
        }

        $isPath = isset($params['path']);

        $ifExists = $input->getOption('if-exists');

        // Need to get rid of _every_ occurrence of dbname from connection configuration
        unset($params['dbname'], $params['path'], $params['url']);

        $connection->close();
        $connection = DriverManager::getConnection($params);
        $shouldDropDatabase = !$ifExists || in_array($dbName, $connection->getSchemaManager()->listDatabases());

        // Only quote if we don't have a path
        if (!$isPath) {
            $dbName = $connection->getDatabasePlatform()->quoteSingleIdentifier($dbName);
        }

        return $this->dropDatabase($output, $connectionName, $connection, $dbName, $shouldDropDatabase);
    }

    private function getConnectionName(InputInterface $input): string
    {
        /** @var string|null $connectionName */
        $connectionName = $input->getOption('connection');

        if (null !== $connectionName) {
            return $connectionName;
        }

        return $this->connectionRegistry->getDefaultConnectionName();
    }

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

    private function writeMissingForceOutput(OutputInterface $output, string $dbName, string $connectionName): void
    {
        $output->writeln(
            '<error>ATTENTION:</error> This operation should not be executed in a production environment.'
        );
        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Would drop the database <comment>%s</comment> for connection'
                    .' named <comment>%s</comment>.</info>',
                $dbName,
                $connectionName
            )
        );
        $output->writeln('Please run the operation with --force to execute');
        $output->writeln('<error>All data will be lost!</error>');
    }

    private function dropDatabase(
        OutputInterface $output,
        string $connectionName,
        Connection $connection,
        string $dbName,
        bool $shouldDropDatabase
    ): int {
        try {
            if ($shouldDropDatabase) {
                $connection->getSchemaManager()->dropDatabase($dbName);
                $output->writeln(
                    sprintf(
                        '<info>Dropped database <comment>%s</comment> for connection'
                            .' named <comment>%s</comment>.</info>',
                        $dbName,
                        $connectionName
                    )
                );
            } else {
                $output->writeln(
                    sprintf(
                        '<info>Database <comment>%s</comment> for connection named <comment>%s</comment>'
                            .' doesn\'t exist. Skipped.</info>',
                        $dbName,
                        $connectionName
                    )
                );
            }
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf(
                    '<error>Could not drop database <comment>%s</comment> for connection'
                        .' named <comment>%s</comment>.</error>',
                    $dbName,
                    $connectionName
                )
            );
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::RETURN_CODE_NOT_DROP;
        }

        return 0;
    }
}
