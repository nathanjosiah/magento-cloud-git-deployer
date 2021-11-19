<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Stub;

use Magento\Deployer\Model\ObjectManager\Factory;
use Magento\Test\Stub\Namespace1\ClassA;
use Magento\Test\Stub\Namespace1\Namespace1A;

class ClassWithFactories
{
    /**
     * @param Factory<EmptyClass> $emptyClassFactory
     * @param Factory<ClassA> $classAFactory
     * @param Factory<Namespace1A\Class1AA> $class1AAFactory
     * @param Factory<Namespace1A\Class1AB> $class1ABFactory
     */
    public function __construct(
        Factory $emptyClassFactory,
        Factory $classAFactory,
        Factory $class1AAFactory,
        Factory $class1ABFactory
    )
    {
    }
}
