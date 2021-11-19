<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

use Kdyby\ParseUseStatements\UseStatements;
use Laminas\Di\InjectorInterface;
use Magento\Deployer\Model\ObjectManager\Factory;
use Psr\Container\ContainerInterface;

class ObjectManager implements ContainerInterface
{
    private array $cache;

    static ObjectManager $instance;

    private InjectorInterface $injector;

    private UseStatements $useStatements;

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

    /**
     * @template T
     * @param class-string<T> $id
     * @param array $parameters
     * @return T
     */
    public function create(string $id, array $parameters = []): object
    {
        $parameters = $this->addFactories($id, $parameters);

        return $this->injector->create($id, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->cache[$id]) || $this->injector->canCreate($id);
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id)
    {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->create($id);
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

    private function addFactories(string $id, array $parameters): array
    {
        $reflection = new \ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return $parameters;
        }

        $factories = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getType()->getName() === Factory::class
                && empty($parameters[$parameter->getName()])
            ) {
                $factories[] = $parameter->getName();
            }
        }

        if (empty($factories)) {
            return $parameters;
        }

        $doc = $constructor->getDocComment();
        $useStatements = $this->getUseStatementsInstance()->getUseStatements($reflection);
        foreach ($factories as $factory) {
            preg_match('/.*?Factory\s*<\s*(?P<type>[^>]+)\s*>\s+\$' . preg_quote($factory) . '/', $doc, $matches);
            if (!empty($useStatements[$matches['type']])) {
                $parameters[$factory] = $this->create(
                    Factory::class,
                    ['class' => $useStatements[$matches['type']]]
                );
            } else {
                $parameters[$factory] = $this->create(
                    Factory::class,
                    ['class' => UseStatements::expandClassName($matches['type'], $reflection)]
                );
            }
        }

        return $parameters;
    }

    private function getUseStatementsInstance(): UseStatements
    {
        if (!isset($this->useStatements)) {
            $this->useStatements = $this->get(UseStatements::class);
        }

        return $this->useStatements;
    }
}
