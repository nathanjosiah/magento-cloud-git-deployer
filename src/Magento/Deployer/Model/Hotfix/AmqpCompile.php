<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Hotfix;

use Magento\Deployer\Model\AppYaml;
use Magento\Deployer\Model\HotfixInterface;
use Magento\Deployer\Model\ObjectManager\Factory;
use Psr\Log\LoggerInterface;

class AmqpCompile implements HotfixInterface
{
    private LoggerInterface $logger;
    private Factory $yamlFactory;

    /**
     * @param LoggerInterface $logger
     * @param Factory<AppYaml> $yamlFactory
     */
    public function __construct(LoggerInterface $logger, Factory $yamlFactory)
    {
        $this->logger = $logger;
        $this->yamlFactory = $yamlFactory;
    }

    public function apply(): void
    {
        $appYaml = $this->yamlFactory->create(['path'=> getcwd()]);
        $this->logger->info('<fg=cyan>Adjusting build hook');
        $appYaml->prependCommandToHook(
            'rm -rf vendor/magento/framework/Amqp',
            AppYaml::HOOK_BUILD
        );
        $appYaml->prependCommandToHook(
            'set -e',
            AppYaml::HOOK_BUILD
        );
        $appYaml->write();
    }

    public function getConfirmationQuestions(): array
    {
        return [];
    }
}
