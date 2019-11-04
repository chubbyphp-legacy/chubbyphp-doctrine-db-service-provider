<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Unit\Driver;

use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapDriver;
use Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapMappingInterface;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Driver\ClassMapDriver
 *
 * @internal
 */
final class ClassMapDriverTest extends TestCase
{
    use MockByCallsTrait;

    public function testLoadMetadataForClass(): void
    {
        $object = $this->getObject();
        $class = get_class($object);
        $mappingObject = $this->getMappingObject();
        $mappingClass = get_class($mappingObject);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockByCalls(ClassMetadata::class, [
            Call::create('mapField')->with(['fieldName' => 'key', 'type' => 'string']),
        ]);

        $classMapDriver = new ClassMapDriver([$class => $mappingClass]);

        $classMapDriver->loadMetadataForClass($class, $classMetadata);
    }

    public function testLoadMetadataForClassWithInvalidClassMetadata(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessageRegExp(
            sprintf('/Metadata is of class "[^"]+" instead of "%s"/', preg_quote(ClassMetadata::class))
        );

        $object = $this->getObject();
        $class = get_class($object);

        /** @var ClassMetadataInterface $classMetadata */
        $classMetadata = $this->getMockByCalls(ClassMetadataInterface::class);

        $classMapDriver = new ClassMapDriver([]);
        $classMapDriver->loadMetadataForClass($class, $classMetadata);
    }

    public function testLoadMetadataForClassWithMissingMapping(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No configured mapping for document "stdClass"');

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockByCalls(ClassMetadata::class);

        $classMapDriver = new ClassMapDriver([]);

        $classMapDriver->loadMetadataForClass(\stdClass::class, $classMetadata);
    }

    public function testLoadMetadataForClassWithInvalidMapping(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessageRegExp(
            sprintf('/Class "[^"]+" does not implement the "%s"/', preg_quote(ClassMapMappingInterface::class))
        );

        $object = $this->getObject();
        $class = get_class($object);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockByCalls(ClassMetadata::class);

        $classMapDriver = new ClassMapDriver([$class => $class]);

        $classMapDriver->loadMetadataForClass($class, $classMetadata);
    }

    public function testGetAllClassNames(): void
    {
        $object = $this->getObject();
        $class = get_class($object);
        $mappingObject = $this->getMappingObject();
        $mappingClass = get_class($mappingObject);

        $classMapDriver = new ClassMapDriver([$class => $mappingClass]);

        self::assertEquals([$class], $classMapDriver->getAllClassNames());
    }

    public function testIsTransient(): void
    {
        $object = $this->getObject();
        $class = get_class($object);
        $mappingObject = $this->getMappingObject();
        $mappingClass = get_class($mappingObject);

        $classMapDriver = new ClassMapDriver([$class => $mappingClass]);

        self::assertFalse($classMapDriver->isTransient($class));
        self::assertTrue($classMapDriver->isTransient(\stdClass::class));
    }

    /**
     * @return object
     */
    private function getObject()
    {
        return new class() {
        };
    }

    /**
     * @return object
     */
    private function getMappingObject()
    {
        return new class() implements ClassMapMappingInterface {
            /**
             * @throws MappingException
             */
            public function configureMapping(ClassMetadata $metadata): void
            {
                $metadata->mapField(['fieldName' => 'key', 'type' => 'string']);
            }
        };
    }
}
