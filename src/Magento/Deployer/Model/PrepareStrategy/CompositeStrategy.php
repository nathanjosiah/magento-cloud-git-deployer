<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\PrepareStrategy;


use Magento\Deployer\Model\Config\PrepareConfig;

class CompositeStrategy implements StrategyInterface
{
    private array $strategies;

    /**
     * @param StrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {

        $this->strategies = $strategies;
    }

    public function execute(PrepareConfig $config): void
    {
        $this->strategies[$config->getStrategy()]->execute($config);
    }
}
