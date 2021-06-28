<?php
use Symfony\Component\Console\Application;
use Magento\Deployer\Command\PrepareCommand;

require_once __DIR__ . '/../bootstrap.php';

$application = new Application();
$application->add(new PrepareCommand());
$application->run();
