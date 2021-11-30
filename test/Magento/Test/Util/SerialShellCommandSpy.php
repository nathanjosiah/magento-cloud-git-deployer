<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;

use Magento\Deployer\Model\ShellCommand;
use Magento\Deployer\Model\ShellExecutor;
use PHPUnit\Framework\TestCase;

class SerialShellCommandSpy extends ShellCommand
{
    private $executed = [];
    private array $results;

    public function __construct()
    {
        // Disable original constructor
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function executeCommandWithArguments(string $name, array $arguments): ?string
    {
        $this->executed[] = compact('name', 'arguments');

        return array_shift($this->results);
    }

    public function assertExpectedResults(array $expected)
    {
        TestCase::assertCount(count($this->executed), $expected, 'Executed count doesn\'t match invoked count');

        foreach ($expected as $index => $invocation) {
            if ($invocation['name'] !== $this->executed[$index]['name']) {
                TestCase::fail(
                    'Invocation #' . $index . ' "' . $this->executed[$index]['name']
                    . '" did not match the expected "' . $invocation['name'] . '"'
                );
            }

            TestCase::assertSame($invocation['arguments'], $this->executed[$index]['arguments']);
        }
    }
}
