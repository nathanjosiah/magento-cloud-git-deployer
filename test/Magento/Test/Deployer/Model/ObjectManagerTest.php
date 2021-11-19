<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Deployer\Model;

use Kdyby\ParseUseStatements\UseStatements;
use Laminas\Di\InjectorInterface;
use Magento\Deployer\Model\ObjectManager;
use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Test\Stub\ClassWithFactories;
use Magento\Test\Stub\EmptyClass;
use Magento\Test\Stub\Namespace1\ClassA;
use Magento\Test\Stub\Namespace1\Namespace1A\Class1AA;
use Magento\Test\Stub\Namespace1\Namespace1A\Class1AB;
use Magento\Test\Util\InjectorMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectManagerTest extends TestCase
{
    /**
     * @var InjectorInterface|MockObject
     */
    private $injector;
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->injector = $this->createMock(InjectorInterface::class);
        $this->objectManager = new ObjectManager($this->injector);
    }

    public function testGetInstance()
    {
        $original = ObjectManager::getInstance();
        ObjectManager::setInstance($this->objectManager);
        self::assertSame($this->objectManager, ObjectManager::getInstance());
        ObjectManager::setInstance($original);
        self::assertSame($original, ObjectManager::getInstance());
    }

    public function testGet()
    {
        $class = new \stdClass();
        $this->objectManager->set('foobar4', $class);

        // test cache
        self::assertSame($class, $this->objectManager->get('foobar4'));

        $empty = new EmptyClass();

        $this->injector->expects(self::once())
            ->method('create')
            ->with(EmptyClass::class, [])
            ->willReturn($empty);

        self::assertSame($empty, $this->objectManager->get(EmptyClass::class));
    }

    public function testGetCreatesFactories()
    {
        $injector = new InjectorMock($this);
        $objectManager = new ObjectManager($injector);
        $objectManager->set(UseStatements::class, new UseStatements());

        $classWithFactories = $objectManager->get(ClassWithFactories::class);
        self::assertInstanceOf(ClassWithFactories::class, $classWithFactories);

        $deps = $injector->getOptionsForObject($classWithFactories);

        self::assertInstanceOf(Factory::class, $deps['class1AAFactory']);
        self::assertSame(Class1AA::class, $injector->getOptionsForObject($deps['class1AAFactory'])['class']);
        self::assertInstanceOf(Factory::class, $deps['class1ABFactory']);
        self::assertSame(Class1AB::class, $injector->getOptionsForObject($deps['class1ABFactory'])['class']);
        self::assertInstanceOf(Factory::class, $deps['classAFactory']);
        self::assertSame(ClassA::class, $injector->getOptionsForObject($deps['classAFactory'])['class']);
        self::assertInstanceOf(Factory::class, $deps['emptyClassFactory']);
        self::assertSame(EmptyClass::class, $injector->getOptionsForObject($deps['emptyClassFactory'])['class']);
    }

    public function testHasFalse()
    {
        $this->injector->expects(self::once())
            ->method('canCreate')
            ->willReturn(false);

        // Not in cache, injector says false
        self::assertFalse($this->objectManager->has('foobar1'));
    }

    public function testHasWithCached()
    {
        $this->injector->expects(self::never())
            ->method('canCreate');

        $this->objectManager->set('foobar2', new \stdClass());

        self::assertTrue($this->objectManager->has('foobar2'));
    }

    public function testHasTrueNoCache()
    {
        $this->injector->expects(self::once())
            ->method('canCreate')
            ->willReturn(true);

        self::assertTrue($this->objectManager->has('foobar3'));
    }

    public function testSet()
    {
        $this->injector->expects(self::never())
            ->method('create');

        $class = new \stdClass();
        $this->objectManager->set('abc', $class);
        self::assertSame($class, $this->objectManager->get('abc'));
    }
}
