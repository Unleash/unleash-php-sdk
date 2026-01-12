<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureDependency implements FeatureDependency
{
    /**
     * @readonly
     */
    private ?Feature $feature;
    /**
     * @readonly
     */
    private bool $expectedState;
    /**
     * @var array<Variant>|null
     * @readonly
     */
    private ?array $requiredVariants;
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(?Feature $feature, bool $expectedState, ?array $requiredVariants)
    {
        $this->feature = $feature;
        $this->expectedState = $expectedState;
        $this->requiredVariants = $requiredVariants;
    }

    #[Override]
    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    #[Override]
    public function getExpectedState(): bool
    {
        return $this->expectedState;
    }

    #[Override]
    public function getRequiredVariants(): ?array
    {
        return $this->requiredVariants;
    }

    #[Override]
    public function isResolved(): bool
    {
        return true;
    }
}
