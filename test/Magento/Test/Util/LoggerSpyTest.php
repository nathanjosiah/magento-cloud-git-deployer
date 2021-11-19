<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LoggerSpyTest extends TestCase
{
    public function testLogsAreLogged()
    {
        $logger = new LoggerSpy();
        $logger->debug('a debug');
        $logger->warning('debug2');
        $logger->log(123, 'message 3');
        $logs = $logger->getLogs();

        self::assertCount(3, $logs);
        self::assertSame('a debug', $logs[0]['message']);
        self::assertSame(LogLevel::DEBUG, $logs[0]['level']);
        self::assertSame('debug2', $logs[1]['message']);
        self::assertSame(LogLevel::WARNING, $logs[1]['level']);
        self::assertSame('message 3', $logs[2]['message']);
        self::assertSame(123, $logs[2]['level']);
    }
}
