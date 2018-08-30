<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\ClassMap\Mapping;

use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapMappingInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class ClassMapMapping implements ClassMapMappingInterface
{
    /**
     * @param ClassMetadata $metadata
     *
     * @throws MappingException
     */
    public function configureMapping(ClassMetadata $metadata)
    {
        $metadata->setPrimaryTable(['name' => 'php']);

        $metadata->mapField([
            'id' => true,
            'fieldName' => 'id',
            'type' => 'string',
        ]);

        $metadata->mapField([
            'fieldName' => 'name',
            'type' => 'string',
        ]);
    }
}
