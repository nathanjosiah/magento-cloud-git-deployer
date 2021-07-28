<?php
/**
 * @deprecated Please use `bin/deploy.php environment:prepare` CLI interface instead
 */

use Magento\Deployer\Model\Config\ComposerResolver;
use Magento\Deployer\Model\Config\PathResolver;
use Magento\Deployer\Model\Config\PrepareConfig;
use Magento\Deployer\Model\ObjectManager;
use Magento\Deployer\Model\Prepare;
use Symfony\Component\Console\Logger\ConsoleLogger;

require_once __DIR__ . '/bootstrap.php';

$objectManager = ObjectManager::getInstance();
/** @var PathResolver $pathResolver */
$pathResolver = $objectManager->get(PathResolver::class);
$composerResolver = $objectManager->get(ComposerResolver::class);
$prepare = $objectManager->get(Prepare::class);
/** @var PrepareConfig $config */
$config = $objectManager->create(PrepareConfig::class);
$logger = $objectManager->get(ConsoleLogger::class);

$logger->warning('<fg=red>nuke-cloud.php is deprecated. Please use `bin/deploy.php environment:prepare` CLI command instead.');
$opts = getopt('', ['exclude:','laminas-fix','ece-version:', 'cloud-branch:'], $firstPositionalArgIndex);
$config->setPath($pathResolver->resolveExistingProjectWithUserInput($argv[$firstPositionalArgIndex] ?? null));
$config->setExclude((array)@$opts['exclude']);
$config->setIsLaminasFix(isset($opts['laminas-fix']));
$config->setEceVersion($opts['ece-version'] ?? 'dev-develop');
$config->setCloudBranch($opts['cloud-branch'] ?? 'master');
$config->setIsComposer2((int)$composerResolver->resolve() === 2);
$prepare->execute($config);
