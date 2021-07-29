<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Magento\Deployer\Model\ObjectManager;
use Symfony\Component\Console\Application;

class ApplicationFactory
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var array
     */
    private $commands;

    /**
     * @param ObjectManager $objectManager
     * @param array $commands
     */
    public function __construct(
        ObjectManager $objectManager,
        array $commands
    ) {
        $this->objectManager = $objectManager;
        $this->commands = $commands;
    }

    public function create(): Application
    {
        /** @var Application $application */
        $application = $this->objectManager->get(Application::class);
        foreach ($this->commands as $command) {
            $application->add($command);
        }

        return $application;
    }
}
