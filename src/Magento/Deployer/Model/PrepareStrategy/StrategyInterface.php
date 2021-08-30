<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\PrepareStrategy;


use Magento\Deployer\Model\Config\PrepareConfig;

interface StrategyInterface
{
    public function execute(PrepareConfig $config): void;
}
