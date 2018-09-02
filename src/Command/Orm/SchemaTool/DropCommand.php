<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand as BaseDropCommand;

/**
 * @codeCoverageIgnore
 */
final class DropCommand extends BaseDropCommand
{
    use CommandTrait;
}
