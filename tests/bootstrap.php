<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider;

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
