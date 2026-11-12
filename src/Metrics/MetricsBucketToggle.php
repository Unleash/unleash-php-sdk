<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    public function __construct(
        /**
         * @readonly
         */
        private Feature $feature,
        /**
         * @readonly
         */
        private bool $success,
        /**
         * @readonly
         */
        private ?Variant $variant = null,
    ) {
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }
}
