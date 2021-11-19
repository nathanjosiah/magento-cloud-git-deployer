<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Deployer\Model;

use Magento\Deployer\Model\ShellExecutor;
use Magento\Test\Util\LoggerSpy;
use PHPUnit\Framework\TestCase;

class ShellExecutorTest extends TestCase
{
    public function testExecute(): void
    {
        $logger = new LoggerSpy();
        $executor = new ShellExecutor($logger);
        $command = 'bash -c "echo -n abc"';
        $result = $executor->execute($command);
        self::assertEquals('abc', $result);
        self::assertStringContainsString($command, $logger->getLogs()[0]['message']);
    }
}
