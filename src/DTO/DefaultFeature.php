<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeature implements Feature
{
    /**
     * @readonly
     */
    private string $name;
    /**
     * @readonly
     */
    private bool $enabled;
    /**
     * @var iterable<Strategy>
     * @readonly
     */
    private iterable $strategies;
    /**
     * @var array<Variant>
     * @readonly
     */
    private array $variants = [];
    /**
     * @readonly
     */
    private bool $impressionData = false;
    /**
     * @var array<FeatureDependency>
     * @readonly
     */
    private array $dependencies = [];
    /**
     * @param iterable<Strategy>       $strategies
     * @param array<Variant>           $variants
     * @param array<FeatureDependency> $dependencies
     */
    public function __construct(string $name, bool $enabled, iterable $strategies, array $variants = [], bool $impressionData = false, array $dependencies = [])
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->variants = $variants;
        $this->impressionData = $impressionData;
        $this->dependencies = $dependencies;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return iterable<Strategy>
     */
    #[Override]
    public function getStrategies(): iterable
    {
        return $this->strategies;
    }

    /**
     * @return array<Variant>
     */
    #[Override]
    public function getVariants(): array
    {
        return $this->variants;
    }

    #[Override]
    public function hasImpressionData(): bool
    {
        return $this->impressionData;
    }

    /**
     * @return array<FeatureDependency>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
