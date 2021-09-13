<?php

namespace Magento\Deployer\Model\PrepareStrategy;

use Magento\Deployer\Model\AppYaml;
use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Composer;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\FilePurger;
use Magento\Deployer\Model\HotfixApplier;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\ShellExecutor;
use Psr\Log\LoggerInterface;

class TraditionalStrategy {
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;
    private CloudCloner $cloudCloner;
    private FilePurger $filePurger;
    private HotfixApplier $hotfixApplier;
    private Factory $composerFactory;
    private Factory $appYamlFactory;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param CloudCloner $cloudCloner
     * @param FilePurger $filePurger
     * @param HotfixApplier $hotfixApplier
     * @param Factory<Composer> $composerFactory
     * @param Factory<AppYaml> $appYamlFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ShellExecutor $shellExecutor,
        CloudCloner $cloudCloner,
        FilePurger $filePurger,
        HotfixApplier $hotfixApplier,
        Factory $composerFactory,
        Factory $appYamlFactory
    ) {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->cloudCloner = $cloudCloner;
        $this->filePurger = $filePurger;
        $this->hotfixApplier = $hotfixApplier;
        $this->composerFactory = $composerFactory;
        $this->appYamlFactory = $appYamlFactory;
    }

    public function execute(
        PrepareConfig $config
    ): void
    {
        if (!is_writable($config->getPath())) {
            $this->logger->error('Directory is not writable!');
            exit;
        }

        chdir($config->getPath());

        $this->logger->info('<fg=blue>Using ece-tools <fg=yellow>' . $config->getEceVersion());

        $this->logger->info('<fg=blue>Purging folder of all but minimum files.');
        $this->filePurger->purgePathWithExceptions($config->getPath(), $config->getExclude());

        $this->logger->info('<fg=blue>Cloning mainline cloud project');
        $this->cloudCloner->cloneToCwd($config->getCloudBranch(), false);

        if (array_search('amqp-compile', $config->getHotfixes()) !== false) {
            $this->hotfixApplier->apply('amqp-compile');
        }

        $this->logger->info('<fg=blue>Configuring composer');
        $composer = $this->composerFactory->create(['path' => $config->getPath()]);
        $composer->addInitialGitSupport($config->getEceVersion());

        if ($config->isComposer2()) {
            $this->logger->info('<fg=blue>Configuring .magento.app.yaml for composer 2.');
            $appYaml = $this->appYamlFactory->create(['path' => $config->getPath()]);
            $appYaml->addComposer2Support();
            $appYaml->write();
        } else {
            $this->logger->info('<fg=blue>Using composer 1 so no .magento.app.yaml changes needed.');
        }

        $this->logger->info('<fg=blue>Writing composer.json');
        $composer->write();

        if (array_search('monolog-and-es', $config->getHotfixes()) !== false) {
            $this->hotfixApplier->apply('monolog-and-es');
        } else {
            $this->logger->info('<fg=blue>Running composer update');
            $this->shellExecutor->execute('composer update --ansi --no-interaction');
        }

        $this->logger->info('<fg=blue>Saving copy of composer.json before dev:git:update-composer to <fg=yellow> original-composer.json');
        $composer->write('original-composer.json');

        $this->logger->info('<fg=blue>Running <fg=yellow>dev:git:update-composer');
        $this->shellExecutor->execute('vendor/bin/ece-tools dev:git:update-composer');

        $this->logger->info('<fg=blue>Replacing composer autoload with mainline cloud autoload.');
        $mainlineComposer = $this->composerFactory->create(['path' => $config->getPath() . '/cloud_tmp']);
        $localComposer = $this->composerFactory->create(['path' => $config->getPath()]);
        $localComposer['autoload'] = $mainlineComposer['autoload'];

        $this->logger->info('<fg=blue>composer.json after dev:git:update-composer saved to composer.json');
        $localComposer->write();
        $this->cloudCloner->cleanup();

        $this->logger->info('<fg=green>Complete!');
    }
}
