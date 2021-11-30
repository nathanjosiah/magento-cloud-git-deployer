<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

class ShellCommand
{
    private ShellExecutor $shellExecutor;
    private array $commands;

    /**
     * @param array $commands
     * @param ShellExecutor $shellExecutor
     */
    public function __construct(array $commands, ShellExecutor $shellExecutor)
    {
        $this->shellExecutor = $shellExecutor;
        $this->commands = $commands;
    }

    public function executeCommandWithArguments(string $name, array $arguments): ?string
    {
        if (!isset($this->commands[$name])) {
            throw new \InvalidArgumentException('Command with name "' . $name . '" not found.');
        }

        $command = $this->commands[$name];

        foreach ($arguments as $argument => $value) {
            $command = str_replace('{{' . $argument . '}}', escapeshellarg($value), $command);
        }

        return $this->shellExecutor->execute($command);
    }
}
