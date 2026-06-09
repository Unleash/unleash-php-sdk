<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureEnabledResult implements FeatureEnabledResult
{
    public function __construct(
        /**
         * @readonly
         */
        private bool $isEnabled = false,
        /**
         * @readonly
         */
        private ?Strategy $strategy = null,
    ) {
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
