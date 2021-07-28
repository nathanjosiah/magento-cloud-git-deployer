<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\ObjectManager;


use Magento\Deployer\Model\ObjectManager;

/**
 *
 */
class Factory
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $class;

    /**
     * @param ObjectManager $objectManager
     * @param string $class
     */
    public function __construct(ObjectManager $objectManager, string $class)
    {
        $this->objectManager = $objectManager;
        $this->class = $class;
    }

    public function create(array $parameters = [])
    {
        return $this->objectManager->create($this->class, $parameters);
    }
}
