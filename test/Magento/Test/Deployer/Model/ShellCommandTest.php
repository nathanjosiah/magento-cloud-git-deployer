<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Deployer\Model;

use Magento\Deployer\Model\ShellCommand;
use Magento\Deployer\Model\ShellExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShellCommandTest extends TestCase
{
    private ShellCommand $shellCommand;

    /**
     * @var ShellExecutor|MockObject
     */
    private $executor;

    protected function setUp(): void
    {
        $this->executor = $this->createMock(ShellExecutor::class);
        $this->shellCommand = new ShellCommand(
            [
                'no_args' => 'foo bar',
                'with_args' => 'foo {{arg1}} {{arg2}} {{arg1}}',
            ],
            $this->executor
        );
    }

    public function testExceptionThrownWhenInvalidCommandGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Command with name "foobar" not found.');
        $this->shellCommand->executeCommandWithArguments('foobar', []);
    }

    public function testWithoutArguments()
    {
        $this->executor->expects(self::once())
            ->method('execute')
            ->with('foo bar')
            ->willReturn('abc');

        $result = $this->shellCommand->executeCommandWithArguments('no_args', []);

        self::assertSame('abc', $result);
    }

    public function testWithArguments()
    {
        $this->executor->expects(self::once())
            ->method('execute')
            ->with('foo \'some \'\\\'\' "value"\' \'cba\' \'some \'\\\'\' "value"\'')
            ->willReturn('abc123');

        $result = $this->shellCommand->executeCommandWithArguments(
            'with_args',
            [
                'arg1' => 'some \' "value"',
                'arg2' => 'cba',
            ]
        );

        self::assertSame('abc123', $result);
    }
}
