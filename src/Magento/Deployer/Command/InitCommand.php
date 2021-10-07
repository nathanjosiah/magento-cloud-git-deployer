<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\AppYaml;
use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Composer;
use Magento\Deployer\Model\Config\ComposerResolver;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\FilePurger;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    protected static $defaultName = 'project:init';
    protected static $defaultDescription = 'Initialize the project with required files and configuration for a type of deployment.';
    private LoggerInterface $logger;
    private PathResolver $pathResolver;
    private CloudCloner $cloudCloner;
    private FilePurger $filePurger;
    private Factory $composerFactory;
    private Factory $appYamlFactory;
    private ComposerResolver $composerResolver;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param PathResolver $pathResolver
     * @param CloudCloner $cloudCloner
     * @param FilePurger $filePurger
     * @param Factory<Composer> $composerFactory
     * @param Factory<AppYaml> $appYamlFactory
     * @param ComposerResolver $composerResolver
     * @param Filesystem $filesystem
     */
    public function __construct(
        LoggerInterface $logger,
        PathResolver $pathResolver,
        CloudCloner $cloudCloner,
        FilePurger $filePurger,
        Factory $composerFactory,
        Factory $appYamlFactory,
        ComposerResolver $composerResolver,
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->pathResolver = $pathResolver;
        $this->cloudCloner = $cloudCloner;
        $this->filePurger = $filePurger;
        $this->composerFactory = $composerFactory;
        $this->appYamlFactory = $appYamlFactory;
        $this->composerResolver = $composerResolver;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this->addArgument(
            'type',
            InputArgument::OPTIONAL,
            'Either "vcs", "traditional", "composer"',
            PrepareConfig::STRATEGY_VCS
        );
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'The directory to operate in. Default is the current directory.'
        );
        $this->addOption(
            'cloud-branch',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the branch of magento-cloud to clone as a base.',
            'master'
        );
        $this->addOption(
            'exclude',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Exclude files from the reset when using the composer type',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->pathResolver->resolveNewProjectWithUserInput($input->getArgument('directory'));

        $type = $input->getArgument('type');
        if (!in_array($type, [PrepareConfig::STRATEGY_TRADITIONAL, PrepareConfig::STRATEGY_VCS, PrepareConfig::STRATEGY_COMPOSER])) {
            $this->logger->error('Invalid type "' . $type . '". Options are "traditional", "vcs", or "composer"');
            return 1;
        }

        chdir($path);
        $helper = $this->getHelper('question');

        if ($this->filesystem->fileExists($path) && count($this->filesystem->getFilesInDirectory($path)) > 2) {
            $question = new ConfirmationQuestion('<fg=red>Directory <fg=yellow>' . $path . '<fg=red> is not empty, are you sure you want to proceed? <fg=blue>(y/n) <fg=green>[default yes]<fg=default>: ');
            if (!$helper->ask($input, $output, $question)) {
                $this->logger->error('Directory is not empty');
                return 1;
            }
        }

        if (!$this->filesystem->fileExists($path . '/auth.json')) {
            $this->logger->info('<fg=blue>Writing auth.json. <fg=red>Please edit "auth.json" and fill in the correct values!');
            $this->filesystem->writeFile($path . '/auth.json', $this->getTemplate('auth.json'));
        }

        if ($type === PrepareConfig::STRATEGY_TRADITIONAL) {
            $template = $this->getTemplate('env-traditional.yaml');
            $this->logger->info('<fg=blue>Checking for existing auth.json github token');
            $auth = json_decode($this->filesystem->readFile($path . '/auth.json'), true);
            $this->logger->info('<fg=blue>Using github token from auth.json');
            if (strpos($auth['http-basic']['github.com']['password'], '<') === false) {
                $token = $auth['http-basic']['github.com']['password'];
            } else {
                $question = new Question('<fg=blue>Github Token:<fg=default> ');
                $token = $helper->ask($input, $output, $question);
                if (!preg_match('/^[A-Z0-9_]+$/i', $token)) {
                    $this->logger->error('Invalid token');
                    exit;
                }
            }

            $this->logger->info('<fg=blue>Writing .magento.env.yaml');
            $this->filesystem->writeFile($path . '/.magento.env.yaml', str_replace('{TOKEN}', $token, $template));
        } elseif ($type === PrepareConfig::STRATEGY_VCS) {
            $question = new Question('<fg=blue>Which magento version do you plan to deploy? Only specify x.x.x and do not include any -p1 identifiers:<fg=default> ');
            $version = $helper->ask($input, $output, $question);
            $this->logger->info('<fg=blue>Writing .magento.env.yaml');
            $template = $this->getTemplate('env-vcs.yaml');
            $this->filesystem->writeFile($path . '/.magento.env.yaml', str_replace('{VERSION}', $version, $template));
        } elseif ($type === PrepareConfig::STRATEGY_COMPOSER) {
            @$this->filesystem->deleteFile($path . '/.magento.env.yaml');
            $this->filePurger->purgePathWithExceptions($path, $input->getOption('exclude'));
        }

        $this->cloudCloner->cloneToCwd($input->getOption('cloud-branch'), true);

        if ($type === PrepareConfig::STRATEGY_COMPOSER) {
            $this->logger->info('<fg=blue>Updating Composer');
            $composer = $this->composerFactory->create(['path' => $path]);
            $composer->addRepo('connect', false, false, ['magento/product-enterprise-edition', 'magento/magento-cloud-metapackage']);

            $question = new ChoiceQuestion('Which version? ', ['2.4.2-p1', '2.4.3-p1']);
            $version = $helper->ask($input, $output, $question);
            if ($version === '2.3.7-p1') {
                $composer->addRequire("magento/ece-tools", "^2002.1.0");
                $composer->addRequire("magento/module-paypal-on-boarding", "~100.4.0");
                $composer->addRequire("fastly/magento2", "^1.2.34");
                $composer->removeRequire('magento/magento-cloud-metapackage');
                $composer->addRequire("magento/product-enterprise-edition", "2.3.7-p1");
            } elseif ($version === '2.4.2-p1') {
                $composer->addRequire("magento/ece-tools", "^2002.1.0");
                $composer->addRequire("magento/module-paypal-on-boarding", "~100.4.0");
                $composer->addRequire("fastly/magento2", "^1.2.34");
                $composer->removeRequire('magento/magento-cloud-metapackage');
                $composer->addRequire("magento/product-enterprise-edition", "2.4.2-p1");
            } elseif ($version === '2.4.3-p1') {
                $composer->addRequire("magento/ece-tools", "^2002.1.0");
                $composer->addRequire("magento/module-paypal-on-boarding", "~100.4.0");
                $composer->addRequire("fastly/magento2", "^1.2.34");
                $composer->removeRequire('magento/magento-cloud-metapackage');
                $composer->addRequire("magento/product-enterprise-edition", "2.4.3-p1");
            }

            $composer->write();

            $this->logger->info('<fg=blue>Updating .magento.app.yaml');
            $appYaml = $this->appYamlFactory->create(['path' => $path]);
            $appYaml->addRelationship('rabbitmq', 'rabbitmq:rabbitmq');
            if ((int)$this->composerResolver->resolve() === 2) {
                $appYaml->addComposer2Support();
            }
            $appYaml->write();

            $this->logger->info('<fg=blue>Updating .magento/services.yaml');
            // Misuse the intended purpose of AppYaml for services.
            $serviceYaml = $this->appYamlFactory->create([
                'path' => $path . '/.magento/', 'filename' => 'services.yaml'
            ]);
            $serviceYaml['rabbitmq'] = [
                'type' => 'rabbitmq:3.8',
                'disk' => 2048
            ];
            $serviceYaml->write();
            $this->logger->info('<fg=green>Ensure that composer.json contains the correct requires then run "composer update".');
            $this->logger->info('<fg=yellow>When you run "composer update" you will need to be on the Magento VPN for connect20 packages.');
        }

        return 0;
    }

    private function getTemplate(string $name): string
    {
        return $this->filesystem->readFile(BP . '/etc/templates/' . $name);
    }
}
