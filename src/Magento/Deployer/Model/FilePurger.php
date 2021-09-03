<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;

class FilePurger
{
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;
    private array $defaultExclusions;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param Filesystem $filesystem
     * @param array $defaultExclusions
     */
    public function __construct(
        LoggerInterface $logger,
        ShellExecutor $shellExecutor,
        Filesystem $filesystem,
        array $defaultExclusions
    ) {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->defaultExclusions = $defaultExclusions;
        $this->filesystem = $filesystem;
    }

    public function purgePathWithExceptions(string $path, array $exceptions = []): void
    {
        $excludedDirs = $this->defaultExclusions;
        if (!empty($exceptions)) {
            $error = false;
            foreach ($exceptions as $excludePath) {
                $excludeRealPath = realpath($excludePath);
                if (!$excludeRealPath || !$this->filesystem->fileExists($excludeRealPath)) {
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
