<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\ClearCache;

use Chubbyphp\DoctrineDbServiceProvider\Command\Orm\DoctrineOrmCommandTrait;
use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand as BaseMetadataCommand;

/**
 * @codeCoverageIgnore
 */
final class MetadataCommand extends BaseMetadataCommand
{
    use DoctrineOrmCommandTrait;
}
