<?php

namespace Magento\Deployer\Model\PrepareStrategy;

use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Composer;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\FilePurger;
use Magento\Deployer\Model\HotfixApplier;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\ShellExecutor;
use Psr\Log\LoggerInterface;

class VcsStrategy {
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;
    private CloudCloner $cloudCloner;
    private FilePurger $filePurger;
    private HotfixApplier $hotfixApplier;
    private Factory $composerFactory;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param CloudCloner $cloudCloner
     * @param FilePurger $filePurger
     * @param HotfixApplier $hotfixApplier
     * @param Factory<Composer> $composerFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ShellExecutor $shellExecutor,
        CloudCloner $cloudCloner,
        FilePurger $filePurger,
        HotfixApplier $hotfixApplier,
        Factory $composerFactory
    ) {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->cloudCloner = $cloudCloner;
        $this->filePurger = $filePurger;
        $this->hotfixApplier = $hotfixApplier;
        $this->composerFactory = $composerFactory;
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

        $this->logger->info('<fg=blue>Purging folder of all but minimum files.');
        $this->filePurger->purgePathWithExceptions($config->getPath(), $config->getExclude());

        $this->logger->info('<fg=blue>Cloning mainline cloud project');
        $this->cloudCloner->cloneToCwd($config->getCloudBranch(), false);

        $this->logger->info('<fg=blue>Removing all magento/* requires');
        $composer = $this->composerFactory->create(['path' => $config->getPath()]);
        $composer->removeMagentoRequires();

        $this->logger->info('<fg=blue>Adding VCS+ECE repo, VCS+ECE require.');
        $composer->addVcsComposerRepo('^1.0', $config->getEceVersion());

        $this->logger->info('<fg=blue>Removing composer.json "scripts"');
        $composer->stripScripts();

        $this->logger->info('<fg=blue>Writing composer.json');
        $composer->write();

        $this->logger->info('<fg=blue>Running composer update');
        $this->shellExecutor->execute('composer update --ansi --no-interaction');

        $repos = [];
        $repos['magento2ce'] = $config->getCommunityEdition();
        $repos['magento2ee'] = $config->getEnterpriseEdition();
        $repos['magento2b2b'] = $config->getBusinessEdition();
        $repos['security-package'] = $config->getSecurityPackage();

        foreach ($repos as $key => $declared) {
            if (empty($declared)) {
                continue;
            }
            [$org, $ref] = explode('/', $declared, 2);
            $composer->addVcsRepo($org . '/' . $key, $ref);
        }

        $repos = [];
        $repos[] = $config->getFastly();
        $repos = array_merge($repos, $config->getAdditionalRepos() ?? []);
        foreach ($repos as $declared) {
            if (empty($declared)) {
                continue;
            }
            [$repo, $ref] = explode(':', $declared, 2);
            $composer->addVcsRepo($repo, $ref);
        }

        $composer->write();
        unset($composer);

        if (array_search('monolog-and-es', $config->getHotfixes()) !== false) {
            $this->hotfixApplier->apply('monolog-and-es');
        } else {
            $this->logger->info('<fg=blue>Running composer update');
            $this->shellExecutor->execute('composer update --ansi --no-interaction');
        }


        $this->cloudCloner->cleanup();
        $this->logger->info('<fg=green>Complete!');
    }
}
