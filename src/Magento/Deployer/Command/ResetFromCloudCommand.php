<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\CloudCloner;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\FilePurger;
use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ResetFromCloudCommand extends Command
{
    protected static $defaultName = 'project:reset-from-cloud';
    protected static $defaultDescription = 'Delete everything and reset with a fresh cloud copy';
    private LoggerInterface $logger;
    private PathResolver $pathResolver;
    private CloudCloner $cloudCloner;
    private FilePurger $filePurger;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param PathResolver $pathResolver
     * @param CloudCloner $cloudCloner
     * @param FilePurger $filePurger
     * @param Filesystem $filesystem
     */
    public function __construct(
        LoggerInterface $logger,
        PathResolver $pathResolver,
        CloudCloner $cloudCloner,
        FilePurger $filePurger,
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->pathResolver = $pathResolver;
        $this->cloudCloner = $cloudCloner;
        $this->filePurger = $filePurger;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
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
        $this->filePurger->purgePathWithExceptions($path, $input->getOption('exclude'));

        $this->cloudCloner->cloneToCwd($input->getOption('cloud-branch'), true);

        return 0;
    }

    private function getTemplate(string $name): string
    {
        return $this->filesystem->readFile(BP . '/etc/templates/' . $name);
    }
}
