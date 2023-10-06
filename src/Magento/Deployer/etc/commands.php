<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

return [
    'self-update' => \Magento\Deployer\Command\SelfUpdate::class,
    'init' => \Magento\Deployer\Command\InitCommand::class,
    'prepare' => \Magento\Deployer\Command\PrepareCommand::class,
    'apply-hotfix' => \Magento\Deployer\Command\ApplyHotfix::class,
    'list-hotfix' => \Magento\Deployer\Command\ListHotfixes::class,
    'reset-from-cloud' => \Magento\Deployer\Command\ResetFromCloudCommand::class
];
