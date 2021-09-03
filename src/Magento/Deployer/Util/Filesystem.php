<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Util;

use Psr\Log\LoggerInterface;

class Filesystem
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function readFile(string $path): string
    {
        $this->logger->debug('Reading file ' . $path);
        return file_get_contents($path);
    }

    public function writeFile(string $path, string $contents): void
    {
        $this->logger->debug('Writing file ' . $path);
        file_put_contents($path, $contents);
    }

    public function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    public function getFilesInDirectory(string $path): array
    {
        return scandir($path);
    }

    public function deleteFile(string $path): void
    {
        $this->logger->debug('Deleting file ' . $path);
        unlink($path);
    }

    public function realpath(string $path): ?string
    {
        return realpath($path) ?: null;
    }

    public function mkdir(string $path): bool
    {
        return mkdir($path);
    }

    public function getCwd(): string
    {
        return getcwd();
    }
}
