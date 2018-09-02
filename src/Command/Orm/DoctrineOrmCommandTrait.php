<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait DoctrineOrmCommandTrait
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
    }

    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'em',
            null,
            InputOption::VALUE_OPTIONAL,
            'The entity manager to use for this command'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption('em');

        $entityManager = $this->managerRegistry->getManager($name);

        $this->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($entityManager)]));

        return parent::execute($input, $output);
    }
}
