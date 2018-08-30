<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Resources\Annotation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Annotation
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $name;
}
