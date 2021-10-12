<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Magento\Deployer\Util\Filesystem;
use Psr\Log\LoggerInterface;

class Composer implements \ArrayAccess
{
    private array $composer;
    private string $path;
    private LoggerInterface $logger;
    private Filesystem $filesystem;

    /**
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param string $path
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem, string $path)
    {
        $this->composer = json_decode($filesystem->readFile($path . '/composer.json'), true);
        $this->path = $path;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
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

    public function addRequire(string $name, string $version): void
    {
        $this->logger->notice('Requiring ' . $name . ':' . $version);
        $this->composer['require'][$name] = $version;
    }

    public function removeRequire(string $name): void
    {
        unset($this->composer['require'][$name]);
    }
    
    public function addRepo(string $name, bool $prepend = false, bool $canonical = null, array $exclude = []): void
    {
        $repos = [
            'connect' => [
                'type' => 'composer',
                'url' => 'https://connect20-qa01.magedevteam.com'
            ],
            'vcs' => [
                'type' => 'git',
                'url' => 'git@github.com:magento-commerce/magento-vcs-installer.git',
                'only' => ['magento/magento-vcs-installer']
            ],
            'ece-tools' => [
                'type' => 'git',
                'url' => 'git@github.com:magento-commerce/ece-tools.git',
                'only' => ['magento/ece-tools']
            ],
            'magento-cloud-components' => [
                'type' => 'git',
                'url' => 'git@github.com:magento-commerce/magento-cloud-components.git',
                'only' => ['magento/magento-cloud-components']
            ],
            'magento-cloud-patches' => [
                'type' => 'git',
                'url' => 'git@github.com:magento-commerce/magento-cloud-patches.git',
                'only' => ['magento/magento-cloud-patches']
            ],
            'magento-cloud-docker' => [
                'type' => 'git',
                'url' => 'git@github.com:magento/magento-cloud-docker.git',
                'only' => ['magento/magento-cloud-docker']
            ],
            'quality-patches' => [
                'type' => 'git',
                'url' => 'git@github.com:magento/quality-patches.git',
                'only' => ['magento/quality-patches']
            ]
        ];

        $repo = $repos[$name];
        if (isset($canonical)) {
            $repo['canonical'] = $canonical;
        }
        if ($prepend) {
            $this->composer['repositories'] = array_merge([$name => $repo], $this->composer['repositories']);
        } else {
            $this->composer['repositories'][$name] = $repo;
        }
        if ($exclude) {
            $this->composer['repositories'][$name]['exclude'] = $exclude;
        }
        $this->logger->notice('Adding repo ' . $repo['url']);
    }

    public function stripScripts(): void
    {
        $this->logger->notice('Stripping "scripts" from composer');
        unset($this->composer['scripts']);
    }

    public function addInitialGitSupport(string $eceVersion): void
    {
        $this->composer['repositories'] = [];
        $this->addRepo('ece-tools');
        $this->addRepo('magento-cloud-components');
        $this->addRepo('magento-cloud-patches');
        $this->addRepo('magento-cloud-docker');
        $this->addRepo('quality-patches');
        $this->disableTimeout();
        $this->logger->notice('Removing composer autoload');
        unset($this->composer['autoload']);
        $this->logger->notice('Removing all composer "require"');
        $this->composer['require'] = [];
        $this->addRequire('magento/ece-tools', $eceVersion);
        $this->logger->notice('Replacing composer "replace" with magento/magento-cloud-components:*');
        $this->composer['replace'] = [
            'magento/magento-cloud-components' => '*'
        ];
    }

    public function addVcsPlugin(string $version, string $eceVersion): void
    {
        $this->composer['repositories']['repo']['exclude'] = ['magento/ece-tools', 'magento/magento-vcs-installer', 'magento/composer'];
        $this->addRepo('vcs');
        $this->addRepo('ece-tools');
        $this->composer['minimum-stability'] = 'dev';
        $this->addRequire('magento/magento-vcs-installer', $version);
        $this->addRequire('magento/ece-tools', $eceVersion);
        $this->disableTimeout();
    }

    public function disableTimeout(): void
    {
        $this->logger->notice('Disabling composer timeout');
        $this->composer['config']['process-timeout'] = 0;
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
        $this->filesystem->writeFile($this->path . '/' . $filename, json_encode($this->composer, JSON_PRETTY_PRINT));
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
