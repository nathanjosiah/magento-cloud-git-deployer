<?php
/**
 * @deprecated Please use `bin/deploy.php environment:prepare` CLI interface instead
 */

use Magento\Deployer\Model\ObjectManager;
use Symfony\Component\Console\Logger\ConsoleLogger;

require_once __DIR__ . '/bootstrap.php';

$objectManager = ObjectManager::getInstance();
$logger = $objectManager->get(ConsoleLogger::class);
$logger->error('<fg=red>standalone script "nuke-cloud.php" support has been removed. Please use the <fg=cyan>cloud-deployer environment:prepare<fg=red> binary or even the <fg=cyan>bin/deploy.php environment:prepare<fg=red> script CLI instead.');
exit(123);
