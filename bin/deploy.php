<?php

use Magento\Deployer\Model\ApplicationFactory;

require_once __DIR__ . '/../bootstrap.php';

$objectManager = \Magento\Deployer\Model\ObjectManager::getInstance();
// Create a shared output so Logger gets the built-in verbosity
$output = $objectManager->get(\Symfony\Component\Console\Output\ConsoleOutput::class);
/** @var ApplicationFactory $applicationFactory */
$applicationFactory = $objectManager->get(ApplicationFactory::class);
$applicationFactory->create()->run(null, $output);
