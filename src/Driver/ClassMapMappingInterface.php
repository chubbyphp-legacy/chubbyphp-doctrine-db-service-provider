<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;

interface ClassMapMappingInterface
{
    /**
     * @param ClassMetadata $metadata
     *
     * @throws MappingException
     */
    public function configureMapping(ClassMetadata $metadata);
}
