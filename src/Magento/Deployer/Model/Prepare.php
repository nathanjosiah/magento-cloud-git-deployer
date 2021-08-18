<?php

namespace Magento\Deployer\Model;

use Magento\Deployer\Model\Config\PrepareConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class Prepare {
    private LoggerInterface $logger;
    private ShellExecutor $shellExecutor;
    private CloudCloner $cloudCloner;
    private FilePurger $filePurger;

    /**
     * @param LoggerInterface $logger
     * @param ShellExecutor $shellExecutor
     * @param CloudCloner $cloudCloner
     * @param FilePurger $filePurger
     */
    public function __construct(
        LoggerInterface $logger,
        ShellExecutor $shellExecutor,
        CloudCloner $cloudCloner,
        FilePurger $filePurger
    ) {
        $this->logger = $logger;
        $this->shellExecutor = $shellExecutor;
        $this->cloudCloner = $cloudCloner;
        $this->filePurger = $filePurger;
    }

    public function execute(
        PrepareConfig $config
    ): void {
        $excludedDirs = ['cloud_tmp', '.git', 'auth.json', 'app', '.magento.env.yaml', '.', '..'];

        if (!is_writable($config->getPath())) {
            $this->logger->error('Directory is not writable!');
            exit;
        }

        chdir($config->getPath());

        $this->logger->info('<fg=blue>Using ece-tools <fg=yellow>' . $config->getEceVersion());
        $deps = ['magento/ece-tools' => $config->getEceVersion()];

        if (!empty($config->getExclude())) {
            $error = false;
            foreach ($config->getExclude() as $excludePath) {
                $excludeRealPath = realpath($excludePath);
                if (!$excludeRealPath || !file_exists($excludeRealPath)) {
                    $this->logger->error("Excluded path $excludePath does not exist");
                    $error = true;
                } else {
                    if (strpos($excludeRealPath, $config->getPath()) !== 0) {
                        $this->logger->error("Exclude path $excludeRealPath isn't in project directory.");
                        $error = true;
                    } else {
                        $excludedDirs[] = substr($excludeRealPath, strlen($config->getPath()) + 1);
                    }
                }
            }
            if ($error) {
                exit;
            }
        }


        $this->filePurger->purgePathWithExceptions($config->getPath(), $excludedDirs);

        $this->cloudCloner->cloneToCwd($config->getCloudBranch(), false);

        $this->logger->info('<fg=blue>Adjusting composer.json.');
        $composer = json_decode(file_get_contents('composer.json'), true);
        $composer['repositories'] = [
            'ece-tools' => [
                'type' => 'git',
                'url' => 'git@github.com:magento/ece-tools.git'
            ],
            'magento-cloud-components' => [
                'type' => 'git',
                'url' => 'git@github.com:magento/magento-cloud-components.git'
            ],
            'magento-cloud-patches' => [
               'type' => 'git',
               'url' => 'git@github.com:magento/magento-cloud-patches.git'
            ],
            'magento-cloud-docker' => [
               'type' => 'git',
               'url' => 'git@github.com:magento/magento-cloud-docker.git'
            ],
            'quality-patches' => [
               'type' => 'git',
               'url' => 'git@github.com:magento/quality-patches.git'
            ]
        ];
        unset($composer['autoload']);
        $composer['require'] = $deps;
        $composer['replace'] = [
            'magento/magento-cloud-components' => '*'
        ];

        if ($config->isLaminasFix()) {
            $composer['require']['laminas/laminas-escaper'] = '2.7.0';
        }

        if ($config->isComposer2()) {
            $this->logger->info('<fg=blue>Configuring for composer 2.');
            $appYaml = Yaml::parseFile($config->getPath() . '/.magento.app.yaml');
            $appYaml['build']['flavor'] = 'none';
            $appYaml['dependencies']['php']['composer/composer'] = '^2.0';
            $appYaml['hooks']['build'] = 'set -e' . "\n"
            . 'composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader' . "\n"
            . $appYaml['hooks']['build'];
            file_put_contents($config->getPath() . '/.magento.app.yaml', Yaml::dump($appYaml));
        } else {
            $this->logger->info('<fg=blue>Using composer 1.');
        }

        file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        $this->logger->info('<fg=blue>Running composer update');
        $this->shellExecutor->execute('composer update --ansi --no-interaction');
        $composerPretty = json_encode($composer, JSON_PRETTY_PRINT);
        $composerCopyPath = realpath('.') . '/original-composer.json';
        $this->logger->info('<fg=blue>Saving copy of composer.json before dev:git:update-composer to <fg=yellow>' . $composerCopyPath);
        file_put_contents($composerCopyPath, $composerPretty);
        $this->logger->info('<fg=blue>Running <fg=yellow>dev:git:update-composer');
        $this->shellExecutor->execute('vendor/bin/ece-tools dev:git:update-composer');
        $this->logger->info('<fg=blue>Fixing composer autoloader settings');
        $mainlineComposer = json_decode(file_get_contents('cloud_tmp/composer.json'), true);
        $localComposer = json_decode(file_get_contents('composer.json'), true);
        $localComposer['autoload'] = $mainlineComposer['autoload'];
        $localComposerPretty = json_encode($localComposer, JSON_PRETTY_PRINT);
        $this->logger->info('<fg=blue>composer.json after dev:git:update-composer saved to composer.json');
        file_put_contents('composer.json', $localComposerPretty);
        $this->cloudCloner->cleanup();
        $this->logger->info('<fg=green>Complete!');
    }
}
