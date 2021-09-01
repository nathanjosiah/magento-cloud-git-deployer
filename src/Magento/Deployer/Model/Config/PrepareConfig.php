<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Deployer\Model\Config;

class PrepareConfig
{
    const STRATEGY_TRADITIONAL = 'traditional';
    const STRATEGY_VCS = 'vcs';
    const STRATEGY_COMPOSER = 'composer';
    private string $path;
    private array $exclude;
    private array $hotfixes = [];
    private string $eceVersion;
    private string $cloudBranch;
    private string $strategy;
    private ?string $communityEdition;
    private ?string $enterpriseEdition;
    private ?string $businessEdition;
    private ?string $securityPackage;
    private ?string $fastly;
    private array $additionalRepos;

    /**
     * @return string|null
     */
    public function getCommunityEdition(): ?string
    {
        return $this->communityEdition;
    }

    /**
     * @param string|null $communityEdition
     */
    public function setCommunityEdition(?string $communityEdition): void
    {
        $this->communityEdition = $communityEdition;
    }

    /**
     * @return string|null
     */
    public function getEnterpriseEdition(): ?string
    {
        return $this->enterpriseEdition;
    }

    /**
     * @param string|null $enterpriseEdition
     */
    public function setEnterpriseEdition(?string $enterpriseEdition): void
    {
        $this->enterpriseEdition = $enterpriseEdition;
    }

    /**
     * @return string|null
     */
    public function getBusinessEdition(): ?string
    {
        return $this->businessEdition;
    }

    /**
     * @param string|null $businessEdition
     */
    public function setBusinessEdition(?string $businessEdition): void
    {
        $this->businessEdition = $businessEdition;
    }

    /**
     * @return string|null
     */
    public function getSecurityPackage(): ?string
    {
        return $this->securityPackage;
    }

    /**
     * @param string|null $securityPackage
     */
    public function setSecurityPackage(?string $securityPackage): void
    {
        $this->securityPackage = $securityPackage;
    }

    /**
     * @return string|null
     */
    public function getFastly(): ?string
    {
        return $this->fastly;
    }

    /**
     * @param string|null $fastly
     */
    public function setFastly(?string $fastly): void
    {
        $this->fastly = $fastly;
    }

    /**
     * @return array
     */
    public function getAdditionalRepos(): array
    {
        return $this->additionalRepos;
    }

    /**
     * @param array $additionalRepos
     */
    public function setAdditionalRepos(array $additionalRepos): void
    {
        $this->additionalRepos = $additionalRepos;
    }

    /**
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * @param string $strategy
     */
    public function setStrategy(string $strategy): void
    {
        $this->strategy = $strategy;
    }

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
     * @return array
     */
    public function getHotfixes(): array
    {
        return $this->hotfixes;
    }

    /**
     * @param array $hotfixes
     */
    public function setHotfixes(array $hotfixes): void
    {
        $this->hotfixes = $hotfixes;
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
