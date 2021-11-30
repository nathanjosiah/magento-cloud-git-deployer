<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Deployer\Model;

use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Util\Filesystem;
use Magento\Test\Util\SerialShellCommandSpy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CloudClonerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;
    /**
     * @var Filesystem|mixed|MockObject
     */
    private $filesystem;
    private SerialShellCommandSpy $commandSpy;
    private CloudCloner $cloner;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->commandSpy = new SerialShellCommandSpy();
        $this->cloner = new CloudCloner(
            $this->logger,
            $this->commandSpy,
            $this->filesystem
        );
    }

    public function testDefault()
    {
        $this->commandSpy->setResults(['','','']);
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->cloner->cloneToCwd('mybranch');

        $this->commandSpy->assertExpectedResults([
            ['name' => 'clone_cloud_to_tmp', 'arguments' => ['branch' => 'mybranch']],
            ['name' => 'sync_cloud_tmp_to_cwd', 'arguments' => []],
            ['name' => 'delete_cloud_tmp', 'arguments' => []],
        ]);
    }

    public function testSkipPostCleanupDefault()
    {
        $this->commandSpy->setResults(['','']);
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->cloner->cloneToCwd('mybranch', false);

        $this->commandSpy->assertExpectedResults([
            ['name' => 'clone_cloud_to_tmp', 'arguments' => ['branch' => 'mybranch']],
            ['name' => 'sync_cloud_tmp_to_cwd', 'arguments' => []],
        ]);
    }

    public function testPreCleanIfExists()
    {
        $this->commandSpy->setResults(['','','','']);
        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $this->cloner->cloneToCwd('mybranch');

        $this->commandSpy->assertExpectedResults([
            ['name' => 'delete_cloud_tmp', 'arguments' => []],
            ['name' => 'clone_cloud_to_tmp', 'arguments' => ['branch' => 'mybranch']],
            ['name' => 'sync_cloud_tmp_to_cwd', 'arguments' => []],
            ['name' => 'delete_cloud_tmp', 'arguments' => []],
        ]);
    }

    public function testPreCleanIfExistsStillWorksWithoutPostCleanup()
    {
        $this->commandSpy->setResults(['','','']);
        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $this->cloner->cloneToCwd('mybranch', false);

        $this->commandSpy->assertExpectedResults([
            ['name' => 'delete_cloud_tmp', 'arguments' => []],
            ['name' => 'clone_cloud_to_tmp', 'arguments' => ['branch' => 'mybranch']],
            ['name' => 'sync_cloud_tmp_to_cwd', 'arguments' => []],
        ]);
    }

    public function testStopAndLogUponCloneFailure()
    {
        $this->commandSpy->setResults(['somewhere in the message it says fatal: this is the error']);
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Could not clone cloud repo! Error output: somewhere in the message it says fatal: this is the error'
            );

        try {
            $this->cloner->cloneToCwd('mybranch', false);
            self::fail('There should have been an exception thrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'There was an error while cloning the repo: '
                . 'somewhere in the message it says fatal: this is the error',
                $exception->getMessage()
            );
        }

        $this->commandSpy->assertExpectedResults([
            ['name' => 'clone_cloud_to_tmp', 'arguments' => ['branch' => 'mybranch']],
        ]);
    }

    public function testStopAndLogUponCleanupFailure()
    {
        $this->commandSpy->setResults(['oh noe']);
        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Could not remove cloud_tmp');

        try {
            $this->cloner->cloneToCwd('mybranch', false);
            self::fail('There should have been an exception thrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Could not remove cloud_tmp',
                $exception->getMessage()
            );
        }

        $this->commandSpy->assertExpectedResults([
            ['name' => 'delete_cloud_tmp', 'arguments' => []],
        ]);
    }
}
