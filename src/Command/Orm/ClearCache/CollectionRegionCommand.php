<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand as BaseCollectionRegionCommand;

final class CollectionRegionCommand extends BaseCollectionRegionCommand
{
    use DoctrineOrmCommandTrait;
}
