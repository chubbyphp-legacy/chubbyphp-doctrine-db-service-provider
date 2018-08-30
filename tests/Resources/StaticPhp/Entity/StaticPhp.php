<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\StaticPhp\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;

class StaticPhp
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param ClassMetadata $metadata
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public static function loadMetadata(ClassMetadata $metadata)
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
