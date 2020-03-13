<?php
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

$deps = ['magento/ece-tools' => '^2002.1.0'];

$vendors = scandir('app/code');
if (is_dir('app/code')) {
    $files = glob('app/code/*/*/composer.json');
    foreach ($files as $file) {
        $composer = json_decode(file_get_contents($file), true);
        if (!empty($composer['require'])) {
            foreach($composer['require'] as $dep => $version) {
                if (strpos($dep, 'magento/') !== 0 && $dep !== 'php') {
                    $deps[$dep] = $version;
                }
            }
        }
    }
}

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
    ]
];
unset($composer['autoload']);
$composer['require'] = $deps;
$composer['replace'] = [
    'magento/magento-cloud-components' => '*'
];
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));

echo "$colorGreen Complete! $colorClear" . \PHP_EOL;
echo "$colorBlue Please run $colorYellow composer update $colorBlue and $colorYellow php vendor/bin/ece-tools dev:git:update-composer $colorClear" . \PHP_EOL;


