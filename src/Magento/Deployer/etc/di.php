<?php
declare(strict_types=1);

use Magento\Deployer\Model\ObjectManager\ObjectArrayResolver;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

return [
    'preferences' => [
        \Psr\Log\LoggerInterface::class => \Symfony\Component\Console\Logger\ConsoleLogger::class,
        \Symfony\Component\Console\Input\InputInterface::class => \Symfony\Component\Console\Input\ArgvInput::class,
        \Symfony\Component\Console\Output\OutputInterface::class => \Symfony\Component\Console\Output\ConsoleOutput::class,
        \Magento\Deployer\Model\PrepareStrategy\StrategyInterface::class => \Magento\Deployer\Model\PrepareStrategy\CompositeStrategy::class
    ],
    'types' => [
        \Symfony\Component\Console\Logger\ConsoleLogger::class => [
            'parameters' => [
                'verbosityLevelMap' => [
                    // Remap the verbosity levels for these
                    LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
                    LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
                    LogLevel::DEBUG => OutputInterface::VERBOSITY_VERY_VERBOSE,
                ]
            ]
        ],
        \Symfony\Component\Yaml\Dumper::class => [
            'parameters' => [
                'indentation' => 2
            ]
        ],
        \Magento\Deployer\Model\HotfixApplier::class => [
            'parameters' => [
                'hotfixes' => new ObjectArrayResolver(include __DIR__ . '/hotfixes.php')
            ]
        ],
        \Magento\Deployer\Model\ApplicationFactory::class => [
            'parameters' => [
                'commands' => new ObjectArrayResolver(include __DIR__ . '/commands.php')
            ]
        ],
        \Magento\Deployer\Model\PrepareStrategy\CompositeStrategy::class => [
            'parameters' => [
                'strategies' => new ObjectArrayResolver([
                    'traditional' => \Magento\Deployer\Model\PrepareStrategy\TraditionalStrategy::class,
                    'vcs' => \Magento\Deployer\Model\PrepareStrategy\VcsStrategy::class,
                ])
            ]
        ],
        \Magento\Deployer\Model\FilePurger::class => [
            'parameters' => [
                'defaultExclusions' => ['cloud_tmp', '.git', 'auth.json', 'app', '.magento.env.yaml', '.', '..']
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
