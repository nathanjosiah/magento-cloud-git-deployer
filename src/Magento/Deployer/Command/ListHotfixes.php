<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\HotfixApplier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListHotfixes extends Command
{
    protected static $defaultName = 'hotfix:list';
    private HotfixApplier $hotfixApplier;

    /**
     * @param HotfixApplier $hotfixApplier
     */
    public function __construct(
        HotfixApplier $hotfixApplier
    ) {
        parent::__construct();
        $this->hotfixApplier = $hotfixApplier;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(implode(\PHP_EOL, $this->hotfixApplier->getAvailablePatches()));

        return 0;
    }
}
