<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class ShellExecutorTest extends TestCase
{
    public function testExecute(): void
    {
        $logger = new class extends AbstractLogger {
            public array $logged = [];
            public function log($level, $message, array $context = array())
            {
                $this->logged[] = $message;
            }
        };

        $executor = new ShellExecutor($logger);
        $command = 'bash -c "echo -n abc"';
        $result = $executor->execute($command);
        self::assertEquals('abc', $result);
        self::assertStringContainsString($command, $logger->logged[0]);
    }
}
