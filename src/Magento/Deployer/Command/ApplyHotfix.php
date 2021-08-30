<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\HotfixApplier;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ApplyHotfix extends Command
{
    protected static $defaultName = 'hotfix:apply';
    private PathResolver $pathResolver;
    private HotfixApplier $hotfixApplier;

    /**
     * @param PathResolver $pathResolver
     * @param HotfixApplier $hotfixApplier
     */
    public function __construct(
        PathResolver $pathResolver,
        HotfixApplier $hotfixApplier
    ) {
        parent::__construct();
        $this->pathResolver = $pathResolver;
        $this->hotfixApplier = $hotfixApplier;
    }

    protected function configure()
    {
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            'The directory to operate in. Default is the current directory.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->pathResolver->resolveExistingProjectWithUserInput($input->getArgument('directory'));
        chdir($path);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Please select a patch: ', $this->hotfixApplier->getAvailablePatches());
        $patch = $helper->ask($input, $output, $question);

        foreach ($this->hotfixApplier->getConfirmationQuestions($patch) as $question) {
            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $this->hotfixApplier->apply($patch);

        return 0;
    }
}
