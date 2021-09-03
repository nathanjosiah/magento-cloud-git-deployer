<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Magento\Deployer\Model\Exception\EnvYamlNotFoundException;
use Magento\Deployer\Util\Filesystem;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class EnvYaml implements \ArrayAccess
{
    private array $env;
    private string $path;
    private Filesystem $filesystem;
    private Dumper $yamlDumper;

    /**
     * @param Filesystem $filesystem
     * @param Dumper $yamlDumper
     * @param string $path
     * @throws EnvYamlNotFoundException
     */
    public function __construct(Filesystem $filesystem, Parser $yamlParser, Dumper $yamlDumper, string $path)
    {
        if (!$filesystem->fileExists($path . '/.magento.env.yaml')) {
            throw new EnvYamlNotFoundException();
        }
        $this->env = $yamlParser->parse($path . '/.magento.env.yaml');
        $this->path = $path;
        $this->filesystem = $filesystem;
        $this->yamlDumper = $yamlDumper;
    }

    public function write(): void
    {
        $this->filesystem->writeFile($this->path . '/.magento.env.yaml',
            $this->yamlDumper->dump($this->env, 50, 0, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->env);
    }

    public function offsetGet($offset)
    {
        return $this->env[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->env[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->env[$offset]);
    }
}
