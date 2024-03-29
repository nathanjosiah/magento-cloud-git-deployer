<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\ObjectManager;

use Laminas\Di\Resolver\ValueInjection;
use Magento\Deployer\Model\ObjectManager;
use Psr\Container\ContainerInterface;

class FactoryProxy extends ValueInjection
{
    public function toValue(ContainerInterface $container)
    {
        return ObjectManager::getInstance()
            ->create(Factory::class, ['class' => $this->value]);
    }

    public function isExportable(): bool
    {
        return false;
    }
}
