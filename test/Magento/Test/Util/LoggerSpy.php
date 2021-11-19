<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;

use Psr\Log\AbstractLogger;

class LoggerSpy extends AbstractLogger {
    private array $logged = [];

    public function log($level, $message, array $context = array())
    {
        $this->logged[] = compact('message', 'level');
    }

    public function clear(): void
    {
        $this->logged = [];
    }

    public function getLogs(): array
    {
        return $this->logged;
    }
};
