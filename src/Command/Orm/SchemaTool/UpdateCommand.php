<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand as BaseUpdateCommand;

final class UpdateCommand extends BaseUpdateCommand
{
    use DoctrineOrmCommandTrait;
}
