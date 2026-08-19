<?php

namespace Unleash\Client\DTO\Internal;

use Override;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;

/**
 * @internal
 */
final class UnresolvedFeature implements Feature
{
    /**
     * @readonly
     * @var string
     */
    private $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return false;
    }

    #[Override]
    public function getStrategies(): iterable
    {
        return [];
    }

    #[Override]
    public function getVariants(): array
    {
        return [];
    }

    #[Override]
    public function hasImpressionData(): bool
    {
        return false;
    }

    /**
     * @return array<FeatureDependency>
     */
    public function getDependencies(): array
    {
        return [];
    }
}
