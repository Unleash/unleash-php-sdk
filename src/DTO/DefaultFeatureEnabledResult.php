<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureEnabledResult implements FeatureEnabledResult
{
    /**
     * @readonly
     */
    private bool $isEnabled = false;
    /**
     * @readonly
     */
    private ?Strategy $strategy = null;
    public function __construct(bool $isEnabled = false, ?Strategy $strategy = null)
    {
        $this->isEnabled = $isEnabled;
        $this->strategy = $strategy;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    #[Override]
    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }
}
