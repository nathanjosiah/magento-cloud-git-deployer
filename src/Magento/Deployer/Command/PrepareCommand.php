<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\Config\ComposerResolver;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\EnvYaml;
use Magento\Deployer\Model\Exception\EnvYamlNotFoundException;
use Magento\Deployer\Model\HotfixApplier;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\PrepareStrategy\StrategyInterface;
use Magento\Deployer\Model\TraditionalStrategy;
use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends Command
{
    protected static $defaultName = 'environment:prepare';
    protected static $defaultDescription = 'Prepare a git-based cloud project for deployment.';

    private Factory $prepareConfigFactory;
    private StrategyInterface $prepare;
    private PathResolver $pathResolver;
    private ComposerResolver $composerResolver;
    private HotfixApplier $hotfixApplier;
    private Factory $envYamlFactory;
    private LoggerInterface $logger;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param Factory<PrepareConfig> $prepareConfigFactory
     * @param StrategyInterface $prepare
     * @param PathResolver $pathResolver
     * @param ComposerResolver $composerResolver
     * @param HotfixApplier $hotfixApplier
     * @param Factory<EnvYaml> $envYamlFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        LoggerInterface     $logger,
        Factory             $prepareConfigFactory,
        StrategyInterface   $prepare,
        PathResolver        $pathResolver,
        ComposerResolver    $composerResolver,
        HotfixApplier       $hotfixApplier,
        Factory             $envYamlFactory,
        Filesystem          $filesystem
    ) {
        parent::__construct();
        $this->prepareConfigFactory = $prepareConfigFactory;
        $this->prepare = $prepare;
        $this->pathResolver = $pathResolver;
        $this->composerResolver = $composerResolver;
        $this->hotfixApplier = $hotfixApplier;
        $this->envYamlFactory = $envYamlFactory;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this->addOption(
            'exclude',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Exclude additional paths from being deleted'
        );
        $this->addOption(
            'hotfix',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Array of hotfix names to be applied'
        );
        $this->addOption(
            'ece-version',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the package version of ece-tools to use.',
            'dev-develop'
        );
        $this->addOption(
            'strategy',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the deployment strategy to use. Default is "VCS" for the new VCS installer.',
            PrepareConfig::STRATEGY_VCS
        );
        $this->addOption(
            'ce',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format <org>/<branch>',
            'magento-commerce/dev-2.4-develop'
        );
        $this->addOption(
            'ee',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format <org>/<branch>',
            'magento-commerce/dev-2.4-develop'
        );
        $this->addOption(
            'b2b',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format <org>/<branch>',
            'magento-commerce/dev-develop'
        );
        $this->addOption(
            'sp',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format <org>/<branch>',
            'magento-commerce/dev-develop'
        );
        $this->addOption(
            'fastly',
            null,
            InputOption::VALUE_OPTIONAL,
            'Format <org>/<branch>',
            'fastly/fastly-magento2:dev-master'
        );
        $this->addOption(
            'add',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'E.g. --add magento-cia/adobe-stock-integration:dev-develop --add fastly/fastly-magento2:dev-master'
        );
        $this->addOption(
            'cloud-branch',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the branch of magento-cloud to clone as a base.',
            'master'
        );
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'The directory to operate in. Default is the current directory.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->pathResolver->resolveExistingProjectWithUserInput($input->getArgument('directory'));
        try {
            $env = $this->envYamlFactory->create(['path' => $path]);
        } catch (EnvYamlNotFoundException $e) {
            $this->logger->error('.magento.env.yaml does not exist. Please run "cloud-deployer project:init" to configure this.');
            return 1;
        }
        if (!$this->filesystem->fileExists($path . '/auth.json')) {
            $this->logger->error('auth.json does not exist. Please run "cloud-deployer project:init" to configure this or add it manually.');
            return 1;
        }

        if (!in_array($input->getOption('strategy'),
            [PrepareConfig::STRATEGY_TRADITIONAL, PrepareConfig::STRATEGY_VCS])
        ) {
            $this->logger->error('Invalid strategy "' . $input->getOption('strategy') . '". Options are "traditional" or "vcs"');
            return 1;
        }

        $this->hotfixApplier->validateAllExist($input->getOption('hotfix'));

        $config = $this->prepareConfigFactory->create();
        $exclude = $input->getOption('exclude');
        if ($input->getOption('strategy') === PrepareConfig::STRATEGY_VCS) {
            $this->logger->info('<fg=blue>Excluding vendor by default for VCS strategy');
            $exclude = array_merge($exclude, ['vendor']);
        }
        $config->setPath($path);
        $config->setExclude($exclude);
        $config->setHotfixes($input->getOption('hotfix'));
        $config->setEceVersion($input->getOption('ece-version'));
        $config->setCloudBranch($input->getOption('cloud-branch'));
        $config->setIsComposer2((int)$this->composerResolver->resolve() === 2);
        $config->setStrategy($input->getOption('strategy'));
        $config->setCommunityEdition($input->getOption('ce'));
        $config->setEnterpriseEdition($input->getOption('ee'));
        $config->setBusinessEdition($input->getOption('b2b'));
        $config->setSecurityPackage($input->getOption('sp'));
        $config->setFastly($input->getOption('fastly'));
        $config->setAdditionalRepos($input->getOption('add'));

        if ($config->getStrategy() === PrepareConfig::STRATEGY_TRADITIONAL
            && !isset($env['stage']['global']['DEPLOY_FROM_GIT_OPTIONS']['repositories'])
        ) {
            $this->logger->error('The current .magento.env.yaml is not configured for a traditional deployment. Please run "cloud-deployer project:init" or add the missing repos.');
            return 1;
        } elseif ($config->getStrategy() === PrepareConfig::STRATEGY_VCS
            && isset($env['stage']['global']['DEPLOY_FROM_GIT_OPTIONS']['repositories'])
        ) {
            $this->logger->error('The current .magento.env.yaml is configured for a VCS deployment. Please run "cloud-deployer project:init" or remove the extra configuration.');
            return 1;
        }

        $this->prepare->execute($config);

        return 0;
    }
}
