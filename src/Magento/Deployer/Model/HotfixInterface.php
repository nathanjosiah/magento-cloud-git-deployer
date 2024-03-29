<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;

interface HotfixInterface
{
    public function apply(): void;

    public function getConfirmationQuestions(): array;
}