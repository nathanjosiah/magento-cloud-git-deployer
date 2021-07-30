<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;

class FilePurger
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

    public function purgePathWithExceptions(string $path, array $exceptions = []): void
    {
        $this->logger->info('<fg=blue>Purging folder of all but minimum files.');
        $files = scandir($path);
        array_shift($files); //.
        array_shift($files); //..
        $excludeMap = array_combine($exceptions, range(1, count($exceptions)));
        foreach ($files as $file) {
            // Don't accidentally delete other parts of the filesystem because of a symlink
            if (realpath($file) === $path . '/' . $file
                && !isset($excludeMap[$file])
            ) {
                $this->shellExecutor->execute('rm -rf ' . escapeshellarg($path . '/' . $file));
            }
        }
    }
}
