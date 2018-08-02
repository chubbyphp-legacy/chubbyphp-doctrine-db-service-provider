<?php

namespace Chubbyphp\Tests\DoctrineDbServiceProvider;

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->setPsr4('Chubbyphp\Tests\DoctrineDbServiceProvider\\', __DIR__);

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
