<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm;

use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand as BaseEnsureProductionSettingsCommand;

final class EnsureProductionSettingsCommand extends BaseEnsureProductionSettingsCommand
{
    use DoctrineOrmCommandTrait;
}
