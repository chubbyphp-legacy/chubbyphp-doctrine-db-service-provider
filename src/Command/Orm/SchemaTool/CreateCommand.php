<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand as BaseCreateCommand;

/**
 * @codeCoverageIgnore
 */
final class CreateCommand extends BaseCreateCommand
{
    use CommandTrait;
}
