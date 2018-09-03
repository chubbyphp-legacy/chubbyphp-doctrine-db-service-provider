<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand as BaseQueryRegionCommand;

final class QueryRegionCommand extends BaseQueryRegionCommand
{
    use DoctrineOrmCommandTrait;
}
