<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Config;

class PrepareConfig
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $exclude;

    /**
     * @var bool
     */
    private $laminasFix;

    /**
     * @var string
     */
    private $eceVersion;

    /**
     * @var string
     */
    private $cloudBranch;

    /**
     * @return bool
     */
    public function isComposer2(): bool
    {
        return $this->composer2;
    }

    /**
     * @param bool $composer2
     */
    public function setIsComposer2(bool $composer2): void
    {
        $this->composer2 = $composer2;
    }

    /**
     * @var bool
     */
    private $composer2;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * @param array $exclude
     */
    public function setExclude(array $exclude): void
    {
        $this->exclude = $exclude;
    }

    /**
     * @return bool
     */
    public function isLaminasFix(): bool
    {
        return $this->laminasFix;
    }

    /**
     * @param bool $laminasFix
     */
    public function setIsLaminasFix(bool $laminasFix): void
    {
        $this->laminasFix = $laminasFix;
    }

    /**
     * @return string
     */
    public function getEceVersion(): string
    {
        return $this->eceVersion;
    }

    /**
     * @param string $eceVersion
     */
    public function setEceVersion(string $eceVersion): void
    {
        $this->eceVersion = $eceVersion;
    }

    /**
     * @return string
     */
    public function getCloudBranch(): string
    {
        return $this->cloudBranch;
    }

    /**
     * @param string $cloudBranch
     */
    public function setCloudBranch(string $cloudBranch): void
    {
        $this->cloudBranch = $cloudBranch;
    }
}
