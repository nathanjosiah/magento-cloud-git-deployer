<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\Config\PrepareConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    protected static $defaultName = 'project:init';
    protected static $defaultDescription = 'Initialize the project with required files and configuration for a type of deployment.';
    private LoggerInterface $logger;
    private PathResolver $pathResolver;
    private CloudCloner $cloudCloner;

    /**
     * @param LoggerInterface $logger
     * @param PathResolver $pathResolver
     * @param CloudCloner $cloudCloner
     */
    public function __construct(
        LoggerInterface $logger,
        PathResolver $pathResolver,
        CloudCloner $cloudCloner
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->pathResolver = $pathResolver;
        $this->cloudCloner = $cloudCloner;
    }

    protected function configure()
    {
        $this->addArgument(
            'type',
            InputArgument::REQUIRED,
            'Either "vcs" or "traditional"'
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->pathResolver->resolveNewProjectWithUserInput($input->getArgument('directory'));

        $type = $input->getArgument('type');
        if (!in_array($type, [PrepareConfig::STRATEGY_TRADITIONAL, PrepareConfig::STRATEGY_VCS])) {
            $this->logger->error('Invalid type "' . $type . '". Options are "traditional" or "vcs"');
            return 1;
        }

        chdir($path);

        if (file_exists($path) && count(scandir($path)) > 2) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<fg=red>Directory <fg=yellow>' . $path . '<fg=red> is not empty, are you sure you want to proceed? <fg=blue>(y/n) <fg=green>[default yes]<fg=default>: ');
            if (!$helper->ask($input, $output, $question)) {
                $this->logger->error('Directory is not empty');
                return 1;
            }
        }

        if (!file_exists($path . '/auth.json')) {
            $this->logger->info('<fg=blue>Writing auth.json. <fg=red>Please edit "auth.json" and fill in the correct values!');
            file_put_contents($path . '/auth.json', $this->getTemplate('auth.json'));
        }

        $helper = $this->getHelper('question');
        if ($type === PrepareConfig::STRATEGY_TRADITIONAL) {
            $template = $this->getTemplate('env-traditional.yaml');
            $question = new Question('<fg=blue>Github Token:<fg=default> ');
            $token = $helper->ask($input, $output, $question);
            if (!preg_match('/^[A-Z0-9_]+$/i', $token)) {
                $this->logger->error('Invalid token');
                exit;
            }
            $this->logger->info('<fg=blue>Writing .magento.env.yaml');
            file_put_contents($path . '/.magento.env.yaml', str_replace('{TOKEN}', $token, $template));
        } else {
            $question = new Question('<fg=blue>Which magento version do you plan to deploy? Only specify x.x.x and do not include any -p1 identifiers:<fg=default> ');
            $version = $helper->ask($input, $output, $question);
            $this->logger->info('<fg=blue>Writing .magento.env.yaml');
            $template = $this->getTemplate('env-vcs.yaml');
            file_put_contents($path . '/.magento.env.yaml', str_replace('{VERSION}', $version, $template));
        }

        $this->cloudCloner->cloneToCwd($input->getOption('cloud-branch'), true);

        return 0;
    }

    private function getTemplate(string $name): string
    {
        return file_get_contents(BP . '/etc/templates/' . $name);
    }
}
