<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Command\Orm;

use Doctrine\ORM\Tools\Console\Command\RunDqlCommand as BaseRunDqlCommand;

final class RunDqlCommand extends BaseRunDqlCommand
{
    use DoctrineOrmCommandTrait;
}
