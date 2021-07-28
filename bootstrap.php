<?php

use Laminas\Di\Config;
use Laminas\Di\Injector;
use Magento\Deployer\Model\ObjectManager;

require_once __DIR__ . '/vendor/autoload.php';

const BP = __DIR__;
$config = include __DIR__ . '/src/Magento/Deployer/etc/di.php';
$injector = new Injector(new Config($config));
$objectManager = new ObjectManager($injector);
$injector->setContainer($objectManager);
ObjectManager::setInstance($objectManager);
