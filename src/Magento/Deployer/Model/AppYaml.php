<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class AppYaml
{
    private array $app;
    private string $path;
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     * @param string $path
     */
    public function __construct(LoggerInterface $logger, string $path)
    {
        $this->app = Yaml::parseFile($path . '/.magento.app.yaml');
        $this->path = $path;
        $this->logger = $logger;
    }

    public function addComposer2Support(): void
    {
        $this->app['build']['flavor'] = 'none';
        $this->app['dependencies']['php']['composer/composer'] = '^2.0';
        $this->addComposerInstallToBuild();
    }

    public function addComposerInstallToBuild(): void
    {
        $this->app['hooks']['build'] = 'set -e' . "\n"
            . 'composer --no-ansi --no-interaction install --no-progress --prefer-dist --optimize-autoloader' . "\n"
            . $this->app['hooks']['build'];
    }

    public function write(): void
    {
        file_put_contents($this->path . '/.magento.app.yaml', Yaml::dump($this->app, 50, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }
}
