<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/** @var ClassMetadata $metadata */
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
