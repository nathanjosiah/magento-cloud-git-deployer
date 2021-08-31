<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Magento\Deployer\Model\Exception\EnvYamlNotFoundException;
use Symfony\Component\Yaml\Yaml;

class EnvYaml implements \ArrayAccess
{
    private array $env;
    private string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        if (!file_exists($path . '/.magento.env.yaml')) {
            throw new EnvYamlNotFoundException();
        }
        $this->env = Yaml::parseFile($path . '/.magento.env.yaml');
        $this->path = $path;
    }

    public function write(): void
    {
        file_put_contents($this->path . '/.magento.env.yaml', Yaml::dump($this->env, 50, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
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
