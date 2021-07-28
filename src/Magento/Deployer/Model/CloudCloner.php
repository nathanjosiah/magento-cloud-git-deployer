<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;

class CloudCloner
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ShellExecutor
     */
    private $shellExecutor;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     */
    public function __construct(LoggerInterface $logger, ShellExecutor $shellExecutor)
    {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
    }

    public function cloneToCwd(string $branch, $cleanup = true)
    {
        if (file_exists('./cloud_tmp')) {
            $this->logger->info('<fg=blue>Existing cloud_tmp found. Deleting.');
            $this->cleanup();
        }

        $this->logger->info('<fg=blue>Cloning cloud repo with branch <fg=yellow>'. $branch);
        $result = $this->shellExecutor->execute('git clone --depth 1 --branch \'' . $branch . '\' git@github.com:magento/magento-cloud.git cloud_tmp 2>&1');

        if (strpos($result, 'fatal:') !== false) {
            $this->logger->error('Could not clone cloud repo!');
            exit;
        }

        $this->logger->info('<fg=blue>Transferring mainline files.');
        $this->shellExecutor->execute('rsync -av cloud_tmp/ . --exclude=.git --exclude=.github');

        if ($cleanup) {
            $this->cleanup();
        }
    }

    public function cleanup(): void
    {
        if ($this->shellExecutor->execute('rm -rf ./cloud_tmp')) {
            $this->logger->error('Could not remove cloud_tmp');
            exit;
        }
    }
}
