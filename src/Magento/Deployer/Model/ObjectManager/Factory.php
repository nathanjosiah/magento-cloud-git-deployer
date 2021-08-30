<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\ObjectManager;


use Magento\Deployer\Model\ObjectManager;

/**
 * @template T
 */
class Factory
{
    private ObjectManager $objectManager;
    private string $class;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager, string $class)
    {
        $this->objectManager = $objectManager;
        $this->class = $class;
    }

    /**
     * @param array $parameters
     * @return T
     */
    public function create(array $parameters = []): object
    {
        return $this->objectManager->create($this->class, $parameters);
    }
}
