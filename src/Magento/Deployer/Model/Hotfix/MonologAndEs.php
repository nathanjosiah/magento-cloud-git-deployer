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
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MonologAndEs implements HotfixInterface
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
        $composer->addRequire('magento/ece-tools', 'dev-ACMP-1263 as 2002.1.99');
        $composer->addRequire('magento/magento-cloud-patches', 'dev-ACMP-1263 as 1.0.99');
        $composer->addRequire('magento/magento-cloud-components', 'dev-ACMP-1263 as 1.0.99');
        $this->logger->info('<fg=cyan>Overwriting composer.json with hotfix changes');
        $composer->write();
        $this->logger->info('<fg=cyan>Running composer update');
        $this->shellExecutor->execute('composer update --ansi --no-interaction');
    }

    public function getConfirmationQuestions(): array
    {
        return [
            new ConfirmationQuestion('<fg=red>This fix requires that dev:git:update-composer has already been run, are you ready to apply this fix? <fg=blue>y/n <fg=green>[default no]<fg=default>: ', false)
        ];
    }
}
