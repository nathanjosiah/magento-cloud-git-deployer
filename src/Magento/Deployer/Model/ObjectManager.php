<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Laminas\Di\InjectorInterface;
use Psr\Container\ContainerInterface;

class ObjectManager implements ContainerInterface
{
    private array $cache;

    static ObjectManager $instance;

    private InjectorInterface $injector;

    /**
     * @param InjectorInterface $injector
     */
    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
        $this->cache[static::class] = $this;
        $this->cache[self::class] = $this;
    }

    /**
     * Get the configured singleton instance
     *
     * @return ObjectManager
     */
    public static function getInstance(): ObjectManager
    {
        return static::$instance;
    }

    /**
     * Set the singleton instance
     *
     * @param ObjectManager $objectManager
     * @return void
     */
    public static function setInstance(ObjectManager $objectManager): void
    {
        static::$instance = $objectManager;
    }

    public function create($id, array $parameters = [])
    {
        return $this->injector->create($id, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return isset($this->cache[$id]) || $this->injector->canCreate($id);
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->injector->create($id);
        }

        return $this->cache[$id];
    }

    /**
     * Set an object by id
     *
     * @param string $id
     * @param object $object
     */
    public function set(string $id, object $object): void
    {
        $this->cache[$id] = $object;
    }
}
