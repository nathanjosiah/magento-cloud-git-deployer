<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Config;


use Magento\Deployer\Model\ShellExecutor;
use Psr\Log\LoggerInterface;

class ComposerResolver
{
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     */
    public function __construct(LoggerInterface $logger, ShellExecutor $shellExecutor)
    {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
    }

    public function resolve(): string
    {
        $this->logger->info('<fg=blue>Getting composer version');

        if (preg_match('/version (?P<version>.*?) /', $this->shellExecutor->execute('composer --version 2>&1'), $matches)) {
            if (empty($matches['version'])) {
                $this->logger->error('Could not find composer!');
                exit;
            } else {
                $this->logger->info('<fg=blue>Found composer version <fg=yellow>' . $matches['version']);
            }
        } else {
            $this->logger->error('Could not detect composer version. There may be a problem with your composer.json file.');
            exit;
        }

        return $matches['version'];
    }
}
