<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Hotfix;

use Magento\Deployer\Model\Composer;
use Magento\Deployer\Model\HotfixInterface;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\ShellExecutor;
use Psr\Log\LoggerInterface;

class DiCompile implements HotfixInterface
{
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;
    private Factory $composerFactory;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param Factory<Composer> $composerFactory
     */
    public function __construct(LoggerInterface $logger, ShellExecutor $shellExecutor, Factory $composerFactory)
    {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->composerFactory = $composerFactory;
    }

    public function apply(): void
    {
        $composer = $this->composerFactory->create(['path'=> getcwd()]);
        $this->logger->info('<fg=cyan>Hotfix for di:compile issue. Adding phpunit/phpunit:~5.3.0');
        $composer->addRequire('phpunit/phpunit', '~9.5.0');
        $composer->write();
        $this->logger->info('<fg=cyan>Running composer update');
        $this->shellExecutor->execute('composer update --ansi --no-interaction');
    }

    public function getConfirmationQuestions(): array
    {
        return [];
    }
}
