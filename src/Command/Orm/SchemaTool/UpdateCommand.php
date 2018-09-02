<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand as BaseUpdateCommand;

/**
 * @codeCoverageIgnore
 */
final class UpdateCommand extends BaseUpdateCommand
{
    use CommandTrait;
}
