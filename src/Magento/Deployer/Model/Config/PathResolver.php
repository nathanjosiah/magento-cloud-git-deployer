<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Config;


use Psr\Log\LoggerInterface;

class PathResolver
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function resolveExistingProjectWithUserInput(?string $path = null): string
    {
        if (!empty($path)) {
            $this->logger->info('<fg=blue>Running in <fg=yellow>' . $path);
            $path = realpath($path);
            if ($path) {
                $this->logger->info('<fg=blue>Resolved to <fg=yellow>' . $path);
            } else {
                $this->logger->error('Could not resolve given path!');
                exit;
            }
        } else {
            $path = realpath(getcwd());
            $this->logger->info('<fg=blue>No path provided. Using working directory <fg=yellow>' . $path);
        }

        // Sanity checks to ensure the path is probably correct
        if (!file_exists($path . '/.magento.env.yaml')) {
            $this->logger->error('No .magento.env.yaml found in project directory. Assuming this is the wrong directory.');
            exit;
        }
        if (!file_exists($path . '/.git')) {
            $this->logger->error('No .git folder found in project directory. Assuming this is the wrong directory.');
            exit;
        }
        if (!file_exists($path . '/app')) {
            $this->logger->error('No app folder found in project directory. Assuming this is the wrong directory.');
            exit;
        }

        return $path;
    }

    public function resolveNewProjectWithUserInput(?string $path = null): string
    {
        if (!empty($path)) {
            $this->logger->info('<fg=blue>Running in <fg=yellow>' . $path);
            if (!file_exists($path)) {
                $this->logger->info('<fg=blue>Making directory <fg=yellow>' . $path);
                if (!mkdir($path)) {
                    $this->logger->error('Could not make directory ' . $path);
                    exit;
                }
            }
            $path = realpath($path);
            if ($path) {
                $this->logger->info('<fg=blue>Resolved to <fg=yellow>' . $path);
            } else {
                $this->logger->error('Could not resolve given path!');
                exit;
            }
        } else {
            $path = getcwd();
            $this->logger->info('<fg=blue>No path provided. Using working directory <fg=yellow>' . $path);
        }

        return $path;
    }
}
