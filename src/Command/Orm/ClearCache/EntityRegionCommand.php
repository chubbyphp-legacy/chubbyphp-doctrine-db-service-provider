<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand as BaseEntityRegionCommand;

/**
 * @codeCoverageIgnore
 */
final class EntityRegionCommand extends BaseEntityRegionCommand
{
    use DoctrineOrmCommandTrait;
}
