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
use Magento\Deployer\Model\HotfixApplier;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\PrepareStrategy\StrategyInterface;
use Magento\Deployer\Model\TraditionalStrategy;
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

    /**
     * @param Factory<PrepareConfig> $prepareConfigFactory
     * @param PathResolver $pathResolver
     * @param ComposerResolver $composerResolver
     * @param HotfixApplier $hotfixApplier
     */
    public function __construct(
        Factory             $prepareConfigFactory,
        StrategyInterface   $prepare,
        PathResolver        $pathResolver,
        ComposerResolver    $composerResolver,
        HotfixApplier       $hotfixApplier
    ) {
        parent::__construct();
        $this->prepareConfigFactory = $prepareConfigFactory;
        $this->prepare = $prepare;
        $this->pathResolver = $pathResolver;
        $this->composerResolver = $composerResolver;
        $this->hotfixApplier = $hotfixApplier;
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
            'Specify the deployment strategy to use. Default is "traditional". You can also use "VCS" to use the new VCS installer.',
            'traditional'
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
            'magento-commerce/dev-release'
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
        $this->hotfixApplier->validateAllExist($input->getOption('hotfix'));

        $config = $this->prepareConfigFactory->create();
        $config->setPath($path);
        $config->setExclude($input->getOption('exclude'));
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

        $this->prepare->execute($config);

        return 0;
    }
}
