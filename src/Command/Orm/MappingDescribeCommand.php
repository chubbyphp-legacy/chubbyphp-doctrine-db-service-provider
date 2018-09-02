<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm;

use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand as BaseMappingDescribeCommand;

/**
 * @codeCoverageIgnore
 */
final class MappingDescribeCommand extends BaseMappingDescribeCommand
{
    use DoctrineOrmCommandTrait;
}
