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
    private array $defaultExclusions;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param array $defaultExclusions
     */
    public function __construct(LoggerInterface $logger, ShellExecutor $shellExecutor, array $defaultExclusions)
    {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->defaultExclusions = $defaultExclusions;
    }

    public function purgePathWithExceptions(string $path, array $exceptions = []): void
    {
        $excludedDirs = $this->defaultExclusions;
        if (!empty($exceptions)) {
            $error = false;
            foreach ($exceptions as $excludePath) {
                $excludeRealPath = realpath($excludePath);
                if (!$excludeRealPath || !file_exists($excludeRealPath)) {
                    $this->logger->error("Excluded path $excludePath does not exist");
                    $error = true;
                } else {
                    if (strpos($excludeRealPath, $path) !== 0) {
                        $this->logger->error("Exclude path $excludeRealPath isn't in project directory.");
                        $error = true;
                    } else {
                        $excludedDirs[] = substr($excludeRealPath, strlen($path) + 1);
                    }
                }
            }
            if ($error) {
                exit;
            }
        }

        $this->logger->info('<fg=blue>Purging folder of all but minimum files.');
        $files = scandir($path);
        array_shift($files); //.
        array_shift($files); //..
        $excludeMap = array_combine($excludedDirs, range(1, count($excludedDirs)));
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
