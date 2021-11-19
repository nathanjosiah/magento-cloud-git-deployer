<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;

use Laminas\Di\InjectorInterface;
use PHPUnit\Framework\TestCase;

class InjectorMock implements InjectorInterface
{
    private TestCase $testCase;
    private array $options = [];

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function canCreate(string $name): bool
    {
        return true;
    }

    public function create(string $name, array $options = [])
    {
        $object = $this->testCase->getMockBuilder($name)
            ->disableOriginalConstructor()
            ->getMock();

        $this->options[spl_object_id($object)] = $options;

        return $object;
    }

    public function getOptionsForObject(object $object): ?array
    {
        $id = spl_object_id($object);
        if (isset($this->options[$id])) {
            return $this->options[$id];
        }

        return null;
    }
}
