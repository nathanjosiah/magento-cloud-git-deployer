<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;

class ShellExecutor
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute($command): ?string
    {
        $this->logger->debug('Executing <fg=yellow>' . $command);

        $startTime = time();
        $result = `$command`;
        $elapsedTime = time() - $startTime;
        $this->logger->debug('Finished executing <fg=yellow>' . $command . '<fg=default>. Command took <fg=blue>' . $elapsedTime . ' <fg=default>seconds to execute');

        return $result;
    }
}
