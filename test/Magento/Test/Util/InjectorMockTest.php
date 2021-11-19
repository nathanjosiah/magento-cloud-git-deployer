<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;


use Magento\Test\Stub\ClassWithFactories;
use Magento\Test\Stub\EmptyClass;
use PHPUnit\Framework\TestCase;

class InjectorMockTest extends TestCase
{
    private InjectorMock $injector;

    protected function setUp(): void
    {
        $this->injector = new InjectorMock($this);
    }

    public function testCanCreateReturnsTrue()
    {
        self::assertTrue($this->injector->canCreate('abc'));
    }

    public function testCreateClassWithNoDependencies()
    {
        self::assertInstanceOf(EmptyClass::class, $this->injector->create(EmptyClass::class));
    }

    public function testCreateClassWithDependencies()
    {
        self::assertInstanceOf(ClassWithFactories::class, $this->injector->create(ClassWithFactories::class));
    }

    public function testCreateClassWithOptionsCachesUniqueOptionsForEachCreatedObject()
    {
        $deps1 = ['emptyClassFactory' => new \stdClass()];
        $deps2 = ['emptyClassFactory' => new \stdClass()];

        $mock1 = $this->injector->create(ClassWithFactories::class, $deps1);
        $mock2 = $this->injector->create(ClassWithFactories::class, $deps2);
        $mock3 = $this->injector->create(ClassWithFactories::class);

        $options1 = $this->injector->getOptionsForObject($mock1);
        $options2 = $this->injector->getOptionsForObject($mock2);

        self::assertArrayHasKey('emptyClassFactory', $options1);
        self::assertArrayHasKey('emptyClassFactory', $options2);

        self::assertNotSame($deps1['emptyClassFactory'], $options2['emptyClassFactory']);
        self::assertNotSame($deps2['emptyClassFactory'], $options1['emptyClassFactory']);
        self::assertSame($deps1['emptyClassFactory'], $options1['emptyClassFactory']);
        self::assertSame($deps2['emptyClassFactory'], $options2['emptyClassFactory']);

        // Check with no deps is empty array
        self::assertSame([], $this->injector->getOptionsForObject($mock3));

        // Assert default is null when object wasn't cached
        self::assertNull($this->injector->getOptionsForObject(new \stdClass()));
    }
}
