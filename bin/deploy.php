<?php

use Magento\Deployer\Model\ApplicationFactory;

require_once __DIR__ . '/../bootstrap.php';

$objectManager = \Magento\Deployer\Model\ObjectManager::getInstance();
/** @var ApplicationFactory $applicationFactory */
$applicationFactory = $objectManager->get(ApplicationFactory::class);
$applicationFactory->create()->run();
