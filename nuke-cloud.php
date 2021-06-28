<?php
/**
 * @deprecated Please use `bin/deploy.php environment:prepare` CLI interface instead
 */
require_once __DIR__ . '/bootstrap.php';

$opts = getopt('', ['exclude:','laminas-fix'], $firstPositionalArgIndex);
$path = getcwd();
$excludedDirs = ['cloud_tmp', '.git', 'auth.json', 'app', '.magento.env.yaml', '.', '..'];
$colorRed = "\e[0;31m";
$colorBlue = "\e[0;34m";
$colorYellow = "\e[1;33m";
$colorClear = "\e[0m";

if (!empty($argv[$firstPositionalArgIndex])) {
    echo "$colorBlue Running in $colorYellow ${argv[$firstPositionalArgIndex]}$colorClear" . \PHP_EOL;
    $path = realpath($argv[$firstPositionalArgIndex]);
    if ($path) {
        echo "$colorBlue Resolved to $colorYellow $path $colorClear" . \PHP_EOL;
    } else {
        echo "$colorRed Could not resolve given path!$colorClear" . \PHP_EOL;
        exit;
    }
} else {
    echo "$colorBlue No path provided. Using working directory $colorYellow $path $colorClear" . \PHP_EOL;
}

$prepare = new \Magento\Deployer\Model\Prepare();
$prepare->execute($path, (array)@$opts['exclude'], isset($opts['laminas-fix']));
