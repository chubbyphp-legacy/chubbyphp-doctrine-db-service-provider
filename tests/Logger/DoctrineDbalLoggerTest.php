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
class DoctrineDbalLoggerTest extends TestCase
{
    use MockByCallsTrait;

    public function testStartQuery()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockByCalls(LoggerInterface::class, [
            Call::create('debug')
                ->with(
                    'select * from users where username = :username',
                    [
                        'username' => 'john.doe+66666666666666666 [...]',
                        'picture' => '(binary value)',
                        'active' => true,
                    ]
                ),
        ]);

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->startQuery(
            'select * from users where username = :username',
            [
                'username' => 'john.doe+6666666666666666666@gmail.com',
                'picture' => base64_decode('R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs='),
                'active' => true,
            ]
        );
    }

    public function testStopQuery()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::never())->method(self::anything());

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->stopQuery();
    }
}
