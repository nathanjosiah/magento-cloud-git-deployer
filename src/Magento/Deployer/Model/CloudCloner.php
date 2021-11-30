<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;

class CloudCloner
{
    private LoggerInterface $logger;
    private ShellCommand $shellCommand;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param ShellCommand $shellCommand
     * @param Filesystem $filesystem
     */
    public function __construct(LoggerInterface $logger, ShellCommand $shellCommand, Filesystem $filesystem)
    {
        $this->logger = $logger;
        $this->shellCommand = $shellCommand;
        $this->filesystem = $filesystem;
    }

    public function cloneToCwd(string $branch, $cleanup = true)
    {
        if ($this->filesystem->fileExists('./cloud_tmp')) {
            $this->logger->info('<fg=blue>Existing cloud_tmp found. Deleting.');
            $this->cleanup();
        }

        $this->logger->info('<fg=blue>Cloning cloud repo with branch <fg=yellow>'. $branch);
        $result = $this->shellCommand->executeCommandWithArguments('clone_cloud_to_tmp', ['branch' => $branch]);

        if (strpos($result, 'fatal:') !== false) {
            $this->logger->error('Could not clone cloud repo! Error output: ' . $result);
            throw new \RuntimeException('There was an error while cloning the repo: ' . $result);
        }

        $this->logger->info('<fg=blue>Transferring mainline files.');
        $this->shellCommand->executeCommandWithArguments('sync_cloud_tmp_to_cwd', []);

        if ($cleanup) {
            $this->cleanup();
        }
    }

    public function cleanup(): void
    {
        if ($this->shellCommand->executeCommandWithArguments('delete_cloud_tmp', [])) {
            $this->logger->error('Could not remove cloud_tmp');
            throw new \RuntimeException('Could not remove cloud_tmp');
        }
    }
}
