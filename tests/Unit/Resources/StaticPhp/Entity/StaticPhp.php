<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Resources\StaticPhp\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;

final class StaticPhp
{
    private string $id;

    private string $name;

    /**
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'static_php']);

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
