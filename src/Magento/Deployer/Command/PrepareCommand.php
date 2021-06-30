<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends Command
{
    protected static $defaultName = 'environment:prepare';

    protected function configure()
    {
        $this->addOption(
            'exclude',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Exclude additional paths from being deleted'
        );
        $this->addOption(
            'laminas-fix',
            null,
            InputOption::VALUE_NONE,
            'Exclude additional paths from being deleted'
        );
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'The directory to operate in. Default is the current directory.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $path = $input->getArgument('directory');
        if (!empty($path)) {
            $output->writeln('<fg=blue>Running in <fg=yellow>' . $path);
            $path = realpath($path);
            if ($path) {
                $output->writeln('<fg=blue>Resolved to <fg=yellow>' . $path);
            } else {
                $output->writeln('<fg=red>Could not resolve given path!');
                return 100;
            }
        } else {
            $path = getcwd();
            $output->writeln('<fg=blue>No path provided. Using working directory <fg=yellow>' . $path);
        }

        $prepare = new \Magento\Deployer\Model\Prepare();
        $prepare->execute($path, $input->getOption('exclude'), $input->getOption('laminas-fix'));

        return 0;
    }
}
