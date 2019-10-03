<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\DoctrineDbServiceProvider\Logger;

use Chubbyphp\DoctrineDbServiceProvider\Logger\DoctrineDbalLogger;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\DoctrineDbServiceProvider\Logger\DoctrineDbalLogger
 *
 * @internal
 */
final class DoctrineDbalLoggerTest extends TestCase
{
    use MockByCallsTrait;

    public function testStartQuery(): void
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockByCalls(LoggerInterface::class, [
            Call::create('debug')
                ->with(
                    'select * from users where username = :username',
                    [
                        'active' => true,
                        'picture' => '(binary value)',
                        'username' => 'jöhn.doé+66666666666666@gm [...]',
                        'alias' => 'jöhn.doé+6666666666666@gmail.com',
                        'document' => '(binary value)',
                    ]
                ),
        ]);

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->startQuery(
            'select * from users where username = :username',
            [
                'active' => true,
                'picture' => base64_decode('R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs='),
                'username' => 'jöhn.doé+66666666666666@gmail.com',
                'alias' => 'jöhn.doé+6666666666666@gmail.com',
                'document' => base64_decode('R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs='),
            ]
        );
    }

    public function testStopQuery(): void
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockByCalls(LoggerInterface::class);

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->stopQuery();
    }
}
