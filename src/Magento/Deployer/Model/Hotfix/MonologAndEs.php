<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Hotfix;

use Magento\Deployer\Model\HotfixInterface;
use Magento\Deployer\Model\ShellExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MonologAndEs implements HotfixInterface
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

    public function apply(): void
    {
        $composer = json_decode(file_get_contents('composer.json'), true);
        $composer['repositories']['magento-cloud-patches']['url'] = 'git@github.com:magento-cia/magento-cloud-components.git';
        $composer['repositories']['magento-cloud-components']['url'] = 'git@github.com:magento/magento-cloud-components.git';
        $composer['repositories']['ece-tools']['url'] = 'git@github.com:magento-cia/ece-tools.git';
        $composer['require']['magento/ece-tools'] = 'dev-ACMP-1263-2';
        $composer['require']['magento/magento-cloud-patches'] = 'dev-ACMP-1263 as 1.0.11';
        $composer['require']['magento/magento-cloud-components'] = 'dev-ACMP-1263-2 as 1.0.8';
        $composer['require']['elasticsearch/elasticsearch'] = 'v7.11.0';
        $this->logger->info('<fg=cyan>Overwriting composer.json with hotfix changes');
        file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));
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
