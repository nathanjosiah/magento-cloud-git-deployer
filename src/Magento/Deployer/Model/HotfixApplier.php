<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HotfixApplier
{
    private LoggerInterface $logger;
    private array $hotfixes;

    /**
     * @param LoggerInterface $logger
     * @param HotfixInterface[] $hotfixes
     */
    public function __construct(LoggerInterface $logger, array $hotfixes)
    {
        $this->logger = $logger;
        $this->hotfixes = $hotfixes;
    }

    public function getAvailablePatches(): array
    {
        return array_keys($this->hotfixes);
    }

    public function validateAllExist(array $patchNames): void
    {
        $validHotfixes = $this->getAvailablePatches();
        if (count(array_intersect($validHotfixes, $patchNames)) !== count($patchNames)) {
            $this->logger->error('Invalid hotfix supplied, valid hotfixes are: "' . implode('", "', $validHotfixes) . '"');
            exit;
        }
    }

    public function apply(string $name): void
    {
        $hotfix = $this->getHotfix($name);
        $this->logger->info('Applying <fg=blue>' . $name);
        $hotfix->apply();
    }

    public function getHotfix(string $hotfixName): HotfixInterface
    {
        if (empty($this->hotfixes[$hotfixName])) {
            throw new \InvalidArgumentException('No hotfix is defined for <fg=blue>' . $hotfixName);
        }

        return $this->hotfixes[$hotfixName];
    }

    public function getConfirmationQuestions(string $hotfixName): array
    {
        return $this->getHotfix($hotfixName)->getConfirmationQuestions();
    }
}
