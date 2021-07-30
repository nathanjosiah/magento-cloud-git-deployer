<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Command;

use Magento\Deployer\Model\ShellExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdate extends Command
{
    protected static $defaultName = 'self-update';
    protected static $defaultDescription = 'Updates this tool to the latest version';

    private ShellExecutor $shellExecutor;


    /**
     * @param ShellExecutor $shellExecutor
     */
    public function __construct(
        ShellExecutor $shellExecutor
    ) {
        parent::__construct();
        $this->shellExecutor = $shellExecutor;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->shellExecutor->execute('composer global update nathanjosiah/magento-cloud-git-deployer');

        return 0;
    }
}
