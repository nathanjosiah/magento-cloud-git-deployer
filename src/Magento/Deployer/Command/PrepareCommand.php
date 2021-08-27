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
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Model\Prepare;
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
    private Prepare $prepare;
    private PathResolver $pathResolver;
    /**
     * @var ComposerResolver
     */
    private ComposerResolver $composerResolver;

    /**
     * @param Factory $prepareConfigFactory
     * @param Prepare $prepare
     * @param PathResolver $pathResolver
     * @param ComposerResolver $composerResolver
     */
    public function __construct(
        Factory $prepareConfigFactory,
        Prepare $prepare,
        PathResolver $pathResolver,
        ComposerResolver $composerResolver
    ) {
        parent::__construct();
        $this->prepareConfigFactory = $prepareConfigFactory;
        $this->prepare = $prepare;
        $this->pathResolver = $pathResolver;
        $this->composerResolver = $composerResolver;
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

        /** @var PrepareConfig $config */
        $config = $this->prepareConfigFactory->create();
        $config->setPath($path);
        $config->setExclude($input->getOption('exclude'));
        $config->setHotfixes($input->getOption('hotfix'));
        $config->setEceVersion($input->getOption('ece-version'));
        $config->setCloudBranch($input->getOption('cloud-branch'));
        $config->setIsComposer2((int)$this->composerResolver->resolve() === 2);

        $this->prepare->execute($config);

        return 0;
    }
}
