<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class Composer implements \ArrayAccess
{
    private array $composer;
    private string $path;
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     * @param string $path
     */
    public function __construct(LoggerInterface $logger, string $path)
    {
        $this->composer = json_decode(file_get_contents($path . '/composer.json'), true);
        $this->path = $path;
        $this->logger = $logger;
    }

    public function removeMagentoRequires(): void
    {
        $requires = $this->composer['require'];
        foreach ($requires as $require => $version) {
            if (strpos($require, 'magento/') === 0) {
                unset($this->composer['require'][$require]);
            }
        }
    }

    public function stripScripts(): void
    {
        unset($this->composer['scripts']);
    }

    public function addInitialGitSupport(string $eceVersion): void
    {
        $deps = ['magento/ece-tools' => $eceVersion];
        $this->composer['repositories'] = [
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
        unset($this->composer['autoload']);
        $this->composer['require'] = $deps;
        $this->composer['replace'] = [
            'magento/magento-cloud-components' => '*'
        ];
    }

    public function addComposer2Support(): void
    {
        $appYaml = Yaml::parseFile($this->path . '/.magento.app.yaml');
        $appYaml['build']['flavor'] = 'none';
        $appYaml['dependencies']['php']['composer/composer'] = '^2.0';
        $appYaml['hooks']['build'] = 'set -e' . "\n"
            . 'composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader' . "\n"
            . $appYaml['hooks']['build'];
        file_put_contents($this->path . '/.magento.app.yaml', Yaml::dump($appYaml));
    }

    public function addVcsComposerRepo(string $version, string $eceVersion): void
    {
        $this->composer['repositories']['vcs'] = [
            'type' => 'git',
            'url' => 'git@github.com:magento-commerce/magento-vcs-installer.git'
        ];
        $this->composer['repositories']['ece-tools'] = [
            'type' => 'git',
            'url' => 'git@github.com:magento/ece-tools.git'
        ];
        $this->composer['minimum-stability'] = 'dev';
        $this->composer['config']['process-timeout'] = 0;
        $this->composer['require']['magento/magento-vcs-installer'] = $version;
        $this->composer['require']['magento/ece-tools'] = $eceVersion;
    }

    public function addVcsRepo(string $repo, string $ref): void
    {
        if (!isset($this->composer['extra']['deploy']['repo'])) {
            $this->composer['extra']['deploy']['repo'] = [];
        }
        $this->composer['extra']['deploy']['repo'][$repo] = [
            'url' => 'git@github.com:' . $repo . '.git',
            'ref' => $ref
        ];
    }

    public function write(string $filename = 'composer.json'): void
    {
        file_put_contents($this->path . '/' . $filename, json_encode($this->composer, JSON_PRETTY_PRINT));
    }


    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->composer);
    }

    public function offsetGet($offset)
    {
        return $this->composer[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->composer[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->composer[$offset]);
    }
}
