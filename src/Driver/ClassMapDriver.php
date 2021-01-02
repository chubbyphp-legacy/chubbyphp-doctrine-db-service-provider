<?php

declare(strict_types=1);

namespace Chubbyphp\DoctrineDbServiceProvider\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class ClassMapDriver implements MappingDriver
{
    /**
     * @var array<string, string>
     */
    private array $classMap;

    /**
     * @param array<string, string> $classMap
     */
    public function __construct(array $classMap)
    {
        $this->classMap = $classMap;
    }

    /**
     * @param string $className
     *
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadataInterface $metadata): void
    {
        if (false === $metadata instanceof ClassMetadata) {
            throw new MappingException(
                sprintf('Metadata is of class "%s" instead of "%s"', get_class($metadata), ClassMetadata::class)
            );
        }

        if (false === isset($this->classMap[$className])) {
            throw new MappingException(
                sprintf('No configured mapping for document "%s"', $className)
            );
        }

        $mappingClassName = $this->classMap[$className];

        if (false === ($mapping = new $mappingClassName()) instanceof ClassMapMappingInterface) {
            throw new MappingException(
                sprintf('Class "%s" does not implement the "%s"', $mappingClassName, ClassMapMappingInterface::class)
            );
        }

        $mapping->configureMapping($metadata);
    }

    /**
     * @return array<string>
     */
    public function getAllClassNames(): array
    {
        return array_keys($this->classMap);
    }

    /**
     * @param string $className
     */
    public function isTransient($className): bool
    {
        if (isset($this->classMap[$className])) {
            return false;
        }

        return true;
    }
}
