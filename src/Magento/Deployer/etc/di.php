<?php
declare(strict_types=1);

use Magento\Deployer\Model\ObjectManager\FactoryProxy;
use Magento\Deployer\Model\ObjectManager\ObjectArrayResolver;

return [
    'preferences' => [
        \Psr\Log\LoggerInterface::class => \Symfony\Component\Console\Logger\ConsoleLogger::class,
        \Symfony\Component\Console\Input\InputInterface::class => \Symfony\Component\Console\Input\ArgvInput::class,
        \Symfony\Component\Console\Output\OutputInterface::class => \Symfony\Component\Console\Output\ConsoleOutput::class,
    ],
    'types' => [
        \Magento\Deployer\Model\HotfixApplier::class => [
            'parameters' => [
                'hotfixes' => new ObjectArrayResolver([
                    'monolog-and-es' => \Magento\Deployer\Model\Hotfix\MonologAndEs::class
                ])
            ]
        ],
        \Magento\Deployer\Model\ApplicationFactory::class => [
            'parameters' => [
                'commands' => new ObjectArrayResolver([
                    'self-update' => \Magento\Deployer\Command\SelfUpdate::class,
                    'init' => \Magento\Deployer\Command\InitCommand::class,
                    'prepare' => \Magento\Deployer\Command\PrepareCommand::class,
                    'apply-hotfix' => \Magento\Deployer\Command\ApplyHotfix::class,
                    'list-hotfix' => \Magento\Deployer\Command\ListHotfixes::class
                ])
            ]
        ],
        \Magento\Deployer\Model\FilePurger::class => [
            'parameters' => [
                'defaultExclusions' => ['cloud_tmp', '.git', 'auth.json', 'app', '.magento.env.yaml', '.', '..']
            ]
        ],
        \Symfony\Component\Console\Output\Output::class => [
            'parameters' => [
                'verbosity' => 9999999,
            ],
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
