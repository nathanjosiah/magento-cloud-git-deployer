<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;
use ZipArchive;

class ArtifactManager
{
    private Filesystem $filesystem;
    private Factory $archiveFactory;
    private LoggerInterface $logger;

    /**
     * @param Filesystem $filesystem
     * @param Factory<ZipArchive> $archiveFactory
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $filesystem, Factory $archiveFactory, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->archiveFactory = $archiveFactory;
        $this->logger = $logger;
    }

    public function createArchive(string $projectPath, string $archiveName): void
    {
        $this->logger->info('<fg=blue>Creating artifacts archive');
        $artifactsDir = $projectPath . '/artifacts';
        if ($this->filesystem->fileExists($artifactsDir)) {
            $this->logger->debug('Removing existing artifacts tmp');
            $this->filesystem->rmdir($artifactsDir);
        }
        if ($this->filesystem->fileExists($projectPath . '/' . $archiveName)) {
            $this->logger->debug('Removing existing ' . $archiveName);
            $this->filesystem->deleteFile($projectPath . '/' . $archiveName);
        }

        $this->logger->debug('Creating artifacts tmp ');
        $this->filesystem->mkdir($artifactsDir);
        $files = ['.magento.env.yaml', 'composer.json', 'original-composer.json', 'composer.lock', '.magento.app.yaml', '.magento/services.yaml'];
        $token = json_decode($this->filesystem->readFile($projectPath . '/auth.json'), true)['http-basic']['github.com']['password'];
        $zip = $this->archiveFactory->create();
        $zip->open($projectPath . '/' . $archiveName, \ZipArchive::CREATE);
        foreach ($files as $file) {
            $this->logger->debug('Adding ' . $file . ' to archive');
            $contents = $this->filesystem->readFile($projectPath . '/' . $file);
            $this->filesystem->writeFile(
                $artifactsDir . '/' . basename($file),
                str_replace($token, '[redacted]', $contents)
            );
            $zip->addEmptyDir('artifacts');
            $zip->addFile($artifactsDir . '/' . $file, 'artifacts/' . basename($file));
        }
        $this->logger->info('<fg=blue>Writing artifacts archive to <fg=yellow>' . $projectPath . '/' . $archiveName);
        $zip->close();
        $this->logger->debug('Removing artifacts tmp');
        $this->filesystem->rmdir($artifactsDir);
    }
}
