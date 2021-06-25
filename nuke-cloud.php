<?php

use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/vendor/autoload.php';
$opts = getopt('', ['exclude:'], $firstPositionalArgIndex);
$path = getcwd();
$excludedDirs = ['cloud_tmp', '.git', 'auth.json', 'app', '.magento.env.yaml', '.', '..'];
$colorRed = "\e[0;31m";
$colorBlue = "\e[0;34m";
$colorGreen = "\e[0;32m";
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

chdir($path);

if (!is_writable($path)) {
    echo "$colorRed Directory is not writable!$colorClear" . \PHP_EOL;
    exit;
}

echo "$colorBlue Getting composer version" . \PHP_EOL;
if (preg_match('/version (?P<version>.*?) /', `composer --version 2>&1`, $matches)) {
    if (empty($matches['version'])) {
        echo "$colorRed Could not find composer!$colorClear" . \PHP_EOL;
        exit;
    } else {
        echo "$colorBlue Found composer version $colorYellow ${matches['version']}$colorClear" . \PHP_EOL;
    }
}
$composer2 = (int)$matches['version'] === 2;

//$deps = ['magento/ece-tools' => '=2002.1.2'];
$deps = ['magento/ece-tools' => 'dev-develop'];

$vendors = @scandir('app/code');

//if (is_dir('app/code')) {
//    $files = glob('app/code/*/*/composer.json');
//    foreach ($files as $file) {
//        $composer = json_decode(file_get_contents($file), true);
//        if (!empty($composer['require'])) {
//            foreach($composer['require'] as $dep => $version) {
//                if (strpos($dep, 'magento/') !== 0 && $dep !== 'php') {
//                    $deps[$dep] = $version;
//                }
//            }
//        }
//    }
//}

if (!empty($opts['exclude'])) {
    if (!is_array($opts['exclude'])) {
        $opts['exclude'] = [$opts['exclude']];
    }
    $error = false;
    foreach ($opts['exclude'] as $excludePath) {
        $excludeRealPath = realpath($excludePath);
        if (!$excludeRealPath || !file_exists($excludeRealPath)) {
            echo "$colorRed Excluded path $colorYellow $excludePath $colorBlue does not exist $colorClear." . \PHP_EOL;
            $error = true;
        } else {
            if (strpos($excludeRealPath, $path) !== 0) {
                echo "$colorRed Exclude path isn't in project directory. $colorClear" . \PHP_EOL;
                $error = true;
            } else {
                $excludedDirs[] = substr($excludeRealPath, strlen($path) + 1);
            }
        }
    }
    if ($error) {
        return;
    }
}

if (file_exists($path . '/cloud_tmp')) {
    echo "$colorBlue Found existing cloud_tmp folder. Deleting.$colorClear" . \PHP_EOL;
    if(system('rm -rf ' . escapeshellarg($path . '/cloud_tmp'))) {
        echo "$colorRed Could not delete tmp folder!$colorClear" . \PHP_EOL;
        exit;
    }
}
echo "$colorBlue Cloning cloud repo.$colorClear" . \PHP_EOL;
$result = `git clone --depth 1 --branch master git@github.com:magento/magento-cloud.git cloud_tmp 2>&1`;

if (strpos($result, 'fatal:') !== false) {
    echo "$colorRed Could not clone cloud repo!$colorClear" . \PHP_EOL;
    exit; 
}

register_shutdown_function(function() use ($path, $colorRed, $colorClear) {
    if(system('rm -rf ' . escapeshellarg($path . '/cloud_tmp'))) {
        echo "$colorRed Could not delete tmp folder!$colorClear" . \PHP_EOL;
        exit;
    }
});

$keep = implode('" -not -name "' , $excludedDirs);
echo "$colorBlue Purging folder of all but minimum files. $colorClear" . \PHP_EOL;
`find . -maxdepth 1 -not -name "$keep" -exec rm -rf {} +`;
echo "$colorBlue Transferring mainline files. $colorClear" . \PHP_EOL;
`rsync -av cloud_tmp/ . --exclude=.git --exclude=.github`;
echo "$colorBlue Adjusting composer.json. $colorClear" . \PHP_EOL;
$composer = json_decode(file_get_contents('composer.json'), true);
$composer['repositories'] = [
    'ece-tools' => [
        'type' => 'git',
        'url' => 'git@github.com:magento/ece-tools.git'
    ],
    'magento-cloud-components' => [
        'type' => 'git',
        'url' => 'git@github.com:magento/magento-cloud-components.git'
    ],
    'magento-cloud-patches' => [
       'type' => 'git',
       'url' => 'git@github.com:magento/magento-cloud-patches.git'
    ],
    'magento-cloud-docker' => [
       'type' => 'git',
       'url' => 'git@github.com:magento/magento-cloud-docker.git'
    ],
    'quality-patches' => [
       'type' => 'git',
       'url' => 'git@github.com:magento/quality-patches.git'
    ]
];
unset($composer['autoload']);
$composer['require'] = $deps;
$composer['replace'] = [
    'magento/magento-cloud-components' => '*'
];

if ($composer2) {
    echo "$colorBlue Configuring for composer 2. $colorClear" . \PHP_EOL;
    $appYaml = Yaml::parseFile($path . '/.magento.app.yaml');
    $appYaml['build']['flavor'] = 'none';
    $appYaml['dependencies']['php']['composer/composer'] = '^2.0';
    $appYaml['hooks']['build'] = 'set -e' . "\n"
    . 'composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader' . "\n"
    . $appYaml['hooks']['build'];
    file_put_contents($path . '/.magento.app.yaml', Yaml::dump($appYaml));
}
else {
    echo "$colorBlue Using composer 1. $colorClear" . \PHP_EOL;
}

file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));

echo "$colorBlue Running composer update $colorClear." . \PHP_EOL;
`composer update --ansi --no-interaction`;
$composerPretty = json_encode($composer, JSON_PRETTY_PRINT);
$composerCopyPath = realpath('.') . '/original-composer.json';
echo "$colorBlue Saving copy of composer.json before dev:git:update-composer to $colorYellow $composerCopyPath $colorClear" . \PHP_EOL;
file_put_contents($composerCopyPath, $composerPretty);
echo "$colorBlue Running $colorYellow php vendor/bin/ece-tools dev:git:update-composer $colorClear" . \PHP_EOL;
$bin = PHP_BINARY;
`{$bin} vendor/bin/ece-tools dev:git:update-composer`;
echo "$colorBlue Fixing composer autoloader settings $colorClear." . \PHP_EOL;
$mainlineComposer = json_decode(file_get_contents('cloud_tmp/composer.json'), true);
$localComposer = json_decode(file_get_contents('composer.json'), true);
$localComposer['autoload'] = $mainlineComposer['autoload'];
$localComposerPretty = json_encode($localComposer, JSON_PRETTY_PRINT);
echo "$colorBlue composer.json after dev:git:update-composer saved to composer.json$colorClear" . \PHP_EOL;
file_put_contents('composer.json', $localComposerPretty);
echo "$colorGreen Complete! $colorClear" . \PHP_EOL;
