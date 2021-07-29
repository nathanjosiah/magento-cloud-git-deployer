<?php
declare(strict_types=1);

use Magento\Deployer\Model\ObjectManager\FactoryProxy;
use Magento\Deployer\Model\ObjectManager\ObjectArrayResolver;

// Should eventually be converted to xml
return [
    'preferences' => [
        \Psr\Log\LoggerInterface::class => \Symfony\Component\Console\Logger\ConsoleLogger::class,
        \Symfony\Component\Console\Input\InputInterface::class => \Symfony\Component\Console\Input\ArgvInput::class,
        \Symfony\Component\Console\Output\OutputInterface::class => \Symfony\Component\Console\Output\ConsoleOutput::class,
    ],
    'types' => [
        \Symfony\Component\Console\Output\Output::class => [
            'parameters' => [
                'verbosity' => 9999999,
            ],
        ],
        \Magento\Deployer\Model\ApplicationFactory::class => [
            'parameters' => [
                'commands' => new ObjectArrayResolver([
                    'init' => \Magento\Deployer\Command\InitCommand::class,
                    'prepare' => \Magento\Deployer\Command\PrepareCommand::class
                ])
            ]
        ],
        \Magento\Deployer\Command\PrepareCommand::class => [
            'parameters' => [
                'prepareConfigFactory' => new FactoryProxy(\Magento\Deployer\Model\Config\PrepareConfig::class)
            ]
        ],
        \Symfony\Component\Console\Application::class => [
            'parameters' => [
                'name' => 'Magento Cloud Git Deployer CLI',
                'version' => 'dev'
            ]
        ]
    ]
];
